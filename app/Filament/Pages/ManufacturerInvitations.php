<?php

namespace App\Filament\Pages;

use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ManufacturerInvitations extends Page
{
    protected string $view = 'filament.pages.manufacturer-invitations';
    protected static ?string $slug = 'manufacturers/invitations';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedEnvelope;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.my_network');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.manufacturer_invitations.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::MANUFACTURER], true);
    }
}
