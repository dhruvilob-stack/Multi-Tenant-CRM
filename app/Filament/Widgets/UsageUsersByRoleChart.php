<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\TenantUserSyncService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Str;

class UsageUsersByRoleChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Users by Role';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'doughnut';
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    protected function getData(): array
    {
        app(TenantUserSyncService::class)->syncAllTenantsToLandlord();

        $tenantId = (string) ($this->pageFilters['tenantId'] ?? '');
        $tenantOrganizationIds = $tenantId !== ''
            ? \App\Models\Organization::query()->where('tenant_id', $tenantId)->pluck('id')->all()
            : [];

        $rows = User::query()
            ->when($tenantId !== '', fn ($q) => $q->whereIn('organization_id', $tenantOrganizationIds))
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->map(fn ($row): string => Str::headline((string) $row->role))->all(),
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => $rows->map(fn ($row): int => (int) $row->total)->all(),
                    'backgroundColor' => ['#2563eb', '#16a34a', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
                ],
            ],
        ];
    }
}
