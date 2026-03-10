<?php

namespace App\Filament\Widgets;

use App\Models\PlatformAuditLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PlatformAuditEventsByTenantChart extends ChartWidget
{
    protected ?string $heading = 'Tenant Activity (Last 24 Hours)';

    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $since = now()->subHours(24);

        $rows = PlatformAuditLog::query()
            ->select(['tenant_slug'])
            ->where('created_at', '>=', $since)
            ->get()
            ->groupBy(fn ($row): string => (string) ($row->tenant_slug ?: 'unknown'))
            ->map(fn ($group): int => (int) $group->count())
            ->sortDesc()
            ->take(12);

        return [
            'labels' => $rows->keys()->values()->all(),
            'datasets' => [
                [
                    'label' => 'Events',
                    'data' => $rows->values()->all(),
                    'backgroundColor' => '#3b82f6',
                ],
            ],
        ];
    }
}

