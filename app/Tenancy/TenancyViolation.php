<?php

namespace App\Tenancy;

use LogicException;

/**
 * Thrown instead of silently returning or writing another business's data.
 */
class TenancyViolation extends LogicException
{
    public static function noTenant(string $model): self
    {
        return new self("Tried to use [{$model}] without a current tenant. Set one with CurrentTenant::run() or opt out explicitly with withoutTenantScope().");
    }

    public static function crossTenantWrite(string $model): self
    {
        return new self("Tried to save [{$model}] into a tenant other than the current one.");
    }
}
