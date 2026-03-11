<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Http\Request;

class FilamentTenantAuthenticate extends FilamentAuthenticate
{
    protected function redirectTo($request): ?string
    {
        $tenant = (string) ($request->route('tenant') ?? $request->session()->get('tenant_slug') ?? '');
        if ($tenant !== '') {
            return url('/' . $tenant . '/login');
        }

        return url('/super-admin/login');
    }
}
