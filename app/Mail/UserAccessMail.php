<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserAccessMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string, string> $payload
     */
    public function __construct(public array $payload)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your organization access details');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.access.user-access',
            with: $this->payload,
        );
    }
}
