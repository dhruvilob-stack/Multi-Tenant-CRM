<?php

namespace App\Mail;

use App\Models\OrganizationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationMailMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int, array{path:string,name?:string,mime?:string,size?:int}> $attachmentFiles
     */
    public function __construct(
        public OrganizationMail $mail,
        public array $attachmentFiles = []
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mail->subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organization-mail',
            with: [
                'mailRow' => $this->mail,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->attachmentFiles as $file) {
            $path = (string) ($file['path'] ?? '');
            if ($path === '' || ! is_file($path)) {
                continue;
            }

            $attachment = Attachment::fromPath($path);

            $name = trim((string) ($file['name'] ?? ''));
            if ($name !== '') {
                $attachment = $attachment->as($name);
            }

            $mime = trim((string) ($file['mime'] ?? ''));
            if ($mime !== '') {
                $attachment = $attachment->withMime($mime);
            }

            $attachments[] = $attachment;
        }

        return $attachments;
    }
}
