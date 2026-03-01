<?php

namespace App\Jobs;

use App\Mail\UserInvitationMail;
use App\Models\Invitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendInvitationEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $invitationId)
    {
    }

    public function handle(): void
    {
        $invitation = Invitation::query()->find($this->invitationId);

        if (! $invitation || $invitation->isExpired() || $invitation->isAccepted()) {
            return;
        }

        Mail::to($invitation->invitee_email)->send(new UserInvitationMail($invitation));
    }
}
