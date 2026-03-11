<?php

namespace App\Support;

use Illuminate\Support\Str;

final class OrganizationEmailFormatter
{
    public static function normalizeDomain(?string $emailOrDomain): string
    {
        $value = strtolower(trim((string) $emailOrDomain));
        if ($value === '') {
            return 'example.com';
        }

        if (str_contains($value, '@')) {
            $value = substr(strrchr($value, '@') ?: '', 1);
        }

        $value = trim($value, '.');

        return $value !== '' ? $value : 'example.com';
    }

    public static function suggestEmail(string $name, string $role, string $domain): string
    {
        $domain = self::normalizeDomain($domain);
        $roleToken = strtolower(trim($role));
        $roleToken = preg_replace('/[^a-z0-9]/', '', $roleToken) ?: 'user';

        $first = strtolower(trim(strtok($name, ' ') ?: 'user'));
        $first = preg_replace('/[^a-z0-9]/', '', $first) ?: 'user';

        return sprintf('%s.%s@%s', $first, $roleToken, $domain);
    }
}
