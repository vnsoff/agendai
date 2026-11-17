<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Guards the rule that keeps businesses apart: a model whose table has a tenant_id must use
 * BelongsToTenant. Forgetting it in M2+ fails here instead of leaking data in production.
 */
class ArchitectureTest extends TestCase
{
    use RefreshDatabase;

    /** Models deliberately outside the trait, each with a reason in its docblock. */
    private const EXEMPT = [Tenant::class, User::class];

    public function test_every_model_with_a_tenant_id_uses_belongs_to_tenant()
    {
        $checked = 0;

        foreach (File::allFiles(app_path('Models')) as $file) {
            $class = 'App\\Models\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (! is_subclass_of($class, Model::class) || in_array($class, self::EXEMPT, true)) {
                continue;
            }

            $table = (new $class)->getTable();

            if (Schema::hasColumn($table, 'tenant_id')) {
                $this->assertContains(BelongsToTenant::class, class_uses_recursive($class), "{$class} has a tenant_id but doesn't use BelongsToTenant.");
                $checked++;
            }
        }

        $this->assertGreaterThan(0, $checked);
    }
}
