<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantResolver
{
    public function resolveFromRequest(Request $request): ?Tenant
    {
        $identifier = $this->resolveIdentifier($request);

        if (blank($identifier)) {
            return null;
        }

        return $this->resolveByIdentifier((string) $identifier);
    }

    public function resolveByIdentifier(string $identifier): ?Tenant
    {
        $identifier = trim(Str::lower($identifier));
        if ($identifier === '') {
            return null;
        }

        $query = Tenant::query()->where('id', $identifier);

        if (Schema::connection(config('tenancy.landlord_connection', 'landlord'))->hasColumn('tenants', 'slug')) {
            $query->orWhere('slug', $identifier);
        }

        if (Schema::connection(config('tenancy.landlord_connection', 'landlord'))->hasColumn('tenants', 'domain')) {
            $query->orWhere('domain', $identifier);
        }

        return $query->first();
    }

    private function resolveIdentifier(Request $request): ?string
    {
        $routeTenant = $request->route('tenant');
        if ($routeTenant !== null) {
            return (string) $routeTenant;
        }

        if ($request->has('tenant')) {
            return (string) $request->query('tenant');
        }

        $sessionTenant = $request->session()->get('tenant_id');
        if ($sessionTenant) {
            return (string) $sessionTenant;
        }

        $sessionTenantSlug = $request->session()->get('tenant_slug');
        if ($sessionTenantSlug) {
            return (string) $sessionTenantSlug;
        }

        if ($request->is('livewire*')) {
            $referer = (string) $request->headers->get('referer', '');
            $path = trim((string) parse_url($referer, PHP_URL_PATH), '/');
            $firstSegment = $path !== '' ? explode('/', $path)[0] : null;

            if ($firstSegment && ! in_array($firstSegment, ['super-admin', 'platform', 'login'], true)) {
                return (string) $firstSegment;
            }
        }

        $host = Str::lower((string) $request->getHost());
        if ($host === '' || in_array($host, ['127.0.0.1', 'localhost'], true)) {
            return null;
        }

        if (preg_match('/^([a-z0-9-]+)\.127\.0\.0\.1$/', $host, $matches) === 1) {
            return $matches[1];
        }

        $baseDomain = Str::lower((string) config('tenancy.base_domain', ''));
        if ($baseDomain !== '' && Str::endsWith($host, $baseDomain)) {
            $subdomain = Str::before($host, '.' . $baseDomain);
            if ($subdomain !== '' && ! str_contains($subdomain, '.')) {
                return $subdomain;
            }
        }

        return $host;
    }
}
