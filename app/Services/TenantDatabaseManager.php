<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TenantDatabaseManager
{
    public function activateTenantConnection(Tenant $tenant): void
    {
        $tenantConnection = config('tenancy.tenant_connection', 'tenant');

        if (blank($tenant->database)) {
            throw new InvalidArgumentException("Tenant [{$tenant->id}] does not have a database configured.");
        }

        Config::set("database.connections.{$tenantConnection}.host", $tenant->db_host ?: config('tenancy.default_tenant_db_host'));
        Config::set("database.connections.{$tenantConnection}.port", (string) ($tenant->db_port ?: config('tenancy.default_tenant_db_port')));
        Config::set("database.connections.{$tenantConnection}.database", (string) $tenant->database);
        Config::set("database.connections.{$tenantConnection}.username", $tenant->db_username ?: config('tenancy.default_tenant_db_username'));
        Config::set("database.connections.{$tenantConnection}.password", $tenant->db_password ?: config('tenancy.default_tenant_db_password'));

        DB::purge($tenantConnection);
        DB::reconnect($tenantConnection);
        DB::setDefaultConnection($tenantConnection);
    }

    public function activateLandlordConnection(): void
    {
        DB::setDefaultConnection(config('tenancy.landlord_connection', 'landlord'));
    }
}
