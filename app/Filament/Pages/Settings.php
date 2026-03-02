<?php

namespace App\Filament\Pages;

use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Settings extends Page
{
    protected string $view = 'filament.pages.settings';
    protected static ?string $slug = 'settings';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.settings.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::ORG_ADMIN], true);
    }
}
