<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inventories\InventoryResource;
use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use App\Models\Inventory;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class InventoryStockStatusChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use ResolvesPanelResourceAccess;

    protected ?string $heading = 'Stock Health Overview';
    protected ?string $description = 'Shows how many products are out of stock, low stock, or healthy based on your threshold.';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return static::canUseResource(InventoryResource::class);
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $threshold = (float) (((array) (auth()->user()?->organization?->settings ?? []))['system']['low_stock_threshold'] ?? 5);

        $inventory = $this->baseInventoryQuery();

        $outOfStock = (clone $inventory)->where('quantity_available', '<=', 0)->count();
        $lowStock = (clone $inventory)
            ->where('quantity_available', '>', 0)
            ->where('quantity_available', '<=', $threshold)
            ->count();
        $healthy = (clone $inventory)->where('quantity_available', '>', $threshold)->count();

        return [
            'labels' => ['Out of stock', 'Low stock', 'Healthy stock'],
            'datasets' => [
                [
                    'label' => 'Products',
                    'data' => [$outOfStock, $lowStock, $healthy],
                    'backgroundColor' => ['#ef4444', '#f59e0b', '#22c55e'],
                    'borderColor' => ['#ef4444', '#f59e0b', '#22c55e'],
                ],
            ],
        ];
    }

    private function baseInventoryQuery(): Builder
    {
        return InventoryResource::getEloquentQuery();
    }
}
