<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TenantUserDeletionService
{
    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\User>|array<int, \App\Models\User> $users
     */
    public function deleteMany(iterable $users): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                $this->deleteOne($user);
            }
        }
    }

    public function deleteOne(User $user): void
    {
        $landlord = config('tenancy.landlord_connection', 'landlord');
        $tenant = $this->resolveTenantForUser($user);

        if ($tenant instanceof Tenant) {
            $this->deleteFromTenant($tenant, $user);
        }

        $this->deleteFromLandlord($landlord, $user);
    }

    private function resolveTenantForUser(User $user): ?Tenant
    {
        $organization = $user->organization;
        if (! $organization) {
            return null;
        }

        return $organization->tenant;
    }

    private function deleteFromTenant(Tenant $tenant, User $user): void
    {
        $manager = app(TenantDatabaseManager::class);
        $tenantConnection = config('tenancy.tenant_connection', 'tenant');

        $manager->activateTenantConnection($tenant);

        DB::connection($tenantConnection)
            ->table('users')
            ->where('email', (string) $user->email)
            ->delete();

        $manager->activateLandlordConnection();
    }

    private function deleteFromLandlord(string $landlordConnection, User $user): void
    {
        User::on($landlordConnection)
            ->withoutGlobalScopes()
            ->where('email', (string) $user->email)
            ->delete();
    }
}
