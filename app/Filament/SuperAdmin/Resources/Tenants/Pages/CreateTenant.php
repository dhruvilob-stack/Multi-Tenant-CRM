<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Pages;

use App\Filament\SuperAdmin\Resources\Tenants\TenantResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantLifecycleService;
use App\Services\TenantSyncService;
use App\Services\TenantDatabaseManager;
use App\Services\UserAccessMailService;
use App\Support\UserRole;
use App\Support\PanelLoginPrefillStore;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $baseSlug = Str::slug((string) ($data['slug'] ?? $data['name']));
        if ($baseSlug === '') {
            $baseSlug = Str::slug((string) $data['name']);
        }
        $slug = $baseSlug;
        $suffix = 1;

        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        $organization = Organization::query()->create([
            'name' => (string) $data['name'],
            'slug' => $slug,
            'email' => (string) $data['email'],
            'status' => 'inactive',
        ]);

        $tenant = app(TenantSyncService::class)->ensureForOrganization($organization);

        app(TenantLifecycleService::class)->updateOrganizationTenant(
            $organization,
            (string) $slug,
            (string) ($data['domain'] ?? ''),
        );

        // Tenant lifecycle may mutate slug/domain/database (including DB rename).
        // Re-read the tenant so later DB activation uses the latest database name.
        $tenant = $tenant->fresh() ?? $tenant;

        $tenant->forceFill([
            'data' => array_merge((array) ($tenant->data ?? []), [
                'organization_id' => (int) $organization->id,
                'source' => 'auto-linked',
                'login_email' => (string) $data['email'],
                'login_password_encrypted' => Crypt::encryptString((string) $data['password']),
            ]),
            'status' => 'inactive',
        ])->save();

        $hashedPassword = Hash::make((string) $data['password']);
        $orgAdmin = User::query()->updateOrCreate([
            'email' => (string) $data['email'],
        ], [
            'name' => (string) $data['name'],
            'password' => $hashedPassword,
            'role' => UserRole::ORG_ADMIN,
            'organization_id' => (int) $organization->id,
            'status' => 'inactive',
            'parent_id' => Auth::id(),
            'email_verified_at' => now(),
            'contact_email' => (string) ($data['admin_contact_email'] ?? ''),
        ]);

        $orgAdmin->organizations()->syncWithoutDetaching([$organization->id]);

        app(TenantDatabaseManager::class)->activateTenantConnection($tenant);
        $tenantConnection = config('tenancy.tenant_connection', 'tenant');

        DB::connection($tenantConnection)->table('organizations')->updateOrInsert(
            ['id' => (int) $organization->id],
            [
                'tenant_id' => null,
                'name' => (string) $organization->name,
                'slug' => (string) $organization->slug,
                'email' => (string) $organization->email,
                'status' => (string) ($organization->status ?? 'inactive'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::connection($tenantConnection)->table('users')->updateOrInsert(
            ['email' => (string) $data['email']],
            [
                'organization_id' => (int) $organization->id,
                'parent_id' => null,
                'name' => (string) $data['name'],
                'email' => (string) $data['email'],
                'contact_email' => (string) ($data['admin_contact_email'] ?? ''),
                'password' => $hashedPassword,
                'role' => UserRole::ORG_ADMIN,
                'status' => 'inactive',
                'email_verified_at' => now(),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        app(TenantDatabaseManager::class)->activateLandlordConnection();

        PanelLoginPrefillStore::saveForUser($orgAdmin, (string) $data['password']);
        app(UserAccessMailService::class)->sendForUser($orgAdmin, (string) $data['password']);

        return $organization;
    }

}
