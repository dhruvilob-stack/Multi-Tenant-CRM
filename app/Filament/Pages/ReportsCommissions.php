<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CommissionMonthlyFlowChart;
use App\Filament\Widgets\CommissionReportStats;
use App\Filament\Widgets\CommissionStatusBreakdownChart;
use App\Filament\Widgets\TopCommissionPartnersChart;
use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ReportsCommissions extends Page
{
    protected string $view = 'filament.pages.reports-commissions';
    protected static ?string $slug = 'reports/commissions';

        protected static ?string $title = 'Commission Dashboard';

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

    protected function getHeaderWidgets(): array
    {
        return [
            CommissionReportStats::class,
            CommissionMonthlyFlowChart::class,
            CommissionStatusBreakdownChart::class,
            TopCommissionPartnersChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
