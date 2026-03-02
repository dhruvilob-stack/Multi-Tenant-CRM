<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Settings extends Page
{
    protected string $view = 'filament.super-admin.pages.settings';
    protected static ?string $slug = 'settings';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.super_admin.groups.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.super_admin.pages.settings.nav');
    }
}

