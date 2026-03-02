<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Plans extends Page
{
    protected string $view = 'filament.super-admin.pages.plans';
    protected static ?string $slug = 'plans';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.super_admin.groups.tenant_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.super_admin.pages.plans.nav');
    }
}

