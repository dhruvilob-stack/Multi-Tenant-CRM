<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Profile extends Page
{
    protected string $view = 'filament.pages.profile';
    protected static ?string $slug = 'profile';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.profile.nav');
    }
}
