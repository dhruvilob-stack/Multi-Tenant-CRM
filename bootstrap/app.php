<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ApplyUserLocale;
use App\Http\Middleware\InitializeTenancy;
use App\Http\Middleware\RedirectPanelLoginToUniversalLogin;
use App\Http\Middleware\SetFilamentPanelFromReferer;
use App\Http\Middleware\SetPanelSessionCookie;
use App\Http\Middleware\SetTenantUrlDefaults;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            SetPanelSessionCookie::class,
        ]);

        $middleware->web(append: [
            SetFilamentPanelFromReferer::class,
            InitializeTenancy::class,
            SetTenantUrlDefaults::class,
            RedirectPanelLoginToUniversalLogin::class,
            ApplyUserLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
