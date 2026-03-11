<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

final class PanelLoginTokenService
{
    public static function make(string $tenant, string $email, string $password, int $ttlMinutes = 10080): string
    {
        $payload = [
            'tenant' => $tenant,
            'email' => $email,
            'password' => $password,
            'issued_at' => time(),
            'exp' => now()->addMinutes($ttlMinutes)->timestamp,
        ];

        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
