<?php

namespace App\Http\Middleware;

use App\Support\UserRole;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminPanelGuardMismatch
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ($request->headers->get('X-Tenant-Alias') === '1') {
            return $next($request);
        }

        if (! ($request->is('admin') || $request->is('admin/*'))) {
            return $next($request);
        }

        // Super admin session should stay on super-admin panel.
        if (Auth::guard('super_admin')->check() && ! Auth::guard('tenant')->check()) {
            return redirect('/super-admin');
        }

        // Never allow super_admin role inside tenant panel.
        if (Auth::guard('tenant')->check() && Auth::guard('tenant')->user()?->role === UserRole::SUPER_ADMIN) {
            Auth::guard('tenant')->logout();

            return redirect('/platform/login');
        }

        return $next($request);
    }
}
