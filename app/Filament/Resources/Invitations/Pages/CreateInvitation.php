<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Models\Invitation;
use App\Services\InvitationService;
use Filament\Resources\Pages\CreateRecord;

class CreateInvitation extends CreateRecord
{
    protected static string $resource = InvitationResource::class;

    protected function handleRecordCreation(array $data): Invitation
    {
        return app(InvitationService::class)->sendInvitation(
            inviterId: (int) auth()->id(),
            inviteeEmail: (string) $data['invitee_email'],
            role: (string) $data['role'],
            organizationId: (int) auth()->user()->organization_id,
            ttlHours: 72,
        );
    }
}
