<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

/**
 * Neon routes connections by SNI. The libpq bundled with the PHP runtime on Vercel doesn't send
 * SNI, so for Neon hosts we pass the endpoint ID in libpq's `options` parameter instead.
 *
 * @see https://neon.com/docs/connect/connection-errors#the-endpoint-id-is-not-specified
 */
class NeonPostgresConnector extends PostgresConnector
{
    /**
     * @param  array<string, mixed>  $config
     */
    protected function getDsn(array $config): string
    {
        $dsn = parent::getDsn($config);

        $endpoint = self::endpointId((string) ($config['host'] ?? ''));

        if ($endpoint === null) {
            return $dsn;
        }

        // Laravel's server_options may already have opened an options='...' value: join it.
        if (str_contains($dsn, ";options='")) {
            return str_replace(";options='", ";options='endpoint={$endpoint} ", $dsn);
        }

        return $dsn.";options='endpoint={$endpoint}'";
    }

    /**
     * "ep-cool-name-123456-pooler.c-2.sa-east-1.aws.neon.tech" → "ep-cool-name-123456".
     */
    public static function endpointId(string $host): ?string
    {
        if (! str_ends_with($host, '.neon.tech')) {
            return null;
        }

        return preg_match('/^(ep-[a-z0-9-]+?)(?:-pooler)?\./', $host, $match) === 1 ? $match[1] : null;
    }
}
