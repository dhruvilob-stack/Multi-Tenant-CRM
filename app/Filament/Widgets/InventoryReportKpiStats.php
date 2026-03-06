<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inventories\InventoryResource;
use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use App\Models\Inventory;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class InventoryReportKpiStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use ResolvesPanelResourceAccess;

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return static::canUseResource(InventoryResource::class);
    }

    protected function getStats(): array
    {
        $threshold = (float) (((array) (auth()->user()?->organization?->settings ?? []))['system']['low_stock_threshold'] ?? 5);

        $inventory = $this->baseInventoryQuery();

        $totalRows = (clone $inventory)->count();
        $availableQty = (float) (clone $inventory)->sum('quantity_available');
        $reservedQty = (float) (clone $inventory)->sum('quantity_reserved');
        $uniqueProducts = (clone $inventory)->distinct('product_id')->count('product_id');
        $outOfStock = (clone $inventory)->where('quantity_available', '<=', 0)->count();
        $lowStock = (clone $inventory)
            ->where('quantity_available', '>', 0)
            ->where('quantity_available', '<=', $threshold)
            ->count();

        return [
            Stat::make('Inventory Records', (string) $totalRows)
                ->description('Rows currently visible with your filters')
                ->color('primary'),
            Stat::make('Total Available Qty', number_format($availableQty, 3))
                ->description('Current available quantity in stock')
                ->color('success'),
            Stat::make('Total Reserved Qty', number_format($reservedQty, 3))
                ->description('Quantity reserved for pending allocation')
                ->color('warning'),
            Stat::make('Unique Products', (string) $uniqueProducts)
                ->description('Distinct products represented in stock')
                ->color('info'),
            Stat::make('Low Stock Products', (string) $lowStock)
                ->description('Available above 0 and below threshold')
                ->color('danger'),
            Stat::make('Out Of Stock', (string) $outOfStock)
                ->description('Products currently at zero availability')
                ->color('gray'),
        ];
    }

    private function baseInventoryQuery(): Builder
    {
        return InventoryResource::getEloquentQuery();
    }
}
