<?php

namespace Tests\Feature\Settings;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_see_the_business_settings()
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get(route('business.edit'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('settings/business')->where('business.name', $owner->tenant->name));
    }

    public function test_staff_cannot_see_or_change_the_business_settings()
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('business.edit'))->assertForbidden();
        $this->actingAs($staff)->patch(route('business.update'), $this->payload())->assertForbidden();
    }

    public function test_owner_can_update_the_business_and_the_change_is_audited()
    {
        $owner = User::factory()->owner()->create();
        $oldName = $owner->tenant->name;

        $this->actingAs($owner)->patch(route('business.update'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('business.edit'));

        $tenant = $owner->tenant->fresh();
        $this->assertSame('Barbearia Central', $tenant?->name);
        $this->assertSame('barbearia-central', $tenant?->slug);
        $this->assertSame('America/Manaus', $tenant?->timezone);

        $log = AuditLog::withoutTenantScope()->where('action', 'tenant.updated')->sole();
        $this->assertSame($owner->id, $log->user_id);
        // assertEquals, not assertSame: jsonb stores object keys in its own order.
        $this->assertEquals(['from' => $oldName, 'to' => 'Barbearia Central'], $log->changes['name'] ?? null);
    }

    public function test_saving_without_changes_writes_no_audit_entry()
    {
        $owner = User::factory()->owner()->create();
        $tenant = $owner->tenant;

        $this->actingAs($owner)->patch(route('business.update'), $tenant->only(['name', 'slug', 'timezone']));

        $this->assertSame(0, AuditLog::withoutTenantScope()->where('action', 'tenant.updated')->count());
    }

    public function test_slug_must_be_unique_across_businesses()
    {
        Tenant::factory()->create(['slug' => 'barbearia-central']);
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->patch(route('business.update'), $this->payload())
            ->assertSessionHasErrors('slug');
    }

    public function test_slug_and_timezone_are_validated()
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->patch(route('business.update'), $this->payload(['slug' => 'Não Vale!', 'timezone' => 'Mars/Olympus']))
            ->assertSessionHasErrors(['slug', 'timezone']);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Barbearia Central',
            'slug' => 'barbearia-central',
            'timezone' => 'America/Manaus',
            ...$overrides,
        ];
    }
}
