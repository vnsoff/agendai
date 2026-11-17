<?php

namespace Tests\Feature\Tenancy;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\CurrentTenant;
use App\Tenancy\TenancyViolation;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AuditLog is the first tenant-owned model; every business model added later inherits the same
 * behaviour from BelongsToTenant (ArchitectureTest makes sure they do).
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $a;

    private Tenant $b;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = Tenant::factory()->create();
        $this->b = Tenant::factory()->create();

        $this->as($this->a, fn () => AuditLog::record('a.thing'));
        $this->as($this->b, fn () => AuditLog::record('b.thing'));
    }

    public function test_queries_only_see_the_current_tenants_rows()
    {
        $this->assertSame(['a.thing'], $this->as($this->a, fn () => AuditLog::pluck('action')->all()));
        $this->assertSame(['b.thing'], $this->as($this->b, fn () => AuditLog::pluck('action')->all()));
    }

    public function test_finding_another_tenants_row_by_id_returns_nothing()
    {
        $bRow = AuditLog::withoutTenantScope()->where('action', 'b.thing')->firstOrFail();

        $this->assertNull($this->as($this->a, fn () => AuditLog::find($bRow->id)));
    }

    public function test_new_rows_are_stamped_with_the_current_tenant()
    {
        $log = $this->as($this->a, fn () => AuditLog::record('a.other'));

        $this->assertSame($this->a->id, $log->tenant_id);
    }

    public function test_querying_without_a_tenant_fails_closed()
    {
        $this->expectException(TenancyViolation::class);

        AuditLog::count();
    }

    public function test_creating_without_a_tenant_fails_closed()
    {
        $this->expectException(TenancyViolation::class);

        AuditLog::create(['action' => 'orphan']);
    }

    public function test_writing_into_another_tenant_is_refused()
    {
        $this->expectException(TenancyViolation::class);

        $this->as($this->a, fn () => AuditLog::create(['tenant_id' => $this->b->id, 'action' => 'sneaky']));
    }

    public function test_moving_a_row_to_another_tenant_is_refused()
    {
        $log = $this->as($this->a, fn () => AuditLog::firstOrFail());

        $this->expectException(TenancyViolation::class);

        $this->as($this->a, fn () => $log->update(['tenant_id' => $this->b->id]));
    }

    public function test_a_signed_in_request_acts_for_the_users_own_business()
    {
        $user = User::factory()->owner()->forTenant($this->b)->create();

        $this->actingAs($user)->get(route('business.edit'))
            ->assertInertia(fn ($page) => $page->where('business.slug', $this->b->slug));
    }

    public function test_the_tenant_is_restored_after_running_as_another()
    {
        $current = app(CurrentTenant::class);
        $current->set($this->a);

        $current->run($this->b, fn () => null);

        $this->assertSame($this->a->id, $current->get()?->id);
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function as(Tenant $tenant, Closure $callback): mixed
    {
        return app(CurrentTenant::class)->run($tenant, $callback);
    }
}
