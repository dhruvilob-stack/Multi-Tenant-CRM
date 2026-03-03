<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\User;
use App\Support\UserRole;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CustomersRevenueBarChart extends ChartWidget
{
    protected ?string $heading = 'Customers vs Revenue';
    protected ?string $description = 'Monthly customer growth and billed revenue (last 6 months).';
    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $shift): string => now()->subMonths($shift)->format('Y-m'))
            ->push(now()->format('Y-m'))
            ->values();

        $customers = $this->baseCustomerQuery()
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $revenue = $this->baseInvoiceQuery()
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(grand_total) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return [
            'labels' => $months->map(fn (string $ym): string => Carbon::createFromFormat('Y-m', $ym)->format('M Y'))->all(),
            'datasets' => [
                [
                    'label' => 'New Customers',
                    'data' => $months->map(fn (string $ym): int => (int) ($customers[$ym] ?? 0))->all(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.85)',
                    'borderRadius' => 8,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Revenue',
                    'data' => $months->map(fn (string $ym): float => round((float) ($revenue[$ym] ?? 0), 2))->all(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.75)',
                    'borderRadius' => 8,
                    'yAxisID' => 'y1',
                ],
            ],
        ];
    }

    protected function getOptions(): ?array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Customers',
                    ],
                ],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue',
                    ],
                ],
            ],
        ];
    }

    private function baseCustomerQuery()
    {
        $query = User::query()->where('role', UserRole::CONSUMER);
        $user = auth()->user();

        if (! $user || $user->role === UserRole::SUPER_ADMIN) {
            return $query;
        }

        return $query->withoutGlobalScopes()->where('organization_id', $user->organization_id);
    }

    private function baseInvoiceQuery()
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

