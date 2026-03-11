<?php

namespace App\Http\Middleware;

use App\Support\UserRole;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectTenantRootToRoleHome
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ((bool) $request->attributes->get('role_dashboard_alias', false) === true) {
            return $next($request);
        }

        if ((string) $request->query('role_dashboard_alias', '') === '1') {
            return $next($request);
        }

        if ((bool) $request->attributes->get('role_prefixed_rewrite', false) === true) {
            return $next($request);
        }

        if (! $request->isMethod('get')) {
            return $next($request);
        }

        $user = auth('tenant')->user();
        if (! $user) {
            return $next($request);
        }

        $tenant = (string) ($request->route('tenant') ?? $request->session()->get('tenant_slug') ?? '');
        if ($tenant === '') {
            return $next($request);
        }

        $path = trim((string) $request->path(), '/');
        if ($path !== $tenant) {
            return $next($request);
        }

        $target = match ((string) $user->role) {
            UserRole::MANUFACTURER => "/{$tenant}/manufacturer",
            UserRole::DISTRIBUTOR => "/{$tenant}/distributor",
            UserRole::VENDOR => "/{$tenant}/vendor",
            UserRole::CONSUMER => "/{$tenant}/consumer",
            default => null,
        };

        if (! $target) {
            return $next($request);
        }

        return redirect($target);
    }
}
