<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TopProductsByRevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use ResolvesPanelResourceAccess;

    protected ?string $heading = 'Top Products by Revenue';
    protected ?string $description = 'Shows which products generated the highest sales value in the selected filters.';

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return static::canUseAnyResource([OrderResource::class, ProductResource::class]);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $startDate = filled($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])->startOfDay()
            : null;
        $endDate = filled($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])->endOfDay()
            : null;
        $orderStatuses = $this->pageFilters['orderStatuses'] ?? null;
        $productCategory = $this->pageFilters['productCategory'] ?? null;

        $allowedOrderIds = OrderResource::getEloquentQuery()->pluck('id');

        if ($allowedOrderIds->isEmpty()) {
            return ['datasets' => [['label' => 'Revenue', 'data' => []]], 'labels' => []];
        }

        $products = OrderItem::query()
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.id', $allowedOrderIds)
            ->when($startDate, fn ($q) => $q->where('orders.created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('orders.created_at', '<=', $endDate))
            ->when(filled($orderStatuses), fn ($q) => $q->whereIn('orders.status', $orderStatuses))
            ->when(filled($productCategory), fn ($q) => $q->where('products.category_id', (int) $productCategory))
            ->select('products.name', DB::raw('SUM(order_items.qty * order_items.unit_price) as revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $products->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->all(),
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $products->pluck('name')->map(fn (string $name) => strlen($name) > 28 ? substr($name, 0, 28).'...' : $name)->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
        ];
    }
}
