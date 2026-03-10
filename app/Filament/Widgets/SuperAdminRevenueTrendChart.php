<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class SuperAdminRevenueTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Revenue Trend';

    protected ?string $description = 'Billed vs collected revenue by month.';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $startDate = filled($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])->startOfMonth()
            : now()->subMonths(11)->startOfMonth();
        $endDate = filled($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])->endOfMonth()
            : now()->endOfMonth();
        $organizationName = filled($this->pageFilters['organizationId'] ?? null)
            ? (string) \App\Models\Organization::query()->whereKey((int) $this->pageFilters['organizationId'])->value('name')
            : null;

        $months = collect();
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $months->push($cursor->format('Y-m'));
            $cursor->addMonth();
        }

        $base = Invoice::query()
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->when(filled($organizationName), fn ($q) => $q->where('organisation_name', $organizationName));

        $billed = (clone $base)
            ->where('status', '!=', 'cancelled')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(grand_total) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $collected = (clone $base)
            ->where('status', 'paid')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(received_amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return [
            'labels' => $months->map(fn (string $ym): string => Carbon::createFromFormat('Y-m', $ym)->format('M Y'))->all(),
            'datasets' => [
                [
                    'label' => 'Billed',
                    'data' => $months->map(fn (string $ym): float => round((float) ($billed[$ym] ?? 0), 2))->all(),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Collected',
                    'data' => $months->map(fn (string $ym): float => round((float) ($collected[$ym] ?? 0), 2))->all(),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.1)',
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
        ];
    }
}

