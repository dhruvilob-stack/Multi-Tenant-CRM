<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantSyncService
{
    public function ensureForOrganization(Organization $organization): Tenant
    {
        if ($organization->tenant_id) {
            $existing = Tenant::query()->find($organization->tenant_id);
            if ($existing) {
                return $existing;
            }

            return Tenant::query()->create([
                'id' => (string) $organization->tenant_id,
                'name' => $organization->name ?: ('Organization ' . $organization->id),
                'domain' => $this->uniqueDomain($organization),
                'data' => [
                    'organization_id' => $organization->id,
                    'source' => 'tenant_id-repair',
                ],
            ]);
        }

        $tenant = Tenant::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $organization->name ?: ('Organization ' . $organization->id),
            'domain' => $this->uniqueDomain($organization),
            'data' => [
                'organization_id' => $organization->id,
                'source' => 'auto-linked',
            ],
        ]);

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
        $base = trim((string) ($organization->slug ?: Str::slug((string) $organization->name)));
        if ($base === '') {
            $base = 'org-' . ($organization->id ?: Str::lower(Str::random(6)));
        }

        $candidate = $base . '.local';
        $index = 1;

        while (Tenant::query()->where('domain', $candidate)->exists()) {
            $candidate = $base . '-' . $index . '.local';
            $index++;
        }

        return $candidate;
    }
}

