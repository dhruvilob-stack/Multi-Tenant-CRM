<?php

namespace App\Http\Controllers;

use App\Support\UserRole;
use App\Services\TenantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantEntryController extends Controller
{
    public function login(string $tenant, Request $request, TenantResolver $resolver): RedirectResponse
    {
        $resolvedTenant = $resolver->resolveByIdentifier($tenant);

        abort_unless($resolvedTenant, 404, 'Tenant not found.');

        $request->session()->put('tenant_id', $resolvedTenant->id);

        if (Auth::guard('tenant')->check()) {
            return redirect('/admin');
        }

        $request->session()->put('url.intended', '/admin');

        return redirect('/admin/login');
    }

    public function dashboard(string $tenant, Request $request, TenantResolver $resolver): RedirectResponse
    {
        $resolvedTenant = $resolver->resolveByIdentifier($tenant);

        abort_unless($resolvedTenant, 404, 'Tenant not found.');

        $request->session()->put('tenant_id', $resolvedTenant->id);

        if (! Auth::guard('tenant')->check()) {
            $request->session()->put('url.intended', '/admin');

            return redirect('/admin/login');
        }

        return redirect('/admin');
    }

    public function roleDashboard(string $tenant, string $role, Request $request, TenantResolver $resolver): RedirectResponse
    {
        $resolvedTenant = $resolver->resolveByIdentifier($tenant);
        abort_unless($resolvedTenant, 404, 'Tenant not found.');

        if (! Auth::guard('tenant')->check()) {
            $request->session()->put('tenant_id', $resolvedTenant->id);
            $request->session()->put('url.intended', '/t/' . $tenant . '/' . $role . '/dashboard');

            return redirect('/admin/login');
        }

        $normalized = match ($role) {
            'organization-admin' => UserRole::ORG_ADMIN,
            default => $role,
        };

        $allowed = [
            UserRole::ORG_ADMIN,
            UserRole::MANUFACTURER,
            UserRole::DISTRIBUTOR,
            UserRole::VENDOR,
            UserRole::CONSUMER,
        ];

        if (! in_array($normalized, $allowed, true)) {
            abort(404);
        }

        $user = Auth::guard('tenant')->user();
        if ($user->role !== $normalized) {
            return redirect('/admin')->with('error', 'Role dashboard mismatch for this user.');
        }

        $request->session()->put('tenant_id', $resolvedTenant->id);

        return redirect('/admin');
    }
}
