<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class TenantPanelAccessController extends Controller
{
    public function openAdmin(Organization $organization): RedirectResponse
    {
        $superAdmin = Auth::user();

        if (! $superAdmin || $superAdmin->role !== UserRole::SUPER_ADMIN) {
            abort(403);
        }

        $orgAdmin = User::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('role', UserRole::ORG_ADMIN)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        if (! $orgAdmin) {
            return back()->with('error', 'No active organization admin found for this tenant.');
        }

        Auth::login($orgAdmin);
        request()->session()->regenerate();

        return redirect('/admin');
    }
}

