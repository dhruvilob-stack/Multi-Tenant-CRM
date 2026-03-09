<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TenantPanelAccessController extends Controller
{
    public function openAdmin(Organization $organization): RedirectResponse
    {
        $superAdmin = Auth::guard('super_admin')->user();

        if (! $superAdmin || $superAdmin->role !== UserRole::SUPER_ADMIN) {
            abort(403);
        }

        $tenant = $organization->tenant;

        if (! $tenant) {
            return back()->with('error', 'No tenant mapping found for this organization.');
        }

        $slug = $tenant->slug ?: $tenant->id;
        $orgAdmin = User::query()
            ->where('organization_id', (int) $organization->id)
            ->where('role', UserRole::ORG_ADMIN)
            ->orderByDesc('id')
            ->first();

        $encryptedPassword = (string) data_get($tenant->data ?? [], 'login_password_encrypted', '');
        $prefillPassword = '';

        if ($encryptedPassword !== '') {
            try {
                $prefillPassword = Crypt::decryptString($encryptedPassword);
            } catch (\Throwable) {
                $prefillPassword = '';
            }
        }

        $payload = [
            'tenant' => (string) $slug,
            'email' => (string) ($orgAdmin?->email ?: $organization->email),
            'password' => $prefillPassword,
            'issued_at' => now()->getTimestamp(),
            'nonce' => Str::uuid()->toString(),
        ];

        $token = Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));

        return redirect("/{$slug}/login?" . http_build_query([
            'sa_prefill' => $token,
        ]));
    }
}
