<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Roles extends Page
{
    protected string $view = 'filament.super-admin.pages.roles';
    protected static ?string $slug = 'roles';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.super_admin.groups.user_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.super_admin.pages.roles.nav');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}

