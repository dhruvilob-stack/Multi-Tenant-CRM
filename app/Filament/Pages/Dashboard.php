<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasDashboardArrangement;
use App\Filament\Widgets\CustomersRevenueBarChart;
use App\Filament\Widgets\PanelResourcesOverview;
use App\Filament\Widgets\RevenueTrendChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use HasDashboardArrangement;

    /**
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    protected function getDefaultDashboardWidgets(): array
    {
        return [
            PanelResourcesOverview::class,
            RevenueTrendChart::class,
            CustomersRevenueBarChart::class,
        ];
    }

    public function mount(): void
    {
        $this->mountHasDashboardArrangement();
    }
}
