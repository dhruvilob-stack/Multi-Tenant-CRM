<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Consumers\ConsumerResource;
use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class CustomerGrowthChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use ResolvesPanelResourceAccess;

    protected ?string $heading = 'Customer Growth';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return static::canUseResource(ConsumerResource::class);
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $startDate = filled($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])->startOfMonth()
            : Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = filled($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])->endOfMonth()
            : now();

        $months = collect();
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        $customers = ConsumerResource::getEloquentQuery()
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->get()
            ->groupBy(fn ($customer): string => $customer->created_at?->format('Y-m') ?? '')
            ->map(fn ($group) => $group->count());

        $labels = [];
        $data = [];

        foreach ($months as $month) {
            $labels[] = $month->format('M Y');
            $data[] = $customers->get($month->format('Y-m'), 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Customers',
                    'data' => $data,
                    'fill' => 'start',
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.12)',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
