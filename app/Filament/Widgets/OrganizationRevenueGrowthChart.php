<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use App\Models\Invoice;
use App\Models\Organization;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class OrganizationRevenueGrowthChart extends ChartWidget
{
    use InteractsWithPageFilters;
    use ResolvesPanelResourceAccess;

    protected ?string $heading = 'Organization-wise Revenue Growth';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    protected function getData(): array
    {
        $startDate = filled($this->pageFilters['startDate'] ?? null)
            ? Carbon::parse($this->pageFilters['startDate'])->startOfMonth()
            : now()->subMonths(11)->startOfMonth();
        $endDate = filled($this->pageFilters['endDate'] ?? null)
            ? Carbon::parse($this->pageFilters['endDate'])->endOfMonth()
            : now();

        $months = collect();
        $cursor = $startDate->copy()->startOfMonth();
        while ($cursor->lte($endDate)) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }

        $organizationFilter = $this->pageFilters['organizationId'] ?? null;

        $invoices = Invoice::query()
            ->select(['organisation_name', 'grand_total', 'created_at'])
            ->where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->when(filled($organizationFilter), fn ($q) => $q->where('organisation_name', Organization::query()->whereKey((int) $organizationFilter)->value('name')))
            ->get();

        $topOrganizations = $invoices
            ->groupBy(fn ($invoice): string => (string) ($invoice->organisation_name ?: 'Unassigned'))
            ->map(fn ($rows): float => (float) $rows->sum('grand_total'))
            ->sortDesc()
            ->take(5)
            ->keys();

        $palette = ['#3b82f6', '#22c55e', '#f59e0b', '#8b5cf6', '#ef4444'];

        $datasets = [];
        foreach ($topOrganizations->values() as $index => $orgName) {
            $series = [];

            foreach ($months as $month) {
                $monthKey = $month->format('Y-m');

                $series[] = round((float) $invoices
                    ->filter(fn ($invoice): bool => (string) ($invoice->organisation_name ?: 'Unassigned') === $orgName)
                    ->filter(fn ($invoice): bool => $invoice->created_at?->format('Y-m') === $monthKey)
                    ->sum('grand_total'), 2);
            }

            $datasets[] = [
                'label' => $orgName,
                'data' => $series,
                'borderColor' => $palette[$index % count($palette)],
                'backgroundColor' => $palette[$index % count($palette)],
                'tension' => 0.35,
                'fill' => false,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $months->map(fn ($month): string => $month->format('M Y'))->all(),
        ];
    }
}
