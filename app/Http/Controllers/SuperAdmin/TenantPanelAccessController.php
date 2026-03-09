<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
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

        $tenant = $organization->tenant;

        if (! $tenant) {
            return back()->with('error', 'No tenant mapping found for this organization.');
        }

        $slug = $tenant->slug ?: $tenant->id;

        return redirect("/{$slug}/login");
    }
}
