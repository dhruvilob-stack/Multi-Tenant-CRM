<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use App\Models\Inventory;
use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ProductMarginAnalysisChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use ResolvesPanelResourceAccess;

    protected ?string $heading = 'Product Margin Analysis';

    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        return static::canUseResource(ProductResource::class);
    }

    protected function getType(): string
    {
        return 'scatter';
    }

    protected function getData(): array
    {
        $productCategory = $this->pageFilters['productCategory'] ?? null;

        $products = ProductResource::getEloquentQuery()
            ->whereNotNull('base_price')
            ->where('base_price', '>', 0)
            ->when(filled($productCategory), fn ($q) => $q->where('category_id', (int) $productCategory))
            ->get(['id', 'base_price']);

        if ($products->isEmpty()) {
            return ['datasets' => [['label' => 'Products', 'showLine' => false, 'data' => [], 'backgroundColor' => '#3b82f6']]];
        }

        $productIds = $products->pluck('id');

        $avgSellingByProduct = OrderItem::query()
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, AVG(unit_price) as avg_selling')
            ->groupBy('product_id')
            ->pluck('avg_selling', 'product_id');

        $qtyByProduct = Inventory::query()
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, SUM(quantity_available) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $dataPoints = $products
            ->map(function ($product) use ($avgSellingByProduct, $qtyByProduct): ?array {
                $avgSelling = (float) ($avgSellingByProduct[$product->id] ?? 0);
                $basePrice = (float) $product->base_price;

                if ($avgSelling <= 0 || $basePrice <= 0) {
                    return null;
                }

                return [
                    'x' => round((($avgSelling - $basePrice) / $avgSelling) * 100, 1),
                    'y' => (int) round((float) ($qtyByProduct[$product->id] ?? 0)),
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'Products',
                    'showLine' => false,
                    'data' => $dataPoints,
                    'backgroundColor' => '#3b82f6',
                ],
            ],
        ];
    }
}
