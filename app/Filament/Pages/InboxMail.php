<?php

namespace App\Filament\Pages;

use App\Models\OrganizationMailRecipient;
use App\Support\UserRole;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class InboxMail extends Page
{
    protected string $view = 'filament.pages.inbox-mail';
    protected static ?string $slug = 'inbox-mail';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedInbox;
    protected static ?string $navigationLabel = 'Inbox Mail';
    protected static string|\UnitEnum|null $navigationGroup = 'Inbox Mail';

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

    public function markRead(int $recipientRowId): void
    {
        $row = OrganizationMailRecipient::query()
            ->where('id', $recipientRowId)
            ->where('recipient_id', auth()->id())
            ->first();

        if (! $row) {
            return;
        }

        $row->update(['read_at' => now()]);
        $this->loadMessages();
    }

    public function moveToTrash(int $recipientRowId): void
    {
        $row = OrganizationMailRecipient::query()
            ->where('id', $recipientRowId)
            ->where('recipient_id', auth()->id())
            ->first();

        if (! $row) {
            return;
        }

        $row->update(['deleted_at' => now()]);

        Notification::make()->success()->title('Moved to trash')->send();
        $this->loadMessages();

        if (($this->openedMail['id'] ?? null) === $recipientRowId) {
            $this->openedMail = null;
        }
    }

    public function toggleFeatured(int $recipientRowId): void
    {
        $row = OrganizationMailRecipient::query()
            ->where('id', $recipientRowId)
            ->where('recipient_id', auth()->id())
            ->first();

        if (! $row) {
            return;
        }

        $row->update(['featured' => ! $row->featured]);
        $this->loadMessages();
    }

    public function openMail(int $recipientRowId): void
    {
        $row = OrganizationMailRecipient::query()
            ->with('mail.recipients')
            ->where('id', $recipientRowId)
            ->where('recipient_id', auth()->id())
            ->first();

        if (! $row) {
            return;
        }

        if (! $row->read_at) {
            $row->update(['read_at' => now()]);
        }

        $meta = (array) ($row->mail?->meta ?? []);
        $attachmentRows = array_values(array_filter((array) ($meta['attachments'] ?? []), fn ($v) => is_array($v)));
        if ($attachmentRows === []) {
            $legacy = array_values(array_filter((array) ($meta['media_attachments'] ?? []), fn ($v) => is_string($v) && $v !== ''));
            $attachmentRows = array_map(fn (string $path): array => [
                'path' => $path,
                'name' => basename($path),
                'mime' => mime_content_type($path) ?: 'application/octet-stream',
                'size' => (int) @filesize($path),
            ], $legacy);
        }
        $attachments = collect($attachmentRows)->map(function (array $attachment, int $index) use ($row): array {
            $name = (string) ($attachment['name'] ?? ('attachment-'.$index));
            $mime = (string) ($attachment['mime'] ?? 'application/octet-stream');
            $isImage = str_starts_with(strtolower($mime), 'image/');

            return [
                'name' => $name,
                'mime' => $mime,
                'is_image' => $isImage,
                'download_url' => route('mail.attachments.download', ['recipient' => $row->id, 'index' => $index]),
                'preview_url' => route('mail.attachments.download', ['recipient' => $row->id, 'index' => $index, 'inline' => 1]),
            ];
        })->all();

        $this->openedMail = [
            'id' => $row->id,
            'subject' => $row->mail?->subject,
            'from' => $row->mail?->sender_email,
            'sent_at' => optional($row->mail?->sent_at)->toDateTimeString(),
            'body' => $row->mail?->body,
            'template_key' => $row->mail?->template_key,
            'attachments' => $attachments,
            'reply_url' => url('/admin/inbox-mail/send?reply='.$row->mail_id),
        ];

        $this->loadMessages();
    }

    private function loadMessages(): void
    {
        $this->messages = OrganizationMailRecipient::query()
            ->with('mail.sender')
            ->where('recipient_id', auth()->id())
            ->whereNull('deleted_at')
            ->latest('featured')
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn (OrganizationMailRecipient $row): array => [
                'id' => $row->id,
                'subject' => $row->mail?->subject,
                'from' => $row->mail?->sender_email,
                'sent_at' => optional($row->mail?->sent_at)->toDateTimeString(),
                'read_at' => optional($row->read_at)->toDateTimeString(),
                'featured' => (bool) $row->featured,
                'template_key' => $row->mail?->template_key,
            ])
            ->all();
    }
}
