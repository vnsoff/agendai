<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Closure;

/**
 * The business the current request (or job) is acting for. Registered as a scoped binding,
 * so it starts empty on every request and every queued job.
 */
class CurrentTenant
{
    private ?Tenant $tenant = null;

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Run a callback as another tenant (seeders, jobs, the public booking page), then restore.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Tenant $tenant, Closure $callback): mixed
    {
        $previous = $this->tenant;
        $this->tenant = $tenant;

        try {
            return $callback();
        } finally {
            $this->tenant = $previous;
        }
    }
}
