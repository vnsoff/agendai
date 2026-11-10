<?php

namespace Tests\Unit;

use App\Database\NeonPostgresConnector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class NeonPostgresConnectorTest extends TestCase
{
    /**
     * @return array<string, array{string, ?string}>
     */
    public static function hosts(): array
    {
        return [
            'direct' => ['ep-aged-meadow-b6ezst36.c-2.sa-east-1.aws.neon.tech', 'ep-aged-meadow-b6ezst36'],
            'pooled' => ['ep-aged-meadow-b6ezst36-pooler.c-2.sa-east-1.aws.neon.tech', 'ep-aged-meadow-b6ezst36'],
            'local postgres' => ['127.0.0.1', null],
            'other provider' => ['ep-lookalike.example.com', null],
        ];
    }

    #[DataProvider('hosts')]
    public function test_it_extracts_the_neon_endpoint_id(string $host, ?string $expected): void
    {
        $this->assertSame($expected, NeonPostgresConnector::endpointId($host));
    }

    public function test_it_adds_the_endpoint_option_for_neon_hosts(): void
    {
        $dsn = $this->dsn(['host' => 'ep-aged-meadow-b6ezst36-pooler.c-2.sa-east-1.aws.neon.tech', 'database' => 'neondb']);

        $this->assertStringEndsWith(";options='endpoint=ep-aged-meadow-b6ezst36'", $dsn);
    }

    public function test_it_merges_with_laravel_server_options(): void
    {
        $dsn = $this->dsn([
            'host' => 'ep-aged-meadow-b6ezst36.c-2.sa-east-1.aws.neon.tech',
            'database' => 'neondb',
            'server_options' => ['statement_timeout' => '5s'],
        ]);

        $this->assertStringContainsString(";options='endpoint=ep-aged-meadow-b6ezst36 -c statement_timeout=5s'", $dsn);
        $this->assertSame(1, substr_count($dsn, 'options='));
    }

    public function test_it_leaves_other_hosts_untouched(): void
    {
        $this->assertStringNotContainsString('options=', $this->dsn(['host' => '127.0.0.1', 'database' => 'agendai']));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function dsn(array $config): string
    {
        return (string) (new ReflectionMethod(NeonPostgresConnector::class, 'getDsn'))
            ->invoke(new NeonPostgresConnector, $config);
    }
}
