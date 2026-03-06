<?php

namespace App\Filament\Widgets;

use App\Models\CommissionLedger;
use App\Models\CommissionPayout;
use App\Support\UserRole;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class CommissionReportStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::ORG_ADMIN;
    }

    protected function getStats(): array
    {
        $ledger = $this->ledgerQuery();
        $payouts = $this->payoutQuery();

        $totalEntries = (clone $ledger)->count();
        $totalEarned = (float) (clone $ledger)
            ->whereNotIn('status', ['rejected'])
            ->whereIn('from_role', [UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, UserRole::VENDOR])
            ->sum('commission_amount');
        $completedPayoutsQuery = Schema::hasColumn('commission_payouts', 'status')
            ? (clone $payouts)->where('status', 'completed')
            : (clone $payouts);
        $totalPaid = (float) (clone $completedPayoutsQuery)->sum('amount');
        $pending = max($totalEarned - $totalPaid, 0);
        $completedPayouts = (clone $completedPayoutsQuery)->count();

        return [
            Stat::make('Total Commission Earned', '$'.number_format($totalEarned, 2))
                ->description('All generated commission from successful invoices')
                ->color('success'),
            Stat::make('Pending To Pay', '$'.number_format($pending, 2))
                ->description('Unpaid commission liability')
                ->color($pending > 0 ? 'warning' : 'success'),
            Stat::make('Total Paid', '$'.number_format($totalPaid, 2))
                ->description($completedPayouts.' payouts completed')
                ->color('primary'),
            Stat::make('Ledger Entries', (string) $totalEntries)
                ->description('Commission transactions recorded')
                ->color('gray'),
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
