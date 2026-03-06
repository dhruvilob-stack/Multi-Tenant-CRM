<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use App\Support\SystemSettings;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class SalesReportKpiStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    use ResolvesPanelResourceAccess;

    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return static::canUseAnyResource([
            OrderResource::class,
            QuotationResource::class,
            InvoiceResource::class,
        ]);
    }

    protected function getStats(): array
    {
        $startDate = filled($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])->startOfDay()
            : null;
        $endDate = filled($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])->endOfDay()
            : null;
        $orderStatuses = $this->pageFilters['orderStatuses'] ?? null;

        $orders = OrderResource::getEloquentQuery()
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
            ->when(filled($orderStatuses), fn ($q) => $q->whereIn('status', $orderStatuses));

        $quotations = QuotationResource::getEloquentQuery()
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate));

        $invoices = InvoiceResource::getEloquentQuery()
            ->where('status', '!=', 'cancelled')
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate));

        $totalOrders = (clone $orders)->count();
        $deliveredOrders = (clone $orders)->where('status', 'delivered')->count();
        $convertedQuotations = (clone $quotations)->where('status', 'converted')->count();
        $billedRevenue = (float) (clone $invoices)->sum('grand_total');
        $collectedRevenue = (float) (clone $invoices)->where('status', 'paid')->sum('received_amount');

        return [
            Stat::make('Total Orders', (string) $totalOrders)
                ->description('All orders in selected filters')
                ->color('primary'),
            Stat::make('Delivered Orders', (string) $deliveredOrders)
                ->description('Orders successfully delivered')
                ->color('success'),
            Stat::make('Converted Quotations', (string) $convertedQuotations)
                ->description('Quotations turned into confirmed deals')
                ->color('info'),
            Stat::make('Billed Revenue', Number::currency($billedRevenue, SystemSettings::currencyForCurrentUser()))
                ->description('Total invoiced amount')
                ->color('warning'),
            Stat::make('Collected Revenue', Number::currency($collectedRevenue, SystemSettings::currencyForCurrentUser()))
                ->description('Amount already received')
                ->color('success'),
        ];
    }
}
