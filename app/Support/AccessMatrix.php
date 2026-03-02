<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class AccessMatrix
{
    public static function isSuper(?User $user): bool
    {
        return $user?->role === UserRole::SUPER_ADMIN;
    }

    public static function isOrgAdmin(?User $user): bool
    {
        return $user?->role === UserRole::ORG_ADMIN;
    }

    public static function scopeOrganization(Builder $query, ?User $user, string $column = 'organization_id'): Builder
    {
        if (! $user || self::isSuper($user)) {
            return $query;
        }

        return $query->where($column, $user->organization_id);
    }

    public static function allowedInviteRoles(?User $user): array
    {
        return match ($user?->role) {
            UserRole::ORG_ADMIN => [
                UserRole::MANUFACTURER => 'Manufacturer',
                UserRole::DISTRIBUTOR => 'Distributor',
                UserRole::VENDOR => 'Vendor',
                UserRole::CONSUMER => 'Consumer',
            ],
            UserRole::MANUFACTURER => [
                UserRole::DISTRIBUTOR => 'Distributor',
                UserRole::VENDOR => 'Vendor',
                UserRole::CONSUMER => 'Consumer',
            ],
            UserRole::DISTRIBUTOR => [
                UserRole::VENDOR => 'Vendor',
                UserRole::CONSUMER => 'Consumer',
            ],
            UserRole::VENDOR => [
                UserRole::CONSUMER => 'Consumer',
            ],
            default => [],
        };
    }

    public static function distributorIdsFor(User $user): array
    {
        return User::query()
            ->where('role', UserRole::DISTRIBUTOR)
            ->where('parent_id', $user->id)
            ->pluck('id')
            ->all();
    }

    public static function vendorIdsForManufacturer(User $user): array
    {
        $distributorIds = self::distributorIdsFor($user);

        if ($distributorIds === []) {
            return [];
        }

        return User::query()
            ->where('role', UserRole::VENDOR)
            ->whereIn('parent_id', $distributorIds)
            ->pluck('id')
            ->all();
    }

    public static function vendorIdsForDistributor(User $user): array
    {
        return User::query()
            ->where('role', UserRole::VENDOR)
            ->where('parent_id', $user->id)
            ->pluck('id')
            ->all();
    }

    public static function consumerIdsForVendor(User $user): array
    {
        return User::query()
            ->where('role', UserRole::CONSUMER)
            ->where('parent_id', $user->id)
            ->pluck('id')
            ->all();
    }

    public static function consumerIdsForDistributor(User $user): array
    {
        $vendorIds = self::vendorIdsForDistributor($user);

        if ($vendorIds === []) {
            return [];
        }

        return User::query()
            ->where('role', UserRole::CONSUMER)
            ->whereIn('parent_id', $vendorIds)
            ->pluck('id')
            ->all();
    }
}
