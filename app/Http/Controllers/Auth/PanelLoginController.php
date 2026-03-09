<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantDatabaseManager;
use App\Services\TenantResolver;
use App\Support\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PanelLoginController extends Controller
{
    public function loginSuperAdmin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);

        if (! Auth::guard('super_admin')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $user = Auth::guard('super_admin')->user();
        if (! $user || $user->role !== UserRole::SUPER_ADMIN) {
            Auth::guard('super_admin')->logout();
            throw ValidationException::withMessages([
                'email' => 'This account is not allowed in super-admin panel.',
            ]);
        }

        return redirect('/super-admin');
    }

    public function loginTenant(
        string $tenant,
        Request $request,
        TenantResolver $tenantResolver,
        TenantDatabaseManager $tenantDatabaseManager
    ): RedirectResponse {
        $tenantModel = $tenantResolver->resolveByIdentifier($tenant);
        abort_unless($tenantModel, 404, 'Tenant not found.');

        $request->session()->put('tenant_id', $tenantModel->id);
        $request->session()->put('tenant_slug', $tenantModel->slug ?: $tenantModel->id);
        $tenantDatabaseManager->activateTenantConnection($tenantModel);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);

        $attempt = fn (): bool => Auth::guard('tenant')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember);

        if (! $attempt()) {
            $this->syncTenantUserFromLandlord($tenantModel, (string) $credentials['email']);

            if (! $attempt()) {
                throw ValidationException::withMessages([
                    'email' => 'These credentials do not match our records.',
                ]);
            }
        }

        return redirect('/' . ($tenantModel->slug ?: $tenantModel->id));
    }

    private function syncTenantUserFromLandlord(Tenant $tenant, string $email): void
    {
        $organization = Organization::query()
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $organization) {
            return;
        }

        $landlordUser = User::query()
            ->where('organization_id', $organization->id)
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->first();

        if (! $landlordUser) {
            return;
        }

        $tenantConnection = config('tenancy.tenant_connection', 'tenant');

        DB::connection($tenantConnection)->table('organizations')->updateOrInsert(
            ['id' => (int) $organization->id],
            [
                'tenant_id' => null,
                'name' => (string) $organization->name,
                'slug' => (string) $organization->slug,
                'email' => (string) $organization->email,
                'status' => (string) ($organization->status ?: 'active'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::connection($tenantConnection)->table('users')->updateOrInsert(
            ['email' => $landlordUser->email],
            [
                'organization_id' => (int) $organization->id,
                'parent_id' => null,
                'name' => (string) $landlordUser->name,
                'email' => (string) $landlordUser->email,
                'password' => (string) $landlordUser->password,
                'role' => (string) ($landlordUser->role ?: UserRole::ORG_ADMIN),
                'status' => (string) ($landlordUser->status ?: 'active'),
                'email_verified_at' => $landlordUser->email_verified_at,
                'remember_token' => $landlordUser->remember_token,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
