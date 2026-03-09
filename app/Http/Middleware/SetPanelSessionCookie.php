<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetPanelSessionCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->resolveContext($request);

        if ($context === 'super-admin') {
            config()->set('session.cookie', 'mtcrm_super_admin_session');
        } elseif ($context !== '') {
            config()->set('session.cookie', 'mtcrm_tenant_' . $context . '_session');
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

