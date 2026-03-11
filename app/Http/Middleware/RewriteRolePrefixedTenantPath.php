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
        'manufacturers',
        'distributor',
        'distributors',
        'vendor',
        'vendors',
        'consumer',
        'consumers',
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


        // if the URL already includes the prefix matching the current user's
        // role we are already inside the proper panel; further rewriting would
        // only convert `/tenant/{role}/foo` into `/tenant/foo` and trigger a
        // redirect back, effectively locking navigation.
        $user = auth('tenant')->user();
        $rolePrefix = null;
        if ($user) {
            $rolePrefix = match ((string) $user->role) {
                \App\Support\UserRole::MANUFACTURER => 'manufacturer',
                \App\Support\UserRole::DISTRIBUTOR   => 'distributor',
                \App\Support\UserRole::VENDOR        => 'vendor',
                \App\Support\UserRole::CONSUMER      => 'consumer',
                default => null,
            };

            if ($rolePrefix !== null && isset($segments[1]) && $segments[1] === $rolePrefix) {
                \Log::debug('rewrite skipped – already in role prefix', [
                    'path' => $path,
                    'rolePrefix' => $rolePrefix,
                    'user_id' => $user->id,
                ]);

                return $next($request);
            }
        }

        // log any incoming request that will be rewritten so we can see what
        // happens during navigation
        \Log::debug('rewrite middleware processing', [
            'path' => $path,
            'segments' => $segments,
            'user_id' => $user?->id,
            'rolePrefix' => $rolePrefix,
        ]);

        if (count($segments) === 2) {
            return $this->rewritePath(
                $request,
                '/'.(string) $segments[0],
                $next,
                [
                    'role_dashboard_alias' => true,
                    'role_alias_forward' => true,
                ],
                ['role_dashboard_alias' => '1']
            );
        }

        $section = strtolower((string) ($segments[2] ?? ''));
        if (! in_array($section, self::FORWARDABLE_SECTIONS, true)) {
            return $next($request);
        }

        return $this->rewritePath(
            $request,
            '/'.(string) $segments[0].'/'.implode('/', array_slice($segments, 2)),
            $next,
            ['role_alias_forward' => true]
        );
    }

    private function rewritePath(
        Request $request,
        string $rewrittenPath,
        Closure $next,
        array $attributes = [],
        array $extraQuery = []
    ): Response
    {
        $session = $request->hasSession() ? $request->session() : null;
        $query = array_merge($request->query->all(), $extraQuery);
        $queryString = http_build_query($query);
        $server = $request->server->all();
        $server['REQUEST_URI'] = $rewrittenPath.($queryString !== '' ? '?'.$queryString : '');
        $server['PATH_INFO'] = $rewrittenPath;

        $request->initialize(
            $query,
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $server,
            $request->getContent()
        );
        if ($session) {
            $request->setLaravelSession($session);
        }
        $request->attributes->set('role_prefixed_rewrite', true);
        foreach ($attributes as $key => $value) {
            $request->attributes->set($key, $value);
        }

        return $next($request);
    }
}
