<?php

namespace App\Filament\Widgets;

use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantUserSyncService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuperAdminUsageStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    protected function getStats(): array
    {
        app(TenantUserSyncService::class)->syncAllTenantsToLandlord();

        $tenantId = (string) ($this->pageFilters['tenantId'] ?? '');

        $activeTenantCount = Tenant::query()->where('status', 'active')->count();
        $tenantOrganizationIds = $tenantId !== ''
            ? \App\Models\Organization::query()->where('tenant_id', $tenantId)->pluck('id')->all()
            : [];
        $totalUserCount = $tenantId !== ''
            ? User::query()->whereIn('organization_id', $tenantOrganizationIds)->count()
            : User::query()->count();
        $events24h = PlatformAuditLog::query()
            ->when($tenantId !== '', fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $events7d = PlatformAuditLog::query()
            ->when($tenantId !== '', fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return [
            Stat::make('Active Tenants', number_format((int) $activeTenantCount))
                ->description('Currently operational organizations')
                ->color('primary'),
            Stat::make('Total Users', number_format((int) $totalUserCount))
                ->description('Across all tenants')
                ->color('success'),
            Stat::make('Events (24h)', number_format((int) $events24h))
                ->description('Recent platform activity')
                ->color('info'),
            Stat::make('Events (7d)', number_format((int) $events7d))
                ->description('Weekly usage pulse')
                ->color('warning'),
        ];
    }
}
