<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPanelLoginToUniversalLogin
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ($request->isMethod('GET') && ($request->is('admin/login') || $request->is('super-admin/login'))) {
            return redirect('/');
        }

        return $next($request);
    }
}
