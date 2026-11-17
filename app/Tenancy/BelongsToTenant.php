<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scopes every query to the current tenant and stamps tenant_id on create, so isolation never
 * depends on remembering a WHERE clause. tests/Feature/Tenancy/ArchitectureTest.php fails if a
 * business model forgets this trait.
 *
 * @mixin Model
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $current = app(CurrentTenant::class)->get();

            if ($model->getAttribute('tenant_id') === null) {
                $model->setAttribute('tenant_id', ($current ?? throw TenancyViolation::noTenant($model::class))->id);
            } elseif ($current !== null && (int) $model->getAttribute('tenant_id') !== $current->id) {
                throw TenancyViolation::crossTenantWrite($model::class);
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('tenant_id')) {
                throw TenancyViolation::crossTenantWrite($model::class);
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Explicit, greppable opt-out for the rare cross-tenant query (platform admin, tests).
     *
     * @return Builder<static>
     */
    public static function withoutTenantScope(): Builder
    {
        return static::query()->withoutGlobalScope(TenantScope::class);
    }
}
