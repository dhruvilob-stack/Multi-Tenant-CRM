<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TenantDatabaseManager;
use App\Services\TenantResolver;
use App\Support\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(
        string $tenant,
        int $user,
        Request $request,
        TenantResolver $tenantResolver,
        TenantDatabaseManager $tenantDatabaseManager
    ): RedirectResponse {
        $tenantModel = $tenantResolver->resolveByIdentifier($tenant);
        abort_unless($tenantModel, 404, 'Tenant not found.');

        // record some context for debugging
        \Log::debug('impersonation.start request', [
            'tenant' => $tenant,
            'session_id' => $request->session()->getId(),
            'cookies' => $request->cookies->all(),
            'guard_user_before' => Auth::guard('tenant')->user()?->id,
        ]);

        $request->session()->put('tenant_id', $tenantModel->id);
        $request->session()->put('tenant_slug', $tenantModel->slug ?: $tenantModel->id);
        $tenantDatabaseManager->activateTenantConnection($tenantModel);

        if (! Auth::guard('tenant')->check()) {
            \Log::warning('impersonation start: guard check failed, redirecting to login', ['tenant' => $tenant]);
            return redirect("/{$tenant}/login");
        }

        $actor = Auth::guard('tenant')->user();
        if (! $actor || $actor->role !== UserRole::ORG_ADMIN) {
            abort(403);
        }

        $target = User::query()->findOrFail($user);

        if ((int) $actor->organization_id !== (int) $target->organization_id) {
            abort(403);
        }

        if (! in_array((string) $target->role, [
            UserRole::MANUFACTURER,
            UserRole::DISTRIBUTOR,
            UserRole::VENDOR,
            UserRole::CONSUMER,
        ], true)) {
            abort(422, 'Only downward roles can be impersonated.');
        }

        if ((int) $actor->id === (int) $target->id) {
            return redirect("/{$tenant}");
        }

        $request->session()->put('impersonator_id', (int) $actor->id);
        $request->session()->put('impersonator_tenant', (string) ($tenantModel->slug ?: $tenantModel->id));
        $request->session()->put('impersonator_name', (string) $actor->name);

        Auth::guard('tenant')->loginUsingId((int) $target->id);

        // dump session after impersonation so we can verify the user ID was saved
        \Log::debug('impersonation session after login', [
            'session_data' => $request->session()->all(),
            'guard_user' => Auth::guard('tenant')->user()?->id,
        ]);

        // redirect to the panel root with flags that tell our middleware
        // and client script to behave. the `impersonated` param prevents any
        // rewriting or redirect logic from running on the first post-login
        // request, and the client‑side script (re‑added below) will swap the
        // visible URL to `/{$tenantSlug}/{$role}` afterwards.
        $role = (string) $target->role;
        $tenantSlug = (string) ($tenantModel->slug ?: $tenantModel->id);

        return redirect("/{$tenantSlug}?impersonated=1&role_dashboard_alias=1&role={$role}");
    }

    public function stop(
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

        $impersonatorId = (int) $request->session()->get('impersonator_id', 0);

        if ($impersonatorId > 0) {
            Auth::guard('tenant')->loginUsingId($impersonatorId);
        }

        $request->session()->forget([
            'impersonator_id',
            'impersonator_tenant',
            'impersonator_name',
        ]);

        return redirect("/{$tenant}");
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
