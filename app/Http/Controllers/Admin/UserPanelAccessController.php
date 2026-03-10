<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantDatabaseManager;
use App\Services\TenantResolver;
use App\Support\PanelLoginPrefillStore;
use App\Support\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class UserPanelAccessController extends Controller
{
    public function open(
        string $tenant,
        int $user,
        Request $request,
        TenantResolver $tenantResolver,
        TenantDatabaseManager $tenantDatabaseManager
    ): RedirectResponse
    {
        $tenantModel = $tenantResolver->resolveByIdentifier($tenant);
        abort_unless($tenantModel, 404, 'Tenant not found.');

        $request->session()->put('tenant_id', $tenantModel->id);
        $request->session()->put('tenant_slug', $tenantModel->slug ?: $tenantModel->id);
        $tenantDatabaseManager->activateTenantConnection($tenantModel);

        $targetUser = User::query()->findOrFail($user);
        $actor = Auth::guard('tenant')->user();

        if (! $actor) {
            return redirect("/{$tenant}/login");
        }

        if ($actor->role !== UserRole::ORG_ADMIN) {
            abort(403);
        }

        if ((int) $actor->organization_id !== (int) $targetUser->organization_id) {
            abort(403);
        }

        $requestedTenant = $tenant;
        $organization = Organization::query()->find((int) $actor->organization_id);
        $tenantModel = $organization?->tenant;

        if (! $tenantModel) {
            return back()->with('error', 'No tenant mapping found for this organization.');
        }

        $slug = (string) ($tenantModel->slug ?: $tenantModel->id);

        if ($requestedTenant !== $slug && $requestedTenant !== (string) $tenantModel->id) {
            abort(403);
        }

        $prefill = PanelLoginPrefillStore::forUser($targetUser);
        $payload = [
            'tenant' => $slug,
            'email' => $prefill['email'],
            'password' => $prefill['password'],
            'issued_at' => now()->getTimestamp(),
            'nonce' => Str::uuid()->toString(),
        ];

        $token = Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
        $roleSegment = $this->roleLoginSegment((string) $targetUser->role);

        return redirect("/{$slug}/{$roleSegment}/login?" . http_build_query([
            'oa_prefill' => $token,
        ]));
    }

    private function roleLoginSegment(string $role): string
    {
        return match ($role) {
            UserRole::MANUFACTURER => 'manufacturer',
            UserRole::DISTRIBUTOR => 'distributor',
            UserRole::VENDOR => 'vendor',
            UserRole::CONSUMER => 'consumer',
            UserRole::ORG_ADMIN => 'organization-admin',
            default => 'organization-admin',
        };
    }
}
