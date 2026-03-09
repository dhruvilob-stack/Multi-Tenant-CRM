<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class TenantLifecycleService
{
    public function deleteOrganizationTenant(Organization $organization): void
    {
        $tenant = $organization->tenant;

        if ($tenant) {
            $backupPath = $this->exportTenantDatabase($tenant);
            $this->emailBackup($tenant, $backupPath);
            $this->dropTenantDatabase($tenant);
        }

        DB::connection(config('tenancy.landlord_connection', 'landlord'))->transaction(function () use ($organization, $tenant): void {
            User::query()->where('organization_id', $organization->id)->delete();

            try {
                $organization->users()->detach();
            } catch (Throwable) {
                // Ignore if pivot table is unavailable in a specific environment.
            }

            $organization->delete();

            if ($tenant) {
                $tenant->delete();
            }
        });
    }

    public function updateOrganizationTenant(
        Organization $organization,
        ?string $tenantSlug = null,
        ?string $tenantDomain = null
    ): void
    {
        $tenant = $organization->tenant;
        if (! $tenant) {
            return;
        }

        $domain = $this->normalizeDomain($tenantDomain) ?: (string) $tenant->domain;
        $requestedSlug = $this->sanitizeSlug($tenantSlug);
        $slugFromDomain = $this->deriveSlugFromDomain($domain);
        $slugBase = $requestedSlug !== '' ? $requestedSlug : $slugFromDomain;
        $slug = $slugBase !== '' ? $this->uniqueSlug($slugBase, $tenant->id) : (string) $tenant->slug;
        $domain = $domain !== '' ? $this->uniqueDomain($domain, $tenant->id) : (string) $tenant->domain;

        $newDatabase = $this->databaseNameForSlug($slug);

        $tenant->forceFill([
            'name' => (string) $organization->name,
            'domain' => $domain,
            'slug' => $slug,
        ])->save();

        if ($newDatabase !== '' && $newDatabase !== (string) $tenant->database) {
            $this->renameTenantDatabase($tenant, $newDatabase);
        }

        app(TenantDatabaseManager::class)->activateTenantConnection($tenant);
        $tenantConnection = config('tenancy.tenant_connection', 'tenant');
        $tenantSchema = DB::connection($tenantConnection)->getSchemaBuilder();

        DB::connection($tenantConnection)->table('organizations')->updateOrInsert(
            ['id' => (int) $organization->id],
            [
                'tenant_id' => null,
                'name' => (string) $organization->name,
                'slug' => (string) $organization->slug,
                'email' => (string) $organization->email,
                'status' => (string) ($organization->status ?? 'active'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if ($tenantSchema->hasTable('tenants')) {
            DB::connection($tenantConnection)->table('tenants')->updateOrInsert(
                ['id' => (string) $tenant->id],
                [
                    'name' => (string) $tenant->name,
                    'slug' => (string) $tenant->slug,
                    'domain' => (string) $tenant->domain,
                    'database' => (string) $tenant->database,
                    'db_host' => (string) ($tenant->db_host ?: config('tenancy.default_tenant_db_host')),
                    'db_port' => (int) ($tenant->db_port ?: config('tenancy.default_tenant_db_port')),
                    'db_username' => (string) ($tenant->db_username ?: config('tenancy.default_tenant_db_username')),
                    'db_password' => (string) ($tenant->db_password ?: config('tenancy.default_tenant_db_password')),
                    'status' => (string) ($tenant->status ?: 'active'),
                    'updated_at' => now(),
                ]
            );
        }

        if ($tenantSchema->hasTable('users')) {
            DB::connection($tenantConnection)->table('users')
                ->where('organization_id', (int) $organization->id)
                ->where('role', 'org_admin')
                ->update([
                    'name' => (string) $organization->name,
                    'updated_at' => now(),
                ]);
        }

        DB::connection(config('tenancy.landlord_connection', 'landlord'))->table('users')
            ->where('organization_id', (int) $organization->id)
            ->where('role', 'org_admin')
            ->update([
                'name' => (string) $organization->name,
                'updated_at' => now(),
            ]);

        app(TenantDatabaseManager::class)->activateLandlordConnection();
    }

    public function exportTenantDatabase(Tenant $tenant): string
    {
        app(TenantDatabaseManager::class)->activateTenantConnection($tenant);
        $tenantConnection = config('tenancy.tenant_connection', 'tenant');
        $connection = DB::connection($tenantConnection);

        $dbName = (string) $tenant->database;
        $tables = $connection->select('SHOW TABLES');
        $backup = [];
        $backup[] = '-- Tenant Backup';
        $backup[] = '-- Tenant: ' . ($tenant->slug ?: $tenant->id);
        $backup[] = '-- Database: ' . $dbName;
        $backup[] = '-- Generated At: ' . now()->toDateTimeString();
        $backup[] = '';

        foreach ($tables as $row) {
            $table = (string) array_values((array) $row)[0];
            if ($table === '') {
                continue;
            }

            $createRow = (array) ($connection->selectOne("SHOW CREATE TABLE `{$table}`") ?? []);
            $createSql = (string) ($createRow['Create Table'] ?? array_values($createRow)[1] ?? '');
            if ($createSql === '') {
                continue;
            }

            $backup[] = "DROP TABLE IF EXISTS `{$table}`;";
            $backup[] = $createSql . ';';

            $rows = $connection->table($table)->get();
            foreach ($rows as $record) {
                $columns = [];
                $values = [];

                foreach ((array) $record as $column => $value) {
                    $columns[] = "`{$column}`";
                    $values[] = $this->quoteSqlValue($connection, $value);
                }

                $backup[] = sprintf(
                    'INSERT INTO `%s` (%s) VALUES (%s);',
                    $table,
                    implode(', ', $columns),
                    implode(', ', $values)
                );
            }

            $backup[] = '';
        }

        $directory = storage_path('app/tenant-backups');
        File::ensureDirectoryExists($directory);
        $filename = ($tenant->slug ?: $tenant->id) . '-' . now()->format('Ymd_His') . '.sql';
        $fullPath = $directory . DIRECTORY_SEPARATOR . $filename;
        File::put($fullPath, implode(PHP_EOL, $backup));

        app(TenantDatabaseManager::class)->activateLandlordConnection();

        return $fullPath;
    }

    public function emailBackup(Tenant $tenant, string $backupPath): void
    {
        $recipient = (string) env('TENANT_BACKUP_EMAIL', 'anakrani29@gmail.com');

        if ($recipient === '' || ! File::exists($backupPath)) {
            return;
        }

        $subject = 'Tenant DB Backup: ' . ($tenant->slug ?: $tenant->id);

        Mail::raw(
            'Attached is the tenant database backup for tenant: ' . ($tenant->slug ?: $tenant->id),
            function ($message) use ($recipient, $subject, $backupPath): void {
                $message
                    ->to($recipient)
                    ->subject($subject)
                    ->attach($backupPath, [
                        'as' => basename($backupPath),
                        'mime' => 'application/sql',
                    ]);
            }
        );
    }

    public function dropTenantDatabase(Tenant $tenant): void
    {
        $dbName = preg_replace('/[^A-Za-z0-9_]/', '', (string) $tenant->database);
        if ($dbName === null || $dbName === '') {
            return;
        }

        $landlordConnection = config('tenancy.landlord_connection', 'landlord');
        DB::connection($landlordConnection)->statement("DROP DATABASE IF EXISTS `{$dbName}`");
    }

    public function renameTenantDatabase(Tenant $tenant, string $newDatabaseName): void
    {
        $oldDatabase = preg_replace('/[^A-Za-z0-9_]/', '', (string) $tenant->database) ?: '';
        $newDatabase = preg_replace('/[^A-Za-z0-9_]/', '', $newDatabaseName) ?: '';

        if ($oldDatabase === '' || $newDatabase === '' || $oldDatabase === $newDatabase) {
            return;
        }

        $landlordConnection = config('tenancy.landlord_connection', 'landlord');
        DB::connection($landlordConnection)->statement("CREATE DATABASE IF NOT EXISTS `{$newDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        app(TenantDatabaseManager::class)->activateTenantConnection($tenant);
        $tenantConnection = config('tenancy.tenant_connection', 'tenant');
        $tables = DB::connection($tenantConnection)->select('SHOW TABLES');

        foreach ($tables as $row) {
            $table = (string) array_values((array) $row)[0];
            if ($table === '') {
                continue;
            }

            DB::connection($landlordConnection)->statement(
                "RENAME TABLE `{$oldDatabase}`.`{$table}` TO `{$newDatabase}`.`{$table}`"
            );
        }

        DB::connection($landlordConnection)->statement("DROP DATABASE IF EXISTS `{$oldDatabase}`");

        $tenant->forceFill(['database' => $newDatabase])->save();
    }

    private function databaseNameForSlug(string $slug): string
    {
        $prefix = (string) config('tenancy.database_prefix', 'tenant_');
        $normalized = Str::of($slug)->slug('_')->value();

        return strtolower($prefix . $normalized);
    }

    private function deriveSlugFromDomain(string $domain): string
    {
        return Str::of($domain)->before('.')->slug()->value();
    }

    private function sanitizeSlug(?string $slug): string
    {
        return Str::slug(trim((string) $slug));
    }

    private function normalizeDomain(?string $domain): string
    {
        return strtolower(trim((string) $domain));
    }

    private function uniqueDomain(string $domain, string $ignoreTenantId): string
    {
        $candidate = $domain;
        $suffix = 1;

        while (
            Tenant::query()
                ->where('domain', $candidate)
                ->whereKeyNot($ignoreTenantId)
                ->exists()
        ) {
            $candidate = $domain . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function uniqueSlug(string $slug, string $ignoreTenantId): string
    {
        $candidate = $slug;
        $suffix = 1;

        while (
            Tenant::query()
                ->where('slug', $candidate)
                ->whereKeyNot($ignoreTenantId)
                ->exists()
        ) {
            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function quoteSqlValue($connection, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $connection->getPdo()->quote((string) $value);
    }
}
