<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class TenantUserMirror
{
    public static function syncToLandlord(User $user): void
    {
        $landlord = config('tenancy.landlord_connection', 'landlord');

        DB::connection($landlord)->table('users')->updateOrInsert(
            ['email' => (string) $user->email],
            [
                'organization_id' => $user->organization_id,
                'parent_id' => $user->parent_id,
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password,
                'role' => $user->role,
                'invitation_token' => $user->invitation_token,
                'invitation_accepted_at' => $user->invitation_accepted_at,
                'status' => $user->status,
                'locale' => $user->locale,
                'custom_role_id' => $user->custom_role_id,
                'email_verified_at' => $user->email_verified_at,
                'remember_token' => $user->remember_token,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
