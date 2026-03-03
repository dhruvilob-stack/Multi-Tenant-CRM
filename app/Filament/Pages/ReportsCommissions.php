<?php

namespace App\Filament\Pages;

use App\Models\CommissionLedger;
use App\Models\CommissionPayout;
use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ReportsCommissions extends Page
{
    protected string $view = 'filament.pages.reports-commissions';
    protected static ?string $slug = 'reports/commissions';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    public array $stats = [];
    public array $topEarners = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.reports');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.reports_commissions.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::ORG_ADMIN], true);
    }

    private function loadData(): void
    {
        $ledgerQuery = $this->ledgerQuery();
        $payoutQuery = $this->payoutQuery();

        $accrued = (float) (clone $ledgerQuery)->sum('commission_amount');
        $paidOut = (float) (clone $payoutQuery)->sum('amount');

        $this->stats = [
            'entries' => (clone $ledgerQuery)->count(),
            'accrued' => round($accrued, 2),
            'paid_out' => round($paidOut, 2),
            'payable' => round(max($accrued - $paidOut, 0), 2),
        ];

        $this->topEarners = (clone $ledgerQuery)
            ->with('toUser:id,name')
            ->selectRaw('to_user_id, SUM(commission_amount) as total_commission')
            ->groupBy('to_user_id')
            ->orderByDesc('total_commission')
            ->limit(10)
            ->get()
            ->map(fn (CommissionLedger $row): array => [
                'user' => $row->toUser?->name ?? 'N/A',
                'amount' => (float) $row->total_commission,
            ])
            ->all();
    }

    private function ledgerQuery(): Builder
    {
        $user = auth()->user();

        return CommissionLedger::query()
            ->whereHas('invoice.quotation.vendor', fn (Builder $q) => $q->where('organization_id', $user?->organization_id));
    }

    private function payoutQuery(): Builder
    {
        $user = auth()->user();

        return CommissionPayout::query()
            ->whereHas('user', fn (Builder $q) => $q->where('organization_id', $user?->organization_id));
    }
}
