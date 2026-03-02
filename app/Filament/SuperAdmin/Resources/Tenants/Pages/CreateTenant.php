<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Pages;

use App\Filament\SuperAdmin\Resources\Tenants\TenantResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantSyncService;
use App\Support\UserRole;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $organization = Organization::query()->create([
            'name' => (string) $data['name'],
            'slug' => (string) $data['slug'],
            'email' => (string) ($data['email'] ?: $data['admin_email']),
            'phone' => (string) ($data['phone'] ?? ''),
            'status' => (string) ($data['status'] ?? 'active'),
            'address' => (string) ($data['address'] ?? ''),
        ]);

        app(TenantSyncService::class)->ensureForOrganization($organization);

        $orgAdmin = User::query()->create([
            'name' => (string) $data['admin_name'],
            'email' => (string) $data['admin_email'],
            'password' => Hash::make((string) $data['admin_password']),
            'role' => UserRole::ORG_ADMIN,
            'organization_id' => (int) $organization->id,
            'status' => 'active',
            'parent_id' => Auth::id(),
            'email_verified_at' => now(),
        ]);

        $orgAdmin->organizations()->syncWithoutDetaching([$organization->id]);

        return $organization;
    }
}
