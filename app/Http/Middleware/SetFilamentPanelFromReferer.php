<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetFilamentPanelFromReferer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('livewire*')) {
            return $next($request);
        }

        $referer = (string) $request->headers->get('referer', '');
        $path = trim((string) parse_url($referer, PHP_URL_PATH), '/');

        if ($path === '') {
            return $next($request);
        }

        if (str_starts_with($path, 'super-admin') || str_starts_with($path, 'platform')) {
            Filament::setCurrentPanel('super-admin');
            Filament::bootCurrentPanel();

            return $next($request);
        }

        $first = explode('/', $path)[0] ?? '';
        $first = strtolower(trim($first));

        if ($first === '' || in_array($first, ['livewire', 'filament', 'login', 'up'], true)) {
            return $next($request);
        }

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();

        return $next($request);
    }
}

