<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class ApplyUserLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supportedLocales = array_keys(Config::get('localization.supported', []));
        $fallbackLocale = Config::get('localization.fallback', config('app.fallback_locale', 'en'));

        $sessionLocale = (string) $request->session()->get('locale', '');
        $preferredLocale = (string) ($request->user()?->locale ?: $sessionLocale ?: config('app.locale'));
        $locale = in_array($preferredLocale, $supportedLocales, true) ? $preferredLocale : $fallbackLocale;

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
