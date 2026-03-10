<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

final class PanelLoginPrefillStore
{
    /**
     * @return array{email: string, password: string}
     */
    public static function forUser(User $user): array
    {
        $organization = Organization::query()->find((int) $user->organization_id);

        if (! $organization) {
            return [
                'email' => (string) $user->email,
                'password' => '',
            ];
        }

        $settings = (array) ($organization->settings ?? []);
        $rows = (array) data_get($settings, 'panel_login_prefills.users', []);
        $row = (array) ($rows[(string) $user->id] ?? []);
        $encryptedPassword = (string) ($row['password_encrypted'] ?? '');
        $password = '';

        if ($encryptedPassword !== '') {
            try {
                $password = Crypt::decryptString($encryptedPassword);
            } catch (\Throwable) {
                $password = '';
            }
        }

        return [
            'email' => (string) ($row['email'] ?? $user->email),
            'password' => $password,
        ];
    }

    public static function saveForUser(User $user, string $plainPassword): void
    {
        $plainPassword = trim($plainPassword);

        if ($plainPassword === '') {
            return;
        }

        $organization = Organization::query()->find((int) $user->organization_id);

        if (! $organization) {
            return;
        }

        $settings = (array) ($organization->settings ?? []);
        $rows = (array) data_get($settings, 'panel_login_prefills.users', []);
        $rows[(string) $user->id] = [
            'email' => (string) $user->email,
            'password_encrypted' => Crypt::encryptString($plainPassword),
            'updated_at' => now()->toIso8601String(),
        ];

        data_set($settings, 'panel_login_prefills.users', $rows);
        $organization->forceFill(['settings' => $settings])->saveQuietly();
    }
}
