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
        return new Content(
            view: 'emails.invitations.user',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => url('/invitation/'.$this->invitation->token),
            ]
        );
    }
}
