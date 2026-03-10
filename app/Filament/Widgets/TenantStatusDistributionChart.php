<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

class TenantStatusDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Tenant Status Distribution';

    protected static ?int $sort = 1;

    protected function getType(): string
    {
        return 'pie';
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    protected function getData(): array
    {
        $rows = Tenant::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->map(fn ($row): string => Str::headline((string) ($row->status ?: 'unknown')))->all(),
            'datasets' => [
                [
                    'label' => 'Tenants',
                    'data' => $rows->map(fn ($row): int => (int) $row->total)->all(),
                    'backgroundColor' => ['#22c55e', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6'],
                ],
            ],
        ];
    }
}

