<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Mail;
use App\Models\OrganizationMail;
use App\Support\UserRole;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class InboxMailSent extends Page
{
    protected string $view = 'filament.pages.inbox-mail-sent';
    protected static ?string $slug = 'inbox-mail/sent';
    protected static ?string $cluster = Mail::class;
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedPaperAirplane;
    protected static ?string $navigationLabel = 'Sent';
    protected static ?int $navigationSort = 2;

    public array $messages = [];
    public ?array $openedMail = null;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::ORG_ADMIN,
            UserRole::MANUFACTURER,
            UserRole::DISTRIBUTOR,
            UserRole::VENDOR,
            UserRole::CONSUMER,
        ], true);
    }

    public function mount(): void
    {
        $this->loadMessages();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compose')
                ->label('Compose')
                ->icon(Heroicon::PencilSquare)
                ->color('primary')
                ->action(fn () => $this->dispatch('open-mail-composer')),
        ];
    }

    public function openMail(int $mailId): void
    {
        $mail = OrganizationMail::query()
            ->with('recipients')
            ->where('id', $mailId)
            ->where('sender_id', auth()->id())
            ->first();

        if (! $mail) {
            return;
        }

        $to = $mail->recipients->where('recipient_type', 'to')->pluck('recipient_email')->values()->all();
        $cc = $mail->recipients->where('recipient_type', 'cc')->pluck('recipient_email')->values()->all();
        $bcc = $mail->recipients->where('recipient_type', 'bcc')->pluck('recipient_email')->values()->all();

        $this->openedMail = [
            'id' => $mail->id,
            'subject' => $mail->subject,
            'from' => $mail->sender_email,
            'sent_at' => optional($mail->sent_at)->toDateTimeString(),
            'body' => $mail->body,
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
        ];

        $replyTo = (string) ($to[0] ?? '');
        $subject = (string) ($this->openedMail['subject'] ?? '');
        $replySubject = str_starts_with(strtolower($subject), 're:') ? $subject : 'Re: '.$subject;
        $replyBody = "\n\n---- Original message ----\n".strip_tags((string) ($this->openedMail['body'] ?? ''));
        $this->dispatch('open-mail-viewer', [
            'subject' => $this->openedMail['subject'] ?? '',
            'from' => $this->openedMail['from'] ?? '',
            'to' => implode(', ', $this->openedMail['to'] ?? []),
            'sent_at' => $this->openedMail['sent_at'] ?? '',
            'body' => $this->openedMail['body'] ?? '',
            'reply_to' => $replyTo,
            'reply_subject' => $replySubject,
            'reply_body' => $replyBody,
        ]);
    }

    private function loadMessages(): void
    {
        $this->messages = OrganizationMail::query()
            ->with('recipients')
            ->where('sender_id', auth()->id())
            ->whereNull('deleted_by_sender_at')
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(function (OrganizationMail $mail): array {
                $to = $mail->recipients->where('recipient_type', 'to')->pluck('recipient_email')->values()->all();

                return [
                    'id' => $mail->id,
                    'subject' => $mail->subject,
                    'to' => implode(', ', array_slice($to, 0, 2)).(count($to) > 2 ? ' +' . (count($to) - 2) . ' more' : ''),
                    'sent_at' => optional($mail->sent_at)->toDateTimeString(),
                ];
            })
            ->all();
    }
}
