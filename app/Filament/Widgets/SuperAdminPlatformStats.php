<?php

namespace App\Filament\Widgets;

use App\Filament\SuperAdmin\Resources\Organizations\OrganizationResource;
use App\Filament\SuperAdmin\Resources\Tenants\TenantResource;
use App\Filament\SuperAdmin\Resources\Users\UserResource;
use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use App\Models\Invoice;
use App\Support\SystemSettings;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class SuperAdminPlatformStats extends BaseWidget
{
    use ResolvesPanelResourceAccess;

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return static::canUseAnyResource([
            TenantResource::class,
            OrganizationResource::class,
            UserResource::class,
        ]);
    }

    protected function getStats(): array
    {
        $tenantCount = TenantResource::getEloquentQuery()->count();
        $orgCount = OrganizationResource::getEloquentQuery()->count();
        $userCount = UserResource::getEloquentQuery()->count();

        $totalRevenue = (float) Invoice::query()->where('status', '!=', 'cancelled')->sum('grand_total');
        $paidRevenue = (float) Invoice::query()->where('status', 'paid')->sum('received_amount');
        $currency = SystemSettings::currencyForCurrentUser();

        return [
            Stat::make('Tenants', number_format((int) $tenantCount))
                ->description('Total tenants')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('info'),
            Stat::make('Organizations', number_format((int) $orgCount))
                ->description('Total organizations')
                ->descriptionIcon('heroicon-o-building-library')
                ->color('primary'),
            Stat::make('Users', number_format((int) $userCount))
                ->description('Across all organizations')
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),
            Stat::make('Revenue', Number::currency($totalRevenue, $currency))
                ->description('Collected: '.Number::currency($paidRevenue, $currency))
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('warning'),
        ];
    }
}
