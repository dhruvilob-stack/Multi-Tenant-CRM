<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class RedirectPanelLoginToUniversalLogin
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ($request->isMethod('get') && $request->is('super-admin/login')) {
            if (auth('super_admin')->check()) {
                return redirect('/super-admin');
            }

            return response()->view('auth.super-admin-login');
        }

        if ($request->isMethod('get') && $this->isTenantLoginPath($request)) {
            $tenant = $this->tenantFromPath($request);
            $prefill = $this->resolveSuperAdminPrefill($request, $tenant);

            if (auth('tenant')->check()) {
                return redirect('/' . $tenant);
            }

            return response()->view('auth.tenant-login', [
                'tenant' => $tenant,
                'role' => null,
                'action' => url('/' . $tenant . '/login'),
                'prefillEmail' => $prefill['email'],
                'prefillPassword' => $prefill['password'],
            ]);
        }

        return $next($request);
    }

    private function isTenantLoginPath(Request $request): bool
    {
        $path = trim($request->path(), '/');
        $segments = $path === '' ? [] : explode('/', $path);

        if (count($segments) !== 2 || $segments[1] !== 'login') {
            return false;
        }

        return $this->isTenantSlug($segments[0]);
    }

    private function tenantFromPath(Request $request): string
    {
        $path = trim($request->path(), '/');
        $segments = $path === '' ? [] : explode('/', $path);

        return (string) ($segments[0] ?? '');
    }

    private function isTenantSlug(string $slug): bool
    {
        if ($slug === '') {
            return false;
        }

        if (in_array($slug, ['super-admin', 'platform', 'login', 'logout', 'livewire', 'filament', 'up'], true)) {
            return false;
        }

        return preg_match('/^[a-z0-9][a-z0-9\\-]*$/i', $slug) === 1;
    }

    /**
     * @return array{email: string, password: string}
     */
    private function resolveSuperAdminPrefill(Request $request, string $tenant): array
    {
        $token = trim((string) $request->query('sa_prefill', ''));
        if ($token === '') {
            return ['email' => '', 'password' => ''];
        }

        try {
            $raw = Crypt::decryptString($token);
            $payload = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return ['email' => '', 'password' => ''];
        }

        $issuedAt = (int) ($payload['issued_at'] ?? 0);
        if (($payload['tenant'] ?? '') !== $tenant || $issuedAt <= 0 || (time() - $issuedAt) > 300) {
            return ['email' => '', 'password' => ''];
        }

        return [
            'email' => (string) ($payload['email'] ?? ''),
            'password' => (string) ($payload['password'] ?? ''),
        ];
    }
}
