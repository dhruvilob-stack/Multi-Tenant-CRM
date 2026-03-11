<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantDatabaseManager;
use App\Services\TenantResolver;
use App\Support\TenantUserMirror;
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

        // log each tenant login attempt to help debug loops caused by stale
        // sessions or unexpected guard behaviour
        \Log::debug('tenant login request received', [
            'tenant' => $tenant,
            'email' => $credentials['email'],
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);

        if (Auth::guard('tenant')->check()) {
            $expectedRole = (string) $request->session()->get('tenant_expected_role', '');
            Auth::guard('tenant')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('tenant_id', $tenantModel->id);
            $request->session()->put('tenant_slug', $tenantModel->slug ?: $tenantModel->id);
            if ($expectedRole !== '') {
                $request->session()->put('tenant_expected_role', $expectedRole);
            }
        }

        $attempt = fn (): bool => Auth::guard('tenant')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember);

        // perform the first attempt, pulling data from the landlord if the
        // user record happens to be missing. regenerating the session after a
        // successful login is important – it clears out any leftover state from
        // previous sessions (an org-admin cookie on a resource device was the
        // root cause of the redirect loops reported by the user) and protects
        // against session fixation attacks.
        if (! $attempt()) {
            $this->syncTenantUserFromLandlord($tenantModel, (string) $credentials['email']);

            if (! $attempt()) {
                throw ValidationException::withMessages([
                    'email' => 'These credentials do not match our records.',
                ]);
            }
        }

        // regenerate once the user is authenticated; we deliberately perform
        // the action here rather than in the guard so that we can do it even
        // when the second "sync" attempt succeeds.
        $request->session()->regenerate();

        $tenantSlug = (string) ($tenantModel->slug ?: $tenantModel->id);
        $organization = Organization::query()
            ->where('tenant_id', $tenantModel->id)
            ->first();
        $user = Auth::guard('tenant')->user();

        \Log::debug('tenant login succeeded', [
            'user_id' => $user?->id,
            'role' => $user?->role,
            'tenant_slug' => $tenantSlug,
        ]);

        if (! $user) {
            return redirect("/{$tenantSlug}/login");
        }

        if ($organization && (int) $user->organization_id !== (int) $organization->id) {
            Auth::guard('tenant')->logout();
            $request->session()->forget('tenant_expected_role');

            throw ValidationException::withMessages([
                'email' => 'This account does not belong to the selected organization.',
            ]);
        }

        $expectedRole = (string) $request->session()->pull('tenant_expected_role', '');
        if ($expectedRole !== '' && (string) $user->role !== $expectedRole) {
            Auth::guard('tenant')->logout();

            throw ValidationException::withMessages([
                'email' => 'Role mismatch for this login URL.',
            ]);
        }

        TenantUserMirror::syncToLandlord($user);

        return redirect($this->roleLandingPath($tenantSlug, (string) $user->role));
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
                'first_name' => $landlordUser->first_name,
                'last_name' => $landlordUser->last_name,
                'email' => (string) $landlordUser->email,
                'contact_email' => $landlordUser->contact_email,
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

    private function roleLandingPath(string $tenantSlug, string $role): string
    {
        return match ($role) {
            UserRole::MANUFACTURER => "/{$tenantSlug}/manufacturer",
            UserRole::DISTRIBUTOR => "/{$tenantSlug}/distributor",
            UserRole::VENDOR => "/{$tenantSlug}/vendor",
            UserRole::CONSUMER => "/{$tenantSlug}/consumer",
            default => "/{$tenantSlug}",
        };
    }
}
