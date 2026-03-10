<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Jobs\SendInvitationEmailJob;
use App\Models\Invitation;
use App\Services\InvitationService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditInvitation extends EditRecord
{
    protected static string $resource = InvitationResource::class;

    private bool $shouldResendInvitation = false;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->shouldResendInvitation = false;

        if (array_key_exists('invitee_email', $data)) {
            $normalizedEmail = strtolower(trim((string) $data['invitee_email']));
            $this->shouldResendInvitation = $normalizedEmail !== strtolower((string) $this->record->invitee_email);
            $data['invitee_email'] = $normalizedEmail;
        }

        unset($data['inviter_id'], $data['organization_id']);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Invitation $record */
        $record->fill($data);

        if ($this->shouldResendInvitation) {
            $service = app(InvitationService::class);
            $ttlHours = max(1, (int) ceil(now()->diffInSeconds($record->expires_at, false) / 3600));
            $token = $service->generateToken(
                email: (string) $record->invitee_email,
                organizationId: (int) $record->organization_id,
                role: (string) $record->role,
                inviterId: (int) $record->inviter_id,
                ttlHours: $ttlHours
            );

            $record->token = $token;
            $record->token_hash = hash('sha256', $token);
        }

        $record->save();

        return $record;
    }

    protected function afterSave(): void
    {
        if (! $this->shouldResendInvitation) {
            return;
        }

        SendInvitationEmailJob::dispatch($this->record->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
