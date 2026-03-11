<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Support\TenantUserMirror;

class ProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->back();
        }

        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $first = trim((string) ($data['first_name'] ?? ''));
        $last = trim((string) ($data['last_name'] ?? ''));
        $name = trim($first.' '.$last);

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')?->store('profile-photos', 'public');
            if ($path) {
                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }
                $user->profile_photo = $path;
            }
        }

        $user->first_name = $first !== '' ? $first : null;
        $user->last_name = $last !== '' ? $last : null;
        if ($name !== '') {
            $user->name = $name;
        }

        $user->save();
        TenantUserMirror::syncToLandlord($user);

        return redirect()->back()->with('status', 'Profile updated successfully.');
    }
}
