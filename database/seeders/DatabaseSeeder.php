<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * A demo business with one owner and one staff member (password: "password").
     */
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Studio Bella',
            'slug' => 'studio-bella',
        ]);

        User::factory()->forTenant($tenant)->owner()->create([
            'name' => 'Bella Souza',
            'email' => 'dono@demo.agendai.dev',
        ]);

        User::factory()->forTenant($tenant)->create([
            'name' => 'Carla Lima',
            'email' => 'profissional@demo.agendai.dev',
            'role' => Role::Staff,
        ]);
    }
}
