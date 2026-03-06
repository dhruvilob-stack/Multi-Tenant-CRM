<?php

namespace App\Filament\Widgets;

use App\Models\CommissionLedger;
use App\Support\UserRole;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class TopCommissionPartnersChart extends ChartWidget
{
    protected ?string $heading = 'Top Earning Partners';
    protected ?string $description = 'Partners with highest commission earnings.';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

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
        $top = $this->ledgerQuery()
            ->whereIn('from_role', [UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, UserRole::VENDOR])
            ->with('fromUser:id,name')
            ->selectRaw('from_user_id, SUM(commission_amount) as total_commission')
            ->groupBy('from_user_id')
            ->orderByDesc('total_commission')
            ->limit(5)
            ->get();

        return [
            'labels' => $top->map(fn (CommissionLedger $row): string => (string) ($row->fromUser?->name ?? 'N/A'))->all(),
            'datasets' => [
                [
                    'label' => 'Commission',
                    'data' => $top->map(fn (CommissionLedger $row): float => round((float) $row->total_commission, 2))->all(),
                    'backgroundColor' => 'rgba(14, 116, 144, 0.75)',
                    'borderColor' => 'rgb(14, 116, 144)',
                ],
            ],
        ];
    }

    protected function getOptions(): ?array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
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
