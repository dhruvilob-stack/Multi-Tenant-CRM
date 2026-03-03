<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Quotation;
use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ReportsSales extends Page
{
    protected string $view = 'filament.pages.reports-sales';
    protected static ?string $slug = 'reports/sales';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChartBar;

    public array $stats = [];
    public array $recentOrders = [];

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
        return __('filament.admin.pages.reports_sales.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::ORG_ADMIN,
            UserRole::MANUFACTURER,
            UserRole::DISTRIBUTOR,
        ], true);
    }

    private function loadData(): void
    {
        $this->stats = [
            'orders_total' => $this->ordersQuery()->count(),
            'orders_delivered' => (clone $this->ordersQuery())->where('status', 'delivered')->count(),
            'quotations_total' => $this->quotationsQuery()->count(),
            'quotations_converted' => (clone $this->quotationsQuery())->where('status', 'converted')->count(),
            'invoices_total_amount' => round((float) $this->invoicesQuery()->sum('grand_total'), 2),
            'invoices_paid_amount' => round((float) (clone $this->invoicesQuery())->where('status', 'paid')->sum('received_amount'), 2),
        ];

        $this->recentOrders = $this->ordersQuery()
            ->with(['vendor:id,name', 'consumer:id,name'])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Order $order): array => [
                'order_number' => $order->order_number,
                'vendor' => $order->vendor?->name,
                'consumer' => $order->consumer?->name,
                'status' => $order->status,
                'total' => (float) $order->total_amount,
                'created_at' => optional($order->created_at)?->toDateTimeString(),
            ])
            ->all();
    }

    private function ordersQuery(): Builder
    {
        $user = auth()->user();

        $query = Order::query()->whereHas('vendor', fn (Builder $q) => $q->where('organization_id', $user?->organization_id));

        if ($user?->role === UserRole::DISTRIBUTOR) {
            $query->whereHas('vendor', fn (Builder $q) => $q->where('parent_id', $user->id));
        }

        return $query;
    }

    private function quotationsQuery(): Builder
    {
        $user = auth()->user();

        $query = Quotation::query()->whereHas('vendor', fn (Builder $q) => $q->where('organization_id', $user?->organization_id));

        if ($user?->role === UserRole::DISTRIBUTOR) {
            $query->where('distributor_id', $user->id);
        }

        return $query;
    }

    private function invoicesQuery(): Builder
    {
        $user = auth()->user();

        $query = Invoice::query()->whereHas('quotation.vendor', fn (Builder $q) => $q->where('organization_id', $user?->organization_id));

        if ($user?->role === UserRole::DISTRIBUTOR) {
            $query->whereHas('quotation', fn (Builder $q) => $q->where('distributor_id', $user->id));
        }

        return $query;
    }
}
