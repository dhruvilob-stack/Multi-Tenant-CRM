<?php

namespace App\Http\Middleware;

use App\Support\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RewriteRolePrefixedTenantPath
{
    /**
     * Shared tenant sections that should work behind role prefixes:
     * /{tenant}/{role}/{section}/...
     */
    private const FORWARDABLE_SECTIONS = [
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
        'manufacturer',
        'distributor',
        'vendor',
        'consumer',
    ];

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $path = trim((string) $request->path(), '/');
        if ($path === '') {
            return $next($request);
        }

        $segments = explode('/', $path);
        if (count($segments) < 2) {
            return $next($request);
        }

        $roleAlias = strtolower((string) ($segments[1] ?? ''));
        if (! in_array($roleAlias, ['manufacturer', 'distributor', 'vendor', 'consumer'], true)) {
            return $next($request);
        }

        $user = auth('tenant')->user();
        if ($user && in_array((string) $user->role, [UserRole::ORG_ADMIN, UserRole::SUPER_ADMIN], true)) {
            return $next($request);
        }

        $section = strtolower((string) ($segments[2] ?? ''));
        if (! in_array($section, self::FORWARDABLE_SECTIONS, true)) {
            return $next($request);
        }

        return $this->rewritePath($request, '/'.(string) $segments[0].'/'.implode('/', array_slice($segments, 2)), $next);
    }

    private function rewritePath(Request $request, string $rewrittenPath, Closure $next): Response
    {
        $queryString = (string) $request->getQueryString();
        $server = $request->server->all();
        $server['REQUEST_URI'] = $rewrittenPath.($queryString !== '' ? '?'.$queryString : '');
        $server['PATH_INFO'] = $rewrittenPath;

        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $server,
            $request->getContent()
        );
        $request->attributes->set('role_prefixed_rewrite', true);

        return $next($request);
    }
}
