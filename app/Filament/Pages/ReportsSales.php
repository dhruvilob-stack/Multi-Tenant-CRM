<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Widgets\OrderValueDistributionChart;
use App\Filament\Widgets\OrdersYearOverYearChart;
use App\Filament\Widgets\RevenueTrendChart;
use App\Filament\Widgets\SalesReportKpiStats;
use App\Filament\Widgets\SalesReportRecentOrdersTable;
use App\Filament\Widgets\TopProductsByRevenueChart;
use App\Support\UserRole;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class ReportsSales extends Page
{
    use HasFiltersForm;

    protected string $view = 'filament.pages.reports-sales';
    protected static ?string $slug = 'reports/sales';
        protected static ?string $title = 'Sales Dashboard';

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

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('From date')
                            ->maxDate(fn () => now()),
                        DatePicker::make('endDate')
                            ->label('To date')
                            ->maxDate(now()),
                        Select::make('orderStatuses')
                            ->label('Order status')
                            ->options(fn (): array => $this->getOrderStatusOptions())
                            ->multiple()
                            ->searchable(),
                        Select::make('productCategory')
                            ->label('Product category')
                            ->options(fn (): array => $this->getCategoryOptions())
                            ->searchable(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SalesReportKpiStats::class,
            RevenueTrendChart::class,
            OrdersYearOverYearChart::class,
            TopProductsByRevenueChart::class,
            OrderValueDistributionChart::class,
            SalesReportRecentOrdersTable::class,
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

    /**
     * @return array<string, string>
     */
    protected function getOrderStatusOptions(): array
    {
        $statuses = collect([
            'new',
            'pending',
            'processing',
            'packed',
            'shipped',
            'delivered',
            'cancelled',
            'refunded',
            'failed',
        ]);

        return $statuses
            ->mapWithKeys(fn (string $status): array => [$status => Str::headline($status)])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function getCategoryOptions(): array
    {
        if (! CategoryResource::canViewAny()) {
            return [];
        }

        return CategoryResource::getEloquentQuery()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
