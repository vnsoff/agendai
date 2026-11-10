<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private CurrentTenant $current) {}

    /**
     * Signing up creates the business and its owner together: there is no user without a tenant.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'business_name' => ['required', 'string', 'max:120'],
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $tenant = Tenant::create([
                'name' => $input['business_name'],
                'slug' => Tenant::uniqueSlug($input['business_name']),
            ]);

            $user = new User([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);
            // Not mass assignable on purpose: a profile form must never be able to set these.
            $user->forceFill(['tenant_id' => $tenant->id, 'role' => Role::Owner])->save();

            $this->current->run($tenant, fn () => AuditLog::create([
                'user_id' => $user->id,
                'action' => 'tenant.created',
                'subject_type' => $tenant->getMorphClass(),
                'subject_id' => $tenant->id,
                'ip' => request()->ip(),
            ]));

            return $user;
        });
    }
}
