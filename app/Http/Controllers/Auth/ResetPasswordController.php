<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function show(string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => request('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)]);
        }

        return redirect('/login')->with('status', __($status));
    }
}
