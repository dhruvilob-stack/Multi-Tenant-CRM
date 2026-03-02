<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileLocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $supportedLocales = array_keys(config('localization.supported', []));

        $request->validate([
            'locale' => ['required', 'string', Rule::in($supportedLocales)],
        ]);

        $locale = $request->input('locale');
        $user = Auth::user();

        if ($user) {
            $user->update(['locale' => $locale]);
        }

        return redirect()->back()->with('status', __('profile.locale_updated'));
    }
}
