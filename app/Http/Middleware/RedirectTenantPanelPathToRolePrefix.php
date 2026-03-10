<?php

namespace App\Http\Middleware;

use App\Support\UserRole;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectTenantPanelPathToRolePrefix
{
    private const ROLE_CANONICAL_SECTIONS = [
        'dashboard',
        'mail',
        'profile',
        'reports',
        'settings',
        'shop',
        'inventory',
        'invoices',
        'orders',
        'products',
        'quotations',
        'users',
        'wallets',
        'organizations',
        'commissions',
        'commission-ledger',
        'commission-payouts',
    ];

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (! $request->isMethod('get')) {
            return $next($request);
        }

        // Requests internally forwarded from role-prefixed aliases
        // should not be redirected back again.
        if ((bool) $request->attributes->get('role_alias_forward', false) === true) {
            return $next($request);
        }

        if ((bool) $request->attributes->get('role_prefixed_rewrite', false) === true) {
            return $next($request);
        }

        if ((bool) $request->attributes->get('role_dashboard_alias', false) === true) {
            return $next($request);
        }

        $user = auth('tenant')->user();
        if (! $user) {
            return $next($request);
        }

        $rolePrefix = match ((string) $user->role) {
            UserRole::MANUFACTURER => 'manufacturer',
            UserRole::DISTRIBUTOR => 'distributor',
            UserRole::VENDOR => 'vendor',
            UserRole::CONSUMER => 'consumer',
            default => null,
        };

        if ($rolePrefix === null) {
            return $next($request);
        }

        $tenant = (string) ($request->route('tenant') ?? $request->session()->get('tenant_slug') ?? '');
        if ($tenant === '') {
            return $next($request);
        }

        $path = trim((string) $request->path(), '/');
        $tenantPrefix = trim($tenant, '/').'/';

        if (! str_starts_with($path, $tenantPrefix)) {
            return $next($request);
        }

        $relative = ltrim(substr($path, strlen($tenantPrefix)), '/');
        if ($relative === '') {
            return $next($request);
        }

        // Manufacturer users should not access org-admin manufacturers listing.
        if ((string) $user->role === UserRole::MANUFACTURER && preg_match('#^manufacturers(/|$)#', $relative) === 1) {
            $target = "/{$tenant}/manufacturer";
            if ($request->getQueryString()) {
                $target .= '?'.$request->getQueryString();
            }

            return redirect($target);
        }

        if (preg_match('#^(manufacturer|distributor|vendor|consumer)(/|$)#', $relative) === 1) {
            return $next($request);
        }

        $section = strtolower((string) strtok($relative, '/'));
        if (! in_array($section, self::ROLE_CANONICAL_SECTIONS, true)) {
            return $next($request);
        }

        $target = "/{$tenant}/{$rolePrefix}/{$relative}";
        if ($request->getQueryString()) {
            $target .= '?'.$request->getQueryString();
        }

        return redirect($target);
    }
}
