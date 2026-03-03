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
use Illuminate\Support\Str;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $baseSlug = Str::slug((string) $data['name']);
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
            'status' => (string) ($data['status'] ?? 'active'),
        ]);

        app(TenantSyncService::class)->ensureForOrganization($organization);

        $orgAdmin = User::query()->updateOrCreate([
            'email' => (string) $data['email'],
        ], [
            'name' => (string) $data['name'],
            'password' => Hash::make((string) $data['password']),
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
