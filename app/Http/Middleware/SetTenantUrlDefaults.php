<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetTenantUrlDefaults
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = (string) ($request->route()?->getName() ?? '');
        if (str_starts_with($routeName, 'filament.super-admin.')) {
            return $next($request);
        }

        if ($request->is('super-admin') || $request->is('super-admin/*') || $request->is('platform') || $request->is('platform/*')) {
            return $next($request);
        }

        if ($request->is('livewire*')) {
            $referer = (string) $request->headers->get('referer', '');
            $path = trim((string) parse_url($referer, PHP_URL_PATH), '/');
            if (str_starts_with($path, 'super-admin') || str_starts_with($path, 'platform')) {
                return $next($request);
            }
        }

        $tenant = $request->route('tenant');

        if (! is_string($tenant) || $tenant === '' || str_contains($tenant, '{') || str_contains($tenant, '}')) {
            $tenant = $request->session()->get('tenant_slug');
        }

        if ((! is_string($tenant) || $tenant === '') && $request->session()->has('tenant_id')) {
            $tenantModel = Tenant::query()->find($request->session()->get('tenant_id'));
            $tenant = $tenantModel?->slug ?: $tenantModel?->id;
        }

        if (filled($tenant)) {
            URL::defaults(['tenant' => (string) $tenant]);
        }

        return $next($request);
    }
}
