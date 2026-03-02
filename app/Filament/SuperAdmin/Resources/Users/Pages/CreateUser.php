<?php

namespace App\Filament\SuperAdmin\Resources\Users\Pages;

use App\Filament\SuperAdmin\Resources\Users\UserResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\TenantSyncService;
use App\Support\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): User
    {
        $organizationId = (int) ($data['organization_id'] ?? 0);

        if (
            (string) ($data['role'] ?? '') === UserRole::ORG_ADMIN
            && (bool) ($data['create_new_organization'] ?? false)
        ) {
            $baseSlug = Str::slug((string) ($data['new_organization_slug'] ?? $data['new_organization_name'] ?? 'organization'));
            $slug = $baseSlug !== '' ? $baseSlug : ('organization-' . now()->timestamp);

            $original = $slug;
            $index = 1;
            while (Organization::query()->where('slug', $slug)->exists()) {
                $slug = $original . '-' . $index;
                $index++;
            }

            $organization = Organization::query()->create([
                'name' => (string) ($data['new_organization_name'] ?? 'New Organization'),
                'slug' => $slug,
                'email' => (string) ($data['new_organization_email'] ?? $data['email']),
                'phone' => (string) ($data['new_organization_phone'] ?? ''),
                'address' => (string) ($data['new_organization_address'] ?? ''),
                'status' => 'active',
            ]);

            app(TenantSyncService::class)->ensureForOrganization($organization);
            $organizationId = (int) $organization->id;
        }

        if ($organizationId <= 0) {
            throw new RuntimeException('Please select an organization or create a new one for this user.');
        }

        $user = User::query()->create([
            'name' => (string) $data['name'],
            'email' => (string) $data['email'],
            'password' => Hash::make((string) ($data['password'] ?? Str::random(18))),
            'role' => (string) $data['role'],
            'organization_id' => $organizationId,
            'custom_role_id' => $data['custom_role_id'] ?? null,
            'status' => (string) ($data['status'] ?? 'active'),
            'parent_id' => Auth::id(),
            'email_verified_at' => now(),
        ]);

        if ($user->status === 'pending') {
            app(InvitationService::class)->sendInvitation(
                inviterId: (int) Auth::id(),
                inviteeEmail: $user->email,
                role: $user->role,
                organizationId: (int) $user->organization_id,
                ttlHours: 168,
                allowExistingPendingUser: true,
            );
        }

        return $user;
    }
}
