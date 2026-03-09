<?php

namespace App\Http\Middleware;

use App\Services\TenantDatabaseManager;
use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancy
{
    public function __construct(
        private readonly TenantResolver $tenantResolver,
        private readonly TenantDatabaseManager $tenantDatabaseManager,
    ) {
    }

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ($this->isLandlordRequest($request)) {
            $this->tenantDatabaseManager->activateLandlordConnection();

            return $next($request);
        }

        $tenant = $this->tenantResolver->resolveFromRequest($request);

        if ($tenant) {
            $request->session()->put('tenant_id', $tenant->id);
            $request->session()->put('tenant_slug', $tenant->slug ?: $tenant->id);
            $this->tenantDatabaseManager->activateTenantConnection($tenant);

            return $next($request);
        }

        if ($this->isTenantPanelRequest($request)) {
            $this->tenantDatabaseManager->activateLandlordConnection();

            $requestedTenant = (string) ($request->route('tenant') ?? '');
            if ($this->isReservedTenantSlug($requestedTenant)) {
                return redirect('/super-admin/login');
            }

            if ($requestedTenant !== '' && ! str_contains($requestedTenant, '{') && ! str_contains($requestedTenant, '}')) {
                abort(404, 'Tenant not found.');
            }

            return redirect('/super-admin/login');
        }

        $this->tenantDatabaseManager->activateLandlordConnection();

        return $next($request);
    }

    private function isLandlordRequest(Request $request): bool
    {
        $routeName = (string) ($request->route()?->getName() ?? '');

        if (str_starts_with($routeName, 'filament.super-admin.')) {
            return true;
        }

        if ($request->is('super-admin') || $request->is('super-admin/*')) {
            return true;
        }

        return $this->isLivewireForLandlord($request);
    }

    private function isTenantPanelRequest(Request $request): bool
    {
        $routeName = (string) ($request->route()?->getName() ?? '');

        return str_starts_with($routeName, 'filament.admin.') || filled($request->route('tenant'));
    }

    private function isLivewireForLandlord(Request $request): bool
    {
        if (! $request->is('livewire*')) {
            return false;
        }

        $referer = (string) $request->headers->get('referer', '');
        $path = trim((string) parse_url($referer, PHP_URL_PATH), '/');

        return str_starts_with($path, 'super-admin') || str_starts_with($path, 'platform');
    }

    private function isReservedTenantSlug(string $slug): bool
    {
        $slug = strtolower(trim($slug));

        if ($slug === '' || str_contains($slug, '{') || str_contains($slug, '}')) {
            return true;
        }

        return in_array($slug, [
            'super-admin',
            'platform',
            'login',
            'logout',
            'livewire',
            'filament',
            'up',
        ], true);
    }
}
