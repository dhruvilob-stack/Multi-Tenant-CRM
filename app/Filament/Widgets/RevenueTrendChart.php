<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Support\UserRole;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueTrendChart extends ChartWidget
{
    protected ?string $heading = 'Revenue Trend';
    protected ?string $description = 'Monthly trend for billed and collected revenue (last 12 months).';
    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $months = collect(range(11, 0))->map(fn (int $shift): string => now()->subMonths($shift)->format('Y-m'))
            ->push(now()->format('Y-m'))
            ->values();

        $billed = $this->baseInvoiceQuery()
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(grand_total) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $collected = $this->baseInvoiceQuery()
            ->where('status', 'paid')
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(received_amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return [
            'labels' => $months->map(fn (string $ym): string => Carbon::createFromFormat('Y-m', $ym)->format('M Y'))->all(),
            'datasets' => [
                [
                    'label' => 'Billed Revenue',
                    'data' => $months->map(fn (string $ym): float => round((float) ($billed[$ym] ?? 0), 2))->all(),
                    'borderColor' => 'rgb(14, 116, 144)',
                    'backgroundColor' => 'rgba(14, 116, 144, 0.2)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Collected Revenue',
                    'data' => $months->map(fn (string $ym): float => round((float) ($collected[$ym] ?? 0), 2))->all(),
                    'borderColor' => 'rgb(22, 163, 74)',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.15)',
                    'fill' => false,
                    'tension' => 0.35,
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
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
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
