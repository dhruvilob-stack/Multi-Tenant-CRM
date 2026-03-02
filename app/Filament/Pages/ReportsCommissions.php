<?php

namespace App\Filament\Pages;

use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ReportsCommissions extends Page
{
    protected string $view = 'filament.pages.reports-commissions';
    protected static ?string $slug = 'reports/commissions';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.reports_commissions.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::ORG_ADMIN], true);
    }
}
