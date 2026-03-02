<?php

namespace App\Filament\Pages;

use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ReportsSales extends Page
{
    protected string $view = 'filament.pages.reports-sales';
    protected static ?string $slug = 'reports/sales';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChartBar;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.reports_sales.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::ORG_ADMIN,
            UserRole::MANUFACTURER,
            UserRole::DISTRIBUTOR,
        ], true);
    }
}
