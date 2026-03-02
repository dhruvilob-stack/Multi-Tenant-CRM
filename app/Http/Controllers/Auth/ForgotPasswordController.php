<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function show()
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($data);

        return back()->with('status', 'If your account exists, a reset link has been sent.');
    }
}
