<?php

namespace App\Filament\Pages;

use App\Models\OrganizationMailRecipient;
use App\Support\UserRole;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class InboxMailTrash extends Page
{
    protected string $view = 'filament.pages.inbox-mail-trash';
    protected static ?string $slug = 'inbox-mail/trash';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedTrash;
    protected static ?string $navigationLabel = 'Trash';
    protected static string|\UnitEnum|null $navigationGroup = 'Inbox Mail';

    public array $messages = [];

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

    public function restore(int $recipientRowId): void
    {
        $row = OrganizationMailRecipient::query()
            ->where('id', $recipientRowId)
            ->where('recipient_id', auth()->id())
            ->first();

        if (! $row) {
            return;
        }

        $row->update(['deleted_at' => null]);
        Notification::make()->success()->title('Restored to inbox')->send();
        $this->loadMessages();
    }

    private function loadMessages(): void
    {
        $this->messages = OrganizationMailRecipient::query()
            ->with('mail.sender')
            ->where('recipient_id', auth()->id())
            ->whereNotNull('deleted_at')
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (OrganizationMailRecipient $row): array => [
                'id' => $row->id,
                'subject' => $row->mail?->subject,
                'from' => $row->mail?->sender_email,
                'sent_at' => optional($row->mail?->sent_at)->toDateTimeString(),
            ])
            ->all();
    }
}
