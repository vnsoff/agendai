<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BusinessUpdateRequest;
use App\Models\AuditLog;
use App\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function edit(CurrentTenant $current): Response
    {
        Gate::authorize('manage-business');

        return Inertia::render('settings/business', [
            'business' => $current->get()?->only(['name', 'slug', 'timezone']),
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function update(BusinessUpdateRequest $request, CurrentTenant $current): RedirectResponse
    {
        $tenant = $current->get() ?? abort(404);
        $tenant->fill($request->validated());

        if ($tenant->isDirty()) {
            DB::transaction(function () use ($tenant): void {
                AuditLog::record('tenant.updated', $tenant, AuditLog::diff($tenant));
                $tenant->save();
            });
        }

        return to_route('business.edit');
    }
}
