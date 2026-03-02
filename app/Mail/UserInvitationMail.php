<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Invitation $invitation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You are invited to join the CRM');
    }

    public function content(): Content
    {
        $this->invitation->loadMissing('organization', 'inviter');
        $baseUrl = rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
        $acceptUrl = sprintf('%s/%s/invitation/%s', $baseUrl, $this->invitation->role, $this->invitation->token);

        return new Content(
            view: 'emails.invitations.user',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => $acceptUrl,
                'organizationName' => $this->invitation->organization?->name,
                'inviterName' => $this->invitation->inviter?->name,
                'inviterEmail' => $this->invitation->inviter?->email,
            ]
        );
    }
}
