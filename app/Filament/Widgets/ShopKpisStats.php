<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Consumers\ConsumerResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use App\Models\OrderItem;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ShopKpisStats extends BaseWidget
{
    use InteractsWithPageFilters;
    use ResolvesPanelResourceAccess;

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return static::canUseResource(OrderResource::class);
    }

    protected function getStats(): array
    {
        $startDate = filled($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])->startOfDay()
            : null;
        $endDate = filled($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])->endOfDay()
            : now();
        $orderStatuses = $this->pageFilters['orderStatuses'] ?? null;

        $orderQuery = OrderResource::getEloquentQuery()
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
            ->when(filled($orderStatuses), fn ($q) => $q->whereIn('status', $orderStatuses));

        $totalOrders = (clone $orderQuery)->count();
        $totalRevenue = (float) (clone $orderQuery)->sum('total_amount');
        $cancelledOrders = (clone $orderQuery)->where('status', 'cancelled')->count();

        $orderIds = (clone $orderQuery)->pluck('id');
        $totalItems = $orderIds->isNotEmpty()
            ? OrderItem::query()->whereIn('order_id', $orderIds)->count()
            : 0;

        $customerQuery = ConsumerResource::getEloquentQuery()
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate));

        $totalCustomers = (clone $customerQuery)->count();

        $repeatCustomers = (clone $orderQuery)
            ->whereNotNull('consumer_id')
            ->selectRaw('consumer_id, COUNT(*) as orders_count')
            ->groupBy('consumer_id')
            ->havingRaw('COUNT(*) >= 2')
            ->get()
            ->count();

        $repeatRate = $totalCustomers > 0
            ? round(($repeatCustomers / $totalCustomers) * 100, 1)
            : 0.0;
        $avgItemsPerOrder = $totalOrders > 0
            ? round($totalItems / $totalOrders, 1)
            : 0.0;
        $cancellationRate = $totalOrders > 0
            ? round(($cancelledOrders / $totalOrders) * 100, 1)
            : 0.0;
        $revenuePerCustomer = $totalCustomers > 0
            ? round($totalRevenue / $totalCustomers, 2)
            : 0.0;

        $months = collect(range(6, 0))->map(fn (int $ago) => now()->subMonths($ago)->startOfMonth());
        $monthStart = $months->first();

        $monthlyOrders = OrderResource::getEloquentQuery()
            ->where('created_at', '>=', $monthStart)
            ->when(filled($orderStatuses), fn ($q) => $q->whereIn('status', $orderStatuses))
            ->get(['id', 'consumer_id', 'status', 'total_amount', 'created_at']);

        $repeatChart = [];
        $avgItemsChart = [];
        $cancellationChart = [];
        $revenueChart = [];

        foreach ($months as $month) {
            $monthKey = $month->format('Y-m');

            $monthOrders = $monthlyOrders->filter(
                fn ($order): bool => $order->created_at?->format('Y-m') === $monthKey,
            );

            $monthOrderCount = $monthOrders->count();
            $monthRevenue = (float) $monthOrders->sum('total_amount');

            $byCustomer = $monthOrders
                ->filter(fn ($order): bool => filled($order->consumer_id))
                ->groupBy('consumer_id');

            $monthCustomerCount = $byCustomer->count();
            $monthRepeatCount = $byCustomer->filter(fn ($orders) => $orders->count() >= 2)->count();

            $repeatChart[] = $monthCustomerCount > 0
                ? round(($monthRepeatCount / $monthCustomerCount) * 100, 1)
                : 0;

            $monthOrderIds = $monthOrders->pluck('id');
            $monthItems = $monthOrderIds->isNotEmpty()
                ? OrderItem::query()->whereIn('order_id', $monthOrderIds)->count()
                : 0;

            $avgItemsChart[] = $monthOrderCount > 0
                ? round($monthItems / $monthOrderCount, 1)
                : 0;

            $monthCancelled = $monthOrders->where('status', 'cancelled')->count();
            $cancellationChart[] = $monthOrderCount > 0
                ? round(($monthCancelled / $monthOrderCount) * 100, 1)
                : 0;

            $revenueChart[] = $monthCustomerCount > 0
                ? round($monthRevenue / $monthCustomerCount, 2)
                : 0;
        }

        return [
            Stat::make('Repeat Customer Rate', $repeatRate.'%')
                ->description($repeatCustomers.' repeat customers')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->chart($repeatChart)
                ->color('success'),
            Stat::make('Avg Items / Order', (string) $avgItemsPerOrder)
                ->description($totalItems.' items across '.$totalOrders.' orders')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->chart($avgItemsChart)
                ->color('info'),
            Stat::make('Cancellation Rate', $cancellationRate.'%')
                ->description($cancelledOrders.' cancelled orders')
                ->descriptionIcon('heroicon-o-x-circle')
                ->chart($cancellationChart)
                ->color($cancellationRate > 10 ? 'danger' : 'warning'),
            Stat::make('Revenue / Customer', '$'.number_format($revenuePerCustomer, 2))
                ->description('$'.number_format($totalRevenue, 2).' total revenue')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->chart($revenueChart)
                ->color('success'),
        ];
    }
}
