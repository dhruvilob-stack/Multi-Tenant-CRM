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

        $request->session()->put('tenant_id', $tenantModel->id);
        $request->session()->put('tenant_slug', $tenantModel->slug ?: $tenantModel->id);
        $tenantDatabaseManager->activateTenantConnection($tenantModel);

        if (! Auth::guard('tenant')->check()) {
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

        return redirect($this->roleLandingPath((string) ($tenantModel->slug ?: $tenantModel->id), (string) $target->role));
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
