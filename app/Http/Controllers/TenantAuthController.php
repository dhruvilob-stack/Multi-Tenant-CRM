<?php

namespace App\Http\Controllers;

use App\Services\TenantResolver;
use App\Support\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantAuthController extends Controller
{
    public function showLogin(string $tenant, Request $request, TenantResolver $resolver): Response
    {
        $this->bootstrapTenantContext($tenant, $request, $resolver);
        $request->session()->forget('tenant_expected_role');
        $request->session()->put('url.intended', "/{$tenant}/dashboard");

        return $this->forwardToAdmin($request, '/admin/login');
    }

    public function login(string $tenant, Request $request, TenantResolver $resolver): Response
    {
        $this->bootstrapTenantContext($tenant, $request, $resolver);
        $request->session()->forget('tenant_expected_role');
        $request->session()->put('url.intended', "/{$tenant}/dashboard");

        return $this->forwardToAdmin($request, '/admin/login');
    }

    public function dashboard(string $tenant, Request $request, TenantResolver $resolver): Response|RedirectResponse
    {
        $this->bootstrapTenantContext($tenant, $request, $resolver);

        if (! Auth::guard('tenant')->check()) {
            return redirect("/{$tenant}/login");
        }

        return $this->forwardToAdmin($request, '/admin');
    }

    public function showRoleLogin(string $tenant, string $role, Request $request, TenantResolver $resolver): Response
    {
        $this->bootstrapTenantContext($tenant, $request, $resolver);

        $normalizedRole = $this->normalizeRole($role);
        abort_if($normalizedRole === null, 404, 'Invalid role.');
        $request->session()->put('tenant_expected_role', $normalizedRole);
        $request->session()->put('url.intended', "/{$tenant}/{$normalizedRole}/dashboard");

        return $this->forwardToAdmin($request, '/admin/login');
    }

    public function roleLogin(string $tenant, string $role, Request $request, TenantResolver $resolver): Response
    {
        $this->bootstrapTenantContext($tenant, $request, $resolver);

        $normalizedRole = $this->normalizeRole($role);
        abort_if($normalizedRole === null, 404, 'Invalid role.');
        $request->session()->put('tenant_expected_role', $normalizedRole);
        $request->session()->put('url.intended', "/{$tenant}/{$normalizedRole}/dashboard");

        return $this->forwardToAdmin($request, '/admin/login');
    }

    public function roleDashboard(string $tenant, string $role, Request $request, TenantResolver $resolver): Response|RedirectResponse
    {
        $this->bootstrapTenantContext($tenant, $request, $resolver);

        $normalizedRole = $this->normalizeRole($role);
        abort_if($normalizedRole === null, 404, 'Invalid role.');

        if (! Auth::guard('tenant')->check()) {
            return redirect("/{$tenant}/{$normalizedRole}/login");
        }

        $user = Auth::guard('tenant')->user();
        if ($user?->role !== $normalizedRole) {
            Auth::guard('tenant')->logout();

            return redirect("/{$tenant}/{$normalizedRole}/login")
                ->withErrors(['email' => 'Role mismatch for this login URL.']);
        }

        return $this->forwardToAdmin($request, '/admin');
    }

    public function panel(string $tenant, string $path, Request $request, TenantResolver $resolver): Response|RedirectResponse
    {
        $this->bootstrapTenantContext($tenant, $request, $resolver);

        if (! Auth::guard('tenant')->check()) {
            return redirect("/{$tenant}/login");
        }

        $path = trim($path);
        if ($path === '' || $path === '/') {
            return $this->forwardToAdmin($request, '/admin');
        }

        return $this->forwardToAdmin($request, '/admin/' . ltrim($path, '/'));
    }

    private function bootstrapTenantContext(string $tenant, Request $request, TenantResolver $resolver): void
    {
        $resolvedTenant = $resolver->resolveByIdentifier($tenant);
        abort_unless($resolvedTenant, 404, 'Tenant not found.');

        $request->session()->put('tenant_id', $resolvedTenant->id);
    }

    private function forwardToAdmin(Request $request, string $uri): Response
    {
        $server = $request->server->all();
        $server['REQUEST_URI'] = $uri;
        $server['PATH_INFO'] = $uri;
        $server['HTTP_X_TENANT_ALIAS'] = '1';

        $subRequest = Request::create(
            $uri,
            $request->method(),
            $request->all(),
            $request->cookies->all(),
            $request->files->all(),
            $server,
            $request->getContent()
        );

        $subRequest->setLaravelSession($request->session());
        $subRequest->headers->set('X-Tenant-Alias', '1');

        return app()->handle($subRequest);
    }

    private function normalizeRole(string $role): ?string
    {
        $role = strtolower(trim($role));
        if ($role === 'organization-admin') {
            $role = UserRole::ORG_ADMIN;
        }

        $allowed = [
            UserRole::ORG_ADMIN,
            UserRole::MANUFACTURER,
            UserRole::DISTRIBUTOR,
            UserRole::VENDOR,
            UserRole::CONSUMER,
        ];

        return in_array($role, $allowed, true) ? $role : null;
    }
}
