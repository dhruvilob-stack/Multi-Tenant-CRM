<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantSyncService
{
    public function ensureForOrganization(Organization $organization): Tenant
    {
        $landlordConnection = config('tenancy.landlord_connection', 'landlord');

        if ($organization->tenant_id) {
            $existing = Tenant::on($landlordConnection)->find($organization->tenant_id);
            if ($existing) {
                $this->provisionTenantDatabase($existing);

                return $existing;
            }

            $slug = $this->uniqueSlug($organization);

            $tenant = Tenant::on($landlordConnection)->create([
                'id' => (string) $organization->tenant_id,
                'name' => $organization->name ?: ('Organization ' . $organization->id),
                'slug' => $slug,
                'domain' => $this->uniqueDomain($organization),
                'database' => $this->databaseNameForSlug($slug),
                'db_host' => config('tenancy.default_tenant_db_host'),
                'db_port' => (string) config('tenancy.default_tenant_db_port'),
                'db_username' => config('tenancy.default_tenant_db_username'),
                'db_password' => config('tenancy.default_tenant_db_password'),
                'status' => 'active',
                'data' => [
                    'organization_id' => $organization->id,
                    'source' => 'tenant_id-repair',
                ],
            ]);

            $this->provisionTenantDatabase($tenant);

            return $tenant;
        }

        $slug = $this->uniqueSlug($organization);

        $tenant = Tenant::on($landlordConnection)->create([
            'id' => (string) Str::uuid(),
            'name' => $organization->name ?: ('Organization ' . $organization->id),
            'slug' => $slug,
            'domain' => $this->uniqueDomain($organization),
            'database' => $this->databaseNameForSlug($slug),
            'db_host' => config('tenancy.default_tenant_db_host'),
            'db_port' => (string) config('tenancy.default_tenant_db_port'),
            'db_username' => config('tenancy.default_tenant_db_username'),
            'db_password' => config('tenancy.default_tenant_db_password'),
            'status' => 'active',
            'data' => [
                'organization_id' => $organization->id,
                'source' => 'auto-linked',
            ],
        ]);

        $this->provisionTenantDatabase($tenant);
        $organization->forceFill(['tenant_id' => $tenant->id])->saveQuietly();

        return $tenant;
    }

    public function backfillMissing(): int
    {
        $count = 0;

        Organization::query()
            ->whereNull('tenant_id')
            ->orWhere('tenant_id', '')
            ->chunkById(100, function ($organizations) use (&$count): void {
                foreach ($organizations as $organization) {
                    $this->ensureForOrganization($organization);
                    $count++;
                }
            });

        return $count;
    }

    protected function uniqueDomain(Organization $organization): string
    {
        $base = $this->baseSlug($organization);
        if ($base === '') {
            $base = 'org-' . ($organization->id ?: Str::lower(Str::random(6)));
        }

        $baseDomain = trim((string) config('tenancy.base_domain', 'multi-tenant-crm.localhost'));
        $candidate = $base . '.' . $baseDomain;
        $index = 1;

        while (Tenant::query()->where('domain', $candidate)->exists()) {
            $candidate = $base . '-' . $index . '.' . $baseDomain;
            $index++;
        }

        return $candidate;
    }

    protected function uniqueSlug(Organization $organization): string
    {
        $base = $this->baseSlug($organization);

        if ($base === '') {
            $base = 'tenant-' . ($organization->id ?: Str::lower(Str::random(6)));
        }

        $candidate = $base;
        $index = 1;

        while (Tenant::query()->where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $index;
            $index++;
        }

        return $candidate;
    }

    protected function baseSlug(Organization $organization): string
    {
        return trim((string) ($organization->slug ?: Str::slug((string) $organization->name)));
    }

    protected function databaseNameForSlug(string $slug): string
    {
        $prefix = (string) config('tenancy.database_prefix', 'tenant_');
        $slug = trim($slug) !== '' ? $slug : ('org_' . Str::lower(Str::random(6)));

        return Str::lower($prefix . str_replace('-', '_', $slug));
    }

    protected function provisionTenantDatabase(Tenant $tenant): void
    {
        $dbName = preg_replace('/[^A-Za-z0-9_]/', '', (string) $tenant->database);

        if ($dbName === '' || $dbName === null) {
            return;
        }

        $landlordConnection = config('tenancy.landlord_connection', 'landlord');
        DB::connection($landlordConnection)->statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        app(TenantDatabaseManager::class)->activateTenantConnection($tenant);

        $tenantConnection = config('tenancy.tenant_connection', 'tenant');
        $hasUsersTable = DB::connection($tenantConnection)->getSchemaBuilder()->hasTable('users');

        if (! $hasUsersTable) {
            Artisan::call('migrate', [
                '--database' => $tenantConnection,
                '--force' => true,
            ]);
        }

        app(TenantDatabaseManager::class)->activateLandlordConnection();
    }
}
