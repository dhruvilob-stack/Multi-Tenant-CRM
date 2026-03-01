<?php

namespace App\Support;

final class UserRole
{
    public const SUPER_ADMIN = 'super_admin';
    public const ORG_ADMIN = 'org_admin';
    public const MANUFACTURER = 'manufacturer';
    public const DISTRIBUTOR = 'distributor';
    public const VENDOR = 'vendor';
    public const CONSUMER = 'consumer';

    public static function all(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ORG_ADMIN,
            self::MANUFACTURER,
            self::DISTRIBUTOR,
            self::VENDOR,
            self::CONSUMER,
        ];
    }
}
