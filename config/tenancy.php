<?php

return [
    'landlord_connection' => env('TENANCY_LANDLORD_CONNECTION', 'landlord'),
    'tenant_connection' => env('TENANCY_TENANT_CONNECTION', 'tenant'),

    'base_domain' => env('TENANCY_BASE_DOMAIN', 'multi-tenant-crm.localhost'),
    'local_path_prefix' => env('TENANCY_LOCAL_PATH_PREFIX', 'tenant'),

    'database_prefix' => env('TENANCY_DATABASE_PREFIX', 'tenant_'),
    'default_tenant_db_host' => env('TENANCY_DEFAULT_DB_HOST', env('DB_HOST', '127.0.0.1')),
    'default_tenant_db_port' => env('TENANCY_DEFAULT_DB_PORT', env('DB_PORT', '3306')),
    'default_tenant_db_username' => env('TENANCY_DEFAULT_DB_USERNAME', env('DB_USERNAME', 'root')),
    'default_tenant_db_password' => env('TENANCY_DEFAULT_DB_PASSWORD', env('DB_PASSWORD', '')),
];
