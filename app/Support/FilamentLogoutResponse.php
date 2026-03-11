<?php

namespace App\Support;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class FilamentLogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $panel = Filament::getCurrentPanel();
        $panelId = $panel?->getId();

        if ($panelId === 'super-admin' || $request->is('super-admin') || $request->is('super-admin/*')) {
            return redirect('/super-admin/login');
        }

        $tenant = (string) ($request->route('tenant')
            ?? $request->session()->get('tenant_slug')
            ?? '');

        if ($tenant !== '') {
            return redirect('/'.$tenant.'/login');
        }

        return redirect('/super-admin/login');
    }
}
