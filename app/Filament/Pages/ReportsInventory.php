<?php

namespace App\Filament\Pages;

use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ReportsInventory extends Page
{
    protected string $view = 'filament.pages.reports-inventory';
    protected static ?string $slug = 'reports/inventory';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChartBarSquare;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.reports_inventory.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::ORG_ADMIN], true);
    }
}
