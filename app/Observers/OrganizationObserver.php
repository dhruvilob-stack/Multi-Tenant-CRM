<?php

namespace App\Observers;

use App\Models\Organization;
use App\Services\TenantLifecycleService;
use App\Services\TenantSyncService;

class OrganizationObserver
{
    public function created(Organization $organization): void
    {
        if (! $organization->tenant_id) {
            app(TenantSyncService::class)->ensureForOrganization($organization);
        }
    }

    public function updated(Organization $organization): void
    {
        if (! $organization->tenant_id) {
            app(TenantSyncService::class)->ensureForOrganization($organization);
        }

        if ($organization->tenant_id) {
            app(TenantLifecycleService::class)->updateOrganizationTenant($organization);
        }
    }

    public function deleting(Organization $organization): void
    {
        if (! $organization->tenant_id) {
            return;
        }

        app(TenantLifecycleService::class)->deleteOrganizationTenant($organization, deleteOrganization: false);
    }
}
