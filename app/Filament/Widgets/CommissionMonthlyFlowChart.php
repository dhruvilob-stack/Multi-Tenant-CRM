<?php

namespace App\Filament\Widgets;

use App\Models\CommissionLedger;
use App\Models\CommissionPayout;
use App\Support\UserRole;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class CommissionMonthlyFlowChart extends ChartWidget
{
    protected ?string $heading = 'Monthly Commission Flow';
    protected ?string $description = 'Compare generated commission vs paid payouts by month.';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 2;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::ORG_ADMIN;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))
            ->map(fn (int $shift): string => now()->subMonths($shift)->format('Y-m'))
            ->push(now()->format('Y-m'))
            ->values();

        $generated = $this->ledgerQuery()
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(commission_amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $paid = $this->payoutQuery()
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(5))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        return [
            'labels' => $months->map(fn (string $ym): string => Carbon::createFromFormat('Y-m', $ym)->format('M Y'))->all(),
            'datasets' => [
                [
                    'label' => 'Commission Generated',
                    'data' => $months->map(fn (string $ym): float => round((float) ($generated[$ym] ?? 0), 2))->all(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.75)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'Payout Completed',
                    'data' => $months->map(fn (string $ym): float => round((float) ($paid[$ym] ?? 0), 2))->all(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.75)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
            ],
        ];
    }

    private function ledgerQuery(): Builder
    {
        $orgId = (int) (auth()->user()?->organization_id ?? 0);

        return CommissionLedger::query()
            ->where(function (Builder $query) use ($orgId): void {
                $query
                    ->whereHas('invoice.quotation.vendor', fn (Builder $q) => $q->where('organization_id', $orgId))
                    ->orWhereHas('invoice.order.vendor', fn (Builder $q) => $q->where('organization_id', $orgId))
                    ->orWhereHas('fromUser', fn (Builder $q) => $q->where('organization_id', $orgId))
                    ->orWhereHas('toUser', fn (Builder $q) => $q->where('organization_id', $orgId));
            });
    }

    private function payoutQuery(): Builder
    {
        $orgId = (int) (auth()->user()?->organization_id ?? 0);
        $query = CommissionPayout::query();

        if (! Schema::hasColumn('commission_payouts', 'organization_id')) {
            return $query->whereHas('user', fn (Builder $q) => $q->where('organization_id', $orgId));
        }

        return $query
            ->where(function (Builder $query) use ($orgId): void {
                $query
                    ->where('organization_id', $orgId)
                    ->orWhere(function (Builder $legacy) use ($orgId): void {
                        $legacy
                            ->whereNull('organization_id')
                            ->whereHas('user', fn (Builder $q) => $q->where('organization_id', $orgId));
                    });
            });
    }
}
