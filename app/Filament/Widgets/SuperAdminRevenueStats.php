<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Support\SystemSettings;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class SuperAdminRevenueStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    protected function getStats(): array
    {
        $startDate = filled($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])->startOfDay()
            : null;
        $endDate = filled($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])->endOfDay()
            : null;
        $organizationName = filled($this->pageFilters['organizationId'] ?? null)
            ? (string) \App\Models\Organization::query()->whereKey((int) $this->pageFilters['organizationId'])->value('name')
            : null;

        $query = Invoice::query()
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
            ->when(filled($organizationName), fn ($q) => $q->where('organisation_name', $organizationName));

        $billed = (float) (clone $query)->where('status', '!=', 'cancelled')->sum('grand_total');
        $collected = (float) (clone $query)->where('status', 'paid')->sum('received_amount');
        $outstanding = max($billed - $collected, 0);
        $paidInvoices = (int) (clone $query)->where('status', 'paid')->count();
        $currency = SystemSettings::BASE_CURRENCY;

        return [
            Stat::make('Billed Revenue', Number::currency($billed, $currency))
                ->description('Total invoice value')
                ->color('primary'),
            Stat::make('Collected Revenue', Number::currency($collected, $currency))
                ->description('Paid invoices')
                ->color('success'),
            Stat::make('Outstanding', Number::currency($outstanding, $currency))
                ->description('Pending collection')
                ->color('warning'),
            Stat::make('Paid Invoices', number_format($paidInvoices))
                ->description('Settled invoice count')
                ->color('info'),
        ];
    }
}

