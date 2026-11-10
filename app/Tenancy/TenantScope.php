<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Fail closed: with no tenant we refuse to query rather than return every business's rows.
        $tenant = app(CurrentTenant::class)->get() ?? throw TenancyViolation::noTenant($model::class);

        $builder->where($model->qualifyColumn('tenant_id'), $tenant->id);
    }
}
