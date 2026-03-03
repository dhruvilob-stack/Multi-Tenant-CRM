<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\Inventory;
use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class StockMovements extends Page
{
    protected string $view = 'filament.pages.stock-movements';
    protected static ?string $slug = 'inventory/movements';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    public array $movements = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.stock_movements.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::ORG_ADMIN,
            UserRole::MANUFACTURER,
            UserRole::DISTRIBUTOR,
            UserRole::VENDOR,
        ], true);
    }

    private function loadData(): void
    {
        $user = auth()->user();

        $inventoryIds = Inventory::query()
            ->whereHas('product.manufacturer', fn (Builder $q) => $q->where('organization_id', $user?->organization_id))
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();

        if ($inventoryIds === []) {
            $this->movements = [];
            return;
        }

        $this->movements = AuditLog::query()
            ->where('auditable_type', Inventory::class)
            ->whereIn('auditable_id', $inventoryIds)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'inventory_id' => $log->auditable_id,
                'event' => $log->event,
                'performed_by' => $log->performed_by,
                'performed_role' => $log->performed_role,
                'ip' => $log->ip_address,
                'at' => optional($log->created_at)?->toDateTimeString(),
            ])
            ->all();
    }
}
