<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'business_name' => 'Studio Bella',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registering_creates_the_business_with_the_user_as_owner()
    {
        $this->post(route('register.store'), $this->registration('Studio Bella', 'bella@example.com'));

        $user = User::where('email', 'bella@example.com')->firstOrFail();

        $this->assertSame('Studio Bella', $user->tenant->name);
        $this->assertSame('studio-bella', $user->tenant->slug);
        $this->assertSame(Role::Owner, $user->role);
        $this->assertSame(['tenant.created'], AuditLog::withoutTenantScope()->pluck('action')->all());
    }

    public function test_businesses_with_the_same_name_get_distinct_slugs()
    {
        $this->post(route('register.store'), $this->registration('Studio Bella', 'a@example.com'));
        auth()->logout();
        $this->post(route('register.store'), $this->registration('Studio Bella', 'b@example.com'));

        $this->assertSame(['studio-bella', 'studio-bella-2'], Tenant::orderBy('id')->pluck('slug')->all());
    }

    public function test_business_name_is_required()
    {
        $response = $this->post(route('register.store'), $this->registration('', 'c@example.com'));

        $response->assertSessionHasErrors('business_name');
        $this->assertGuest();
        $this->assertSame(0, Tenant::count());
    }

    public function test_a_failed_signup_leaves_no_orphan_business()
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $tenantsBefore = Tenant::count();

        $this->post(route('register.store'), $this->registration('Another Studio', 'taken@example.com'))
            ->assertSessionHasErrors('email');

        $this->assertSame($tenantsBefore, Tenant::count());
    }

    /**
     * @return array<string, string>
     */
    private function registration(string $business, string $email): array
    {
        return [
            'business_name' => $business,
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
    }
}
