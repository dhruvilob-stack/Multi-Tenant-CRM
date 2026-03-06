<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class OrdersYearOverYearChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use ResolvesPanelResourceAccess;

    protected ?string $heading = 'Orders Year-over-Year';
    protected ?string $description = 'Compares monthly order counts between the last 12 months and the 12 months before that.';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return static::canUseResource(OrderResource::class);
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $orderStatuses = $this->pageFilters['orderStatuses'] ?? null;

        $recentMonths = collect(range(11, 0))->map(fn (int $ago) => Carbon::now()->subMonths($ago)->startOfMonth());
        $priorMonths = collect(range(23, 12))->map(fn (int $ago) => Carbon::now()->subMonths($ago)->startOfMonth());

        $recentStart = $recentMonths->first();
        $priorStart = $priorMonths->first();

        $recentOrders = OrderResource::getEloquentQuery()
            ->where('created_at', '>=', $recentStart)
            ->when(filled($orderStatuses), fn ($q) => $q->whereIn('status', $orderStatuses))
            ->get()
            ->groupBy(fn ($order): string => $order->created_at?->format('Y-m') ?? '')
            ->map(fn ($group) => $group->count());

        $priorOrders = OrderResource::getEloquentQuery()
            ->where('created_at', '>=', $priorStart)
            ->where('created_at', '<', $recentStart)
            ->when(filled($orderStatuses), fn ($q) => $q->whereIn('status', $orderStatuses))
            ->get()
            ->groupBy(fn ($order): string => $order->created_at?->format('Y-m') ?? '')
            ->map(fn ($group) => $group->count());

        $recentData = [];
        $priorData = [];
        $labels = [];

        foreach ($recentMonths as $month) {
            $labels[] = $month->format('M Y');
            $recentData[] = $recentOrders->get($month->format('Y-m'), 0);
        }

        foreach ($priorMonths as $month) {
            $priorData[] = $priorOrders->get($month->format('Y-m'), 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Last 12 Months',
                    'data' => $recentData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.12)',
                    'fill' => 'start',
                ],
                [
                    'label' => 'Prior 12 Months',
                    'data' => $priorData,
                    'borderColor' => '#9ca3af',
                    'backgroundColor' => 'rgba(156, 163, 175, 0.06)',
                    'borderDash' => [5, 5],
                ],
            ],
            'labels' => $labels,
        ];
    }
}
