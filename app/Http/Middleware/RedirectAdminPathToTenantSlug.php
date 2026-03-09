<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminPathToTenantSlug
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ($request->headers->get('X-Tenant-Alias') === '1') {
            return $next($request);
        }

        if (! ($request->is('admin') || $request->is('admin/*'))) {
            return $next($request);
        }

        $tenantId = $request->session()->get('tenant_id');
        if (! $tenantId) {
            return $next($request);
        }

        $tenant = Tenant::query()->find($tenantId);
        if (! $tenant || blank($tenant->slug)) {
            return $next($request);
        }

        $adminPath = ltrim((string) $request->path(), '/');
        $suffix = (string) preg_replace('/^admin\/?/', '', $adminPath);

        $target = '/' . $tenant->slug . '/' . ($suffix === '' ? 'dashboard' : $suffix);

        return redirect($target);
    }
}
