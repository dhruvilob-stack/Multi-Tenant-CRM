<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantHealthStats extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    protected function getStats(): array
    {
        $total = Tenant::query()->count();
        $active = Tenant::query()->where('status', 'active')->count();
        $inactive = Tenant::query()->where('status', 'inactive')->count();
        $withDatabase = Tenant::query()->whereNotNull('database')->where('database', '!=', '')->count();

        return [
            Stat::make('Total Tenants', number_format((int) $total))
                ->description('Registered organizations')
                ->color('primary'),
            Stat::make('Active Tenants', number_format((int) $active))
                ->description('Live and accessible')
                ->color('success'),
            Stat::make('Inactive Tenants', number_format((int) $inactive))
                ->description('Suspended or paused')
                ->color('warning'),
            Stat::make('DB Configured', number_format((int) $withDatabase))
                ->description('Provisioned tenant databases')
                ->color('info'),
        ];
    }
}

