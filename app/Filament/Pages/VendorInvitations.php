<?php

namespace App\Filament\Pages;

use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class VendorInvitations extends Page
{
    protected string $view = 'filament.pages.vendor-invitations';
    protected static ?string $slug = 'vendor/invitations';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedEnvelope;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.my_network');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.vendor_invitations.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::VENDOR], true);
    }
}
