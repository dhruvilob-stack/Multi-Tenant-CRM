<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasDashboardArrangement;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Widgets\CustomerGrowthChart;
use App\Filament\Widgets\CustomerSegmentsChart;
use App\Filament\Widgets\FlaggedOrders;
use App\Filament\Widgets\FeaturesOverview;
use App\Filament\Widgets\OrderValueDistributionChart;
use App\Filament\Widgets\OrdersYearOverYearChart;
use App\Filament\Widgets\ProductMarginAnalysisChart;
use App\Filament\Widgets\ShopKpisStats;
use App\Filament\Widgets\TopProductsByRevenueChart;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class Dashboard extends BaseDashboard
{
    use HasDashboardArrangement;
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->maxDate(fn () => now()),
                        DatePicker::make('endDate')
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

    /**
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    protected function getDefaultDashboardWidgets(): array
    {
        return [
            ShopKpisStats::class,
            OrdersYearOverYearChart::class,
            CustomerGrowthChart::class,
            FlaggedOrders::class,
            TopProductsByRevenueChart::class,
            CustomerSegmentsChart::class,
            OrderValueDistributionChart::class,
            ProductMarginAnalysisChart::class,
            FeaturesOverview::class,
        ];
    }

    public function mount(): void
    {
        $this->mountHasDashboardArrangement();
    }

    /**
     * @return array<string, string>
     */
    protected function getOrderStatusOptions(): array
    {
        if (! OrderResource::canViewAny()) {
            return [];
        }

        return OrderResource::getEloquentQuery()
            ->whereNotNull('status')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
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
