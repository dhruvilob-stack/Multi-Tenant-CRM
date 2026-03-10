<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\Widgets\PlatformAuditEventsByTenantChart;
use App\Filament\Widgets\SuperAdminUsageStats;
use App\Filament\Widgets\UsageActivityTrendChart;
use App\Filament\Widgets\UsageUsersByRoleChart;
use App\Models\Tenant;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ReportsUsage extends Page
{
    use HasFiltersForm;

    protected string $view = 'filament.super-admin.pages.reports-usage';
    protected static ?string $slug = 'reports/usage';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChartPie;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.super_admin.groups.analytics');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.super_admin.pages.reports_usage.nav');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('From date')
                            ->maxDate(now()),
                        DatePicker::make('endDate')
                            ->label('To date')
                            ->maxDate(now()),
                        Select::make('tenantId')
                            ->label('Tenant')
                            ->options(fn (): array => Tenant::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SuperAdminUsageStats::class,
            UsageActivityTrendChart::class,
            UsageUsersByRoleChart::class,
            PlatformAuditEventsByTenantChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }
}
