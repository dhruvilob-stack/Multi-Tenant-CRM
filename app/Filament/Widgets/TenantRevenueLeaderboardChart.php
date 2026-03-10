<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class TenantRevenueLeaderboardChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Top Organizations by Revenue';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    protected function getData(): array
    {
        $startDate = filled($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])->startOfDay()
            : now()->subMonths(3)->startOfDay();
        $endDate = filled($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])->endOfDay()
            : now()->endOfDay();

        $rows = Invoice::query()
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->selectRaw('organisation_name as name, SUM(grand_total) as total')
            ->groupBy('organisation_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'labels' => $rows->map(fn ($row): string => (string) ($row->name ?: 'Unassigned'))->all(),
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $rows->map(fn ($row): float => round((float) $row->total, 2))->all(),
                    'backgroundColor' => '#0ea5e9',
                ],
            ],
        ];
    }
}

