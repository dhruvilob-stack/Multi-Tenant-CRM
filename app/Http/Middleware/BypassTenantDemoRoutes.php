<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class BypassTenantDemoRoutes
{
    /**
     * Redirect reserved slugs away from the tenant panel route.
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $slug = (string) ($request->route('tenant') ?? '');
        if ($slug === 'demo-dn-crm') {
            return response()->view('demo-dn-crm', [
                'superAdminUrl' => URL::to('/super-admin/login') . '?' . http_build_query([
                    'email' => 'superadmin@example.com',
                    'password' => 'password',
                ]),
                'tenantDemoUrl' => URL::to('/nebulonix/login') . '?' . http_build_query([
                    'email' => 'org@nebulonix.com',
                    'password' => 'password',
                ]),
            ]);
        }

        return $next($request);
    }
}
