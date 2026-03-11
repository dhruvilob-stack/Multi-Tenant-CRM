<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetPanelSessionCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->resolveContext($request);

        if ($context === 'super-admin') {
            config()->set('session.cookie', 'mtcrm_super_admin_session');
            Auth::shouldUse('super_admin');
        }
        // Keep sessions on landlord connection even when tenant DB is activated.
        config()->set('session.connection', config('tenancy.landlord_connection', 'landlord'));
        if ($context !== '' && $context !== 'super-admin') {
            Auth::shouldUse('tenant');
        }

        return $next($request);
    }

    private function resolveContext(Request $request): string
    {
        $tenantFromRoute = (string) ($request->route('tenant') ?? '');
        if ($this->isTenantSlug($tenantFromRoute)) {
            return Str::lower($tenantFromRoute);
        }

        $path = trim($request->path(), '/');
        $first = $path === '' ? '' : explode('/', $path)[0];

        if ($request->is('livewire*')) {
            $referer = (string) $request->headers->get('referer', '');
            $refererPath = trim((string) parse_url($referer, PHP_URL_PATH), '/');
            $first = $refererPath === '' ? '' : explode('/', $refererPath)[0];
        }

        $first = Str::lower((string) $first);

        if (in_array($first, ['super-admin', 'platform'], true)) {
            return 'super-admin';
        }

        if ($this->isTenantSlug($first)) {
            return $first;
        }

        // Fall back to referer for Filament background requests
        // that do not carry tenant slug in the URL.
        $referer = (string) $request->headers->get('referer', '');
        if ($referer !== '') {
            $refererPath = trim((string) parse_url($referer, PHP_URL_PATH), '/');
            $refererFirst = $refererPath === '' ? '' : explode('/', $refererPath)[0];
            $refererFirst = Str::lower((string) $refererFirst);

            if (in_array($refererFirst, ['super-admin', 'platform'], true)) {
                return 'super-admin';
            }

            if ($this->isTenantSlug($refererFirst)) {
                return $refererFirst;
            }
        }

        // If the path doesn't disclose context, fall back to the existing
        // session cookies so we don't accidentally switch cookie names
        // during background Filament requests.
        if ($request->cookies->has('mtcrm_super_admin_session')) {
            return 'super-admin';
        }

        if ($request->cookies->has('mtcrm_tenant_session')) {
            return 'tenant';
        }

        return '';
    }

    private function isTenantSlug(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (in_array($value, ['super-admin', 'platform', 'login', 'logout', 'livewire', 'filament', 'up'], true)) {
            return false;
        }

        return preg_match('/^[a-z0-9][a-z0-9\\-]*$/i', $value) === 1;
    }
}
