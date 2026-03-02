<?php

namespace App\Services;

use App\Jobs\SendInvitationEmailJob;
use App\Models\Invitation;
use App\Models\User;
use App\Support\AccessMatrix;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function generateToken(string $email, int $organizationId, string $role, int $inviterId, int $ttlHours = 72): string
    {
        $payload = [
            'email' => strtolower(trim($email)),
            'org_id' => $organizationId,
            'role' => $role,
            'inviter_id' => $inviterId,
            'exp' => now()->addHours($ttlHours)->timestamp,
            'nonce' => Str::uuid()->toString(),
        ];

        $encrypted = Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));

        return $this->base64UrlEncode($encrypted);
    }

    public function sendInvitation(
        int $inviterId,
        string $inviteeEmail,
        string $role,
        int $organizationId,
        int $ttlHours = 72,
        bool $allowExistingPendingUser = false
    ): Invitation
    {
        $normalizedEmail = strtolower(trim($inviteeEmail));
        $inviter = User::query()->findOrFail($inviterId);

        if ($inviter->status !== 'active') {
            throw ValidationException::withMessages([
                'inviter_id' => 'Inviter account is not active.',
            ]);
        }

        if (! AccessMatrix::isSuper($inviter) && (int) $inviter->organization_id !== (int) $organizationId) {
            throw ValidationException::withMessages([
                'organization_id' => 'Invalid organization for inviter.',
            ]);
        }

        if (! AccessMatrix::isSuper($inviter) && ! array_key_exists($role, AccessMatrix::allowedInviteRoles($inviter))) {
            throw ValidationException::withMessages([
                'role' => 'You are not allowed to invite this role.',
            ]);
        }

        $existingUser = User::withoutGlobalScopes()->where('email', $normalizedEmail)->first();

        if ($existingUser && ! $allowExistingPendingUser) {
            throw ValidationException::withMessages([
                'invitee_email' => 'A user with this email already exists.',
            ]);
        }

        if ($existingUser && $allowExistingPendingUser) {
            $sameOrganization = (int) $existingUser->organization_id === (int) $organizationId;
            $sameRole = (string) $existingUser->role === (string) $role;
            $isPending = (string) $existingUser->status === 'pending';

            if (! ($sameOrganization && $sameRole && $isPending)) {
                throw ValidationException::withMessages([
                    'invitee_email' => 'Existing user does not match pending invite requirements.',
                ]);
            }
        }

        return DB::transaction(function () use ($inviterId, $normalizedEmail, $role, $organizationId, $ttlHours): Invitation {
            Invitation::query()
                ->where('invitee_email', $normalizedEmail)
                ->where('organization_id', $organizationId)
                ->whereNull('accepted_at')
                ->update(['expires_at' => now()]);

            $token = $this->generateToken($normalizedEmail, $organizationId, $role, $inviterId, $ttlHours);

            $invitation = Invitation::query()->create([
                'inviter_id' => $inviterId,
                'invitee_email' => $normalizedEmail,
                'role' => $role,
                'token' => $token,
                'token_hash' => hash('sha256', $token),
                'organization_id' => $organizationId,
                'expires_at' => now()->addHours($ttlHours),
            ]);

            SendInvitationEmailJob::dispatch($invitation->id);

            return $invitation;
        });
    }

    public function verifyToken(string $token): array
    {
        $invitation = Invitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->orWhere('token', $token) // backward compatibility for legacy rows.
            ->first();

        if (! $invitation) {
            throw ValidationException::withMessages(['token' => 'Invitation token not found.']);
        }

        $payload = $this->decodePayload($token);

        if ($payload['email'] !== strtolower($invitation->invitee_email)) {
            throw ValidationException::withMessages(['token' => 'Invitation email mismatch.']);
        }

        if ((int) $payload['org_id'] !== (int) $invitation->organization_id) {
            throw ValidationException::withMessages(['token' => 'Invitation organization mismatch.']);
        }

        if ((int) $payload['exp'] < now()->timestamp || $invitation->isExpired()) {
            throw ValidationException::withMessages(['token' => 'Invitation token expired.']);
        }

        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages(['token' => 'Invitation has already been accepted.']);
        }

        return ['invitation' => $invitation, 'payload' => $payload];
    }

    public function acceptInvitation(string $token, string $name, string $password): User
    {
        return DB::transaction(function () use ($token, $name, $password): User {
            ['invitation' => $invitation, 'payload' => $payload] = $this->verifyToken($token);

            $user = User::withoutGlobalScopes()
                ->where('email', $payload['email'])
                ->where('organization_id', $payload['org_id'])
                ->where('role', $payload['role'])
                ->first();

            if ($user && $user->status !== 'pending') {
                throw ValidationException::withMessages([
                    'token' => 'User is already active for this invitation.',
                ]);
            }

            if (! $user) {
                $user = User::query()->create([
                    'name' => $name,
                    'email' => $payload['email'],
                    'password' => $password,
                    'role' => $payload['role'],
                    'organization_id' => $payload['org_id'],
                    'parent_id' => $payload['inviter_id'],
                    'status' => 'active',
                    'invitation_token' => $token,
                    'invitation_accepted_at' => now(),
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->fill([
                    'name' => $name ?: $user->name,
                    'password' => $password,
                    'parent_id' => $payload['inviter_id'],
                    'status' => 'active',
                    'invitation_token' => $token,
                    'invitation_accepted_at' => now(),
                    'email_verified_at' => now(),
                ]);
                $user->save();
            }

            $user->organizations()->syncWithoutDetaching([$payload['org_id']]);

            $invitation->update(['accepted_at' => now()]);

            return $user;
        });
    }

    private function decodePayload(string $token): array
    {
        $encrypted = $this->base64UrlDecode($token);
        $json = Crypt::decryptString($encrypted);

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
