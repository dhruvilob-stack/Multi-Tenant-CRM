<?php

namespace App\Http;

use App\Http\Middleware\ApplyUserLocale;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustHosts;
use App\Http\Middleware\TrustProxies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middlewarePriority = [
        \App\Http\Middleware\SetPanelSessionCookie::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \App\Http\Middleware\InitializeTenancy::class,
        \App\Http\Middleware\SetTenantUrlDefaults::class,
        \Illuminate\Auth\Middleware\Authenticate::class,
    ];

    protected $middleware = [
        TrustHosts::class,
        TrustProxies::class,
        \App\Http\Middleware\SetPanelSessionCookie::class,
        \Fruitcake\Cors\HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        // rewritten paths depend on knowing the current tenant user, so the
        // middleware must run *after* the session is started. it previously lived
        // in the global stack which executed before StartSession, causing our
        // role‑based skip logic to never fire. we now move it to the web group
        // further down (see below).
        ApplyUserLocale::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \App\Http\Middleware\SetPanelSessionCookie::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // Ensure tenant connection is ready before any role-based rewrites
            // or auth checks run.
            \App\Http\Middleware\InitializeTenancy::class,
            // path rewriting must run once the session/auth are ready
            \App\Http\Middleware\RewriteRolePrefixedTenantPath::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
    ];
}
