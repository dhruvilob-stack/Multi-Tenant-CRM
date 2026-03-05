<?php

namespace App\Filament\Clusters;

use App\Models\OrganizationMailRecipient;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class Mail extends Cluster
{
    protected static ?string $slug = 'mail';

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Mail';

    protected static ?string $title = 'Mail';

    protected static string | \UnitEnum | null $navigationGroup = 'Inbox Mail';

    public static function getNavigationBadge(): ?string
    {
        $userId = auth()->id();

        if (! $userId) {
            return null;
        }

        $count = OrganizationMailRecipient::query()
            ->where('recipient_id', $userId)
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }
}
