<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ReportsRevenue extends Page
{
    protected string $view = 'filament.super-admin.pages.reports-revenue';
    protected static ?string $slug = 'reports/revenue';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChartBar;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.super_admin.groups.analytics');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.super_admin.pages.reports_revenue.nav');
    }
}

