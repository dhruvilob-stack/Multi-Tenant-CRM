<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantUserSyncService
{
    public function syncAllTenantsToLandlord(): void
    {
        $landlord = config('tenancy.landlord_connection', 'landlord');
        $tenantConnection = config('tenancy.tenant_connection', 'tenant');
        $tenants = Tenant::query()->get();

        foreach ($tenants as $tenant) {
            try {
                app(TenantDatabaseManager::class)->activateTenantConnection($tenant);
                $rows = DB::connection($tenantConnection)
                    ->table('users')
                    ->select([
                        'organization_id',
                        'parent_id',
                        'name',
                        'email',
                        'password',
                        'role',
                        'invitation_token',
                        'invitation_accepted_at',
                        'status',
                        'locale',
                        'custom_role_id',
                        'email_verified_at',
                        'remember_token',
                    ])
                    ->get();

                foreach ($rows as $row) {
                    DB::connection($landlord)->table('users')->updateOrInsert(
                        ['email' => (string) $row->email],
                        [
                            'organization_id' => $row->organization_id,
                            'parent_id' => $row->parent_id,
                            'name' => $row->name,
                            'email' => $row->email,
                            'password' => $row->password,
                            'role' => $row->role,
                            'invitation_token' => $row->invitation_token,
                            'invitation_accepted_at' => $row->invitation_accepted_at,
                            'status' => $row->status,
                            'locale' => $row->locale,
                            'custom_role_id' => $row->custom_role_id,
                            'email_verified_at' => $row->email_verified_at,
                            'remember_token' => $row->remember_token,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            } catch (\Throwable) {
                // Skip broken tenant config and continue with others.
                continue;
            }
        }

        app(TenantDatabaseManager::class)->activateLandlordConnection();
    }
}
