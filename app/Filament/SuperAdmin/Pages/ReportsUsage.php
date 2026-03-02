<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ReportsUsage extends Page
{
    protected string $view = 'filament.super-admin.pages.reports-usage';
    protected static ?string $slug = 'reports/usage';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChartPie;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.super_admin.groups.analytics');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.super_admin.pages.reports_usage.nav');
    }
}

