<?php

namespace App\Services;

use App\Mail\UserAccessMail;
use App\Models\Organization;
use App\Models\User;
use App\Support\PanelLoginTokenService;
use App\Support\UserRole;
use Illuminate\Support\Facades\Mail;

class UserAccessMailService
{
    public function sendForUser(User $user, string $plainPassword): void
    {
        $organization = Organization::query()->find((int) $user->organization_id);
        if (! $organization) {
            return;
        }

        $recipient = (string) ($user->contact_email ?: $user->email);
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $tenantSlug = (string) ($organization->slug ?: $organization->id);
        $token = PanelLoginTokenService::make($tenantSlug, (string) $user->email, $plainPassword);
        $path = $user->role === UserRole::ORG_ADMIN
            ? '/'.$tenantSlug.'/login'
            : '/'.$tenantSlug.'/'.$this->roleSlug($user->role).'/login';

        $loginUrl = url($path.'?' . http_build_query([
            'sa_prefill' => $token,
        ]));

        $payload = [
            'organizationName' => (string) $organization->name,
            'userName' => (string) ($user->name ?: $user->first_name ?: 'User'),
            'roleLabel' => $this->roleLabel($user->role),
            'loginEmail' => (string) $user->email,
            'loginPassword' => $plainPassword,
            'loginUrl' => $loginUrl,
        ];

        Mail::to($recipient)->send(new UserAccessMail($payload));
    }

    private function roleSlug(?string $role): string
    {
        return match ($role) {
            UserRole::ORG_ADMIN => 'organization-admin',
            UserRole::MANUFACTURER => 'manufacturer',
            UserRole::DISTRIBUTOR => 'distributor',
            UserRole::VENDOR => 'vendor',
            UserRole::CONSUMER => 'consumer',
            default => 'organization-admin',
        };
    }

    private function roleLabel(?string $role): string
    {
        return match ($role) {
            UserRole::ORG_ADMIN => 'Organization Admin',
            UserRole::MANUFACTURER => 'Manufacturer',
            UserRole::DISTRIBUTOR => 'Distributor',
            UserRole::VENDOR => 'Vendor',
            UserRole::CONSUMER => 'Consumer',
            default => 'User',
        };
    }
}
