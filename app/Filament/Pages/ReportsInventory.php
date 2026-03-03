<?php

namespace App\Filament\Pages;

use App\Models\Inventory;
use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ReportsInventory extends Page
{
    protected string $view = 'filament.pages.reports-inventory';
    protected static ?string $slug = 'reports/inventory';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChartBarSquare;

    public array $stats = [];
    public array $lowStock = [];

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
        return __('filament.admin.pages.reports_inventory.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::ORG_ADMIN], true);
    }

    private function loadData(): void
    {
        $query = $this->inventoryQuery();

        $this->stats = [
            'records' => (clone $query)->count(),
            'total_available' => round((float) (clone $query)->sum('quantity_available'), 3),
            'total_reserved' => round((float) (clone $query)->sum('quantity_reserved'), 3),
            'unique_products' => (clone $query)->distinct('product_id')->count('product_id'),
        ];

        $threshold = (float) (((array) (auth()->user()?->organization?->settings ?? []))['system']['low_stock_threshold'] ?? 5);

        $this->lowStock = (clone $query)
            ->with('product:id,name,sku')
            ->where('quantity_available', '<=', $threshold)
            ->orderBy('quantity_available')
            ->limit(20)
            ->get()
            ->map(fn (Inventory $inventory): array => [
                'product' => $inventory->product?->name,
                'sku' => $inventory->product?->sku,
                'available' => (float) $inventory->quantity_available,
                'reserved' => (float) $inventory->quantity_reserved,
                'location' => $inventory->warehouse_location,
            ])
            ->all();
    }

    private function inventoryQuery(): Builder
    {
        $user = auth()->user();

        return Inventory::query()
            ->whereHas('product.manufacturer', fn (Builder $q) => $q->where('organization_id', $user?->organization_id));
    }
}
