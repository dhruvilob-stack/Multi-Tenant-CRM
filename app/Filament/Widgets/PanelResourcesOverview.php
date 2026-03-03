<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Support\UserRole;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PanelResourcesOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Resources Overview';

    protected function getColumns(): int
    {
        return 4;
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $stats = [];
        $revenue = $this->resolveRevenueTotal();
        $paid = $this->resolvePaidRevenueTotal();
        $stats[] = Stat::make('Revenue (All Invoices)', '$'.number_format($revenue, 2))
            ->description('Collected: $'.number_format($paid, 2))
            ->color('success')
            ->icon('heroicon-o-banknotes');

        $panel = Filament::getCurrentPanel();

        if (! $panel) {
            return $stats;
        }

        $colors = ['primary', 'success', 'info', 'warning', 'danger'];
        $colorIndex = 0;

        foreach ($panel->getResources() as $resource) {
            if (! is_string($resource) || ! class_exists($resource) || ! is_subclass_of($resource, Resource::class)) {
                continue;
            }

            if (method_exists($resource, 'canAccess') && ! $resource::canAccess()) {
                continue;
            }

            try {
                $count = $resource::getEloquentQuery()->count();
            } catch (\Throwable) {
                continue;
            }

            $label = (string) ($resource::getNavigationLabel() ?: $resource::getPluralModelLabel());

            $stats[] = Stat::make($label, number_format((int) $count))
                ->description('Total records')
                ->color($colors[$colorIndex % count($colors)])
                ->icon('heroicon-o-rectangle-stack');

            $colorIndex++;
        }

        return $stats;
    }

    private function resolveRevenueTotal(): float
    {
        $query = $this->baseRevenueQuery()
            ->where('status', '!=', 'cancelled');

        return (float) $query->sum('grand_total');
    }

    private function resolvePaidRevenueTotal(): float
    {
        $query = $this->baseRevenueQuery()
            ->where('status', 'paid');

        return (float) $query->sum('received_amount');
    }

    private function baseRevenueQuery()
    {
        $user = auth()->user();
        $query = Invoice::query();

        if (! $user || $user->role === UserRole::SUPER_ADMIN) {
            return $query;
        }

        $orgId = (int) $user->organization_id;

        return $query->where(function ($q) use ($orgId): void {
            $q->whereHas('quotation.vendor', fn ($x) => $x->where('organization_id', $orgId))
                ->orWhereHas('order.vendor', fn ($x) => $x->where('organization_id', $orgId))
                ->orWhereHas('assignee', fn ($x) => $x->where('organization_id', $orgId));
        });
    }
}
