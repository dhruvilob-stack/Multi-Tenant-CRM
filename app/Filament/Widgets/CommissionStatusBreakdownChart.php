<?php

namespace App\Filament\Widgets;

use App\Models\CommissionLedger;
use App\Support\UserRole;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class CommissionStatusBreakdownChart extends ChartWidget
{
    protected ?string $heading = 'Commission Status Breakdown';
    protected ?string $description = 'How much commission is in each stage.';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::ORG_ADMIN;
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $query = $this->ledgerQuery();

        $accrued = (float) (clone $query)->where('status', 'accrued')->sum('commission_amount');
        $approved = (float) (clone $query)->where('status', 'approved')->sum('commission_amount');
        $paid = (float) (clone $query)->sum('paid_amount');
        $rejected = (float) (clone $query)->where('status', 'rejected')->sum('commission_amount');

        return [
            'labels' => ['Accrued', 'Approved', 'Paid', 'Rejected'],
            'datasets' => [
                [
                    'label' => 'Amount',
                    'data' => [round($accrued, 2), round($approved, 2), round($paid, 2), round($rejected, 2)],
                    'backgroundColor' => ['#f59e0b', '#3b82f6', '#22c55e', '#ef4444'],
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

}
