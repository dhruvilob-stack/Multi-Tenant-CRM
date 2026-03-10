<?php

namespace App\Filament\Widgets;

use App\Models\PlatformAuditLog;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class UsageActivityTrendChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Platform Activity Trend';

    protected ?string $description = 'Daily audit activity for the last 14 days.';

    protected static ?int $sort = 1;

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    protected function getData(): array
    {
        $tenantId = (string) ($this->pageFilters['tenantId'] ?? '');

        $days = collect(range(13, 0))
            ->map(fn (int $shift): string => now()->subDays($shift)->format('Y-m-d'))
            ->push(now()->format('Y-m-d'))
            ->values();

        $events = PlatformAuditLog::query()
            ->when($tenantId !== '', fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('created_at', '>=', now()->startOfDay()->subDays(13))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d') as dt, COUNT(*) as total")
            ->groupBy('dt')
            ->pluck('total', 'dt');

        return [
            'labels' => $days->map(fn (string $dt): string => Carbon::parse($dt)->format('d M'))->all(),
            'datasets' => [
                [
                    'label' => 'Events',
                    'data' => $days->map(fn (string $dt): int => (int) ($events[$dt] ?? 0))->all(),
                    'borderColor' => '#0284c7',
                    'backgroundColor' => 'rgba(2, 132, 199, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
        ];
    }
}
