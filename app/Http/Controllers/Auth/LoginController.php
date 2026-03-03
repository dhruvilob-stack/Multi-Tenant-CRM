<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect($this->resolveDashboardPath(Auth::user()?->role));
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $target = $this->resolveDashboardPath(Auth::user()?->role);

        return redirect()->intended($target);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function resolveDashboardPath(?string $role): string
    {
        return $role === UserRole::SUPER_ADMIN ? '/super-admin' : '/admin';
    }
}
