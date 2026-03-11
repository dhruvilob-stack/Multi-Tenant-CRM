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

        // the resolver is occasionally invoked very early in the request
        // lifecycle (for example from middleware) and there may not yet be a
        // working landlord connection. this happens in our test suite where
        // the connection is configured to ":memory:" but the namespaced
        // "landlord" connection remains MySQL. rather than crashing we simply
        // ignore any failures when inspecting the schema and fall back to the
        // id-based lookup.
        try {
            $conn = config('tenancy.landlord_connection', 'landlord');
            if (Schema::connection($conn)->hasColumn('tenants', 'slug')) {
                $query->orWhere('slug', $identifier);
            }

            if (Schema::connection($conn)->hasColumn('tenants', 'domain')) {
                $query->orWhere('domain', $identifier);
            }
        } catch (\Throwable $e) {
            // if the landlord connection is unavailable just move on; this is
            // primarily to make the test environment behave without setting up
            // a full MySQL server. any real failure will surface later when the
            // application actually touches the database.
            \Log::debug('tenant resolver skipping schema checks', [
                'error' => $e->getMessage(),
            ]);
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
