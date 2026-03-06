<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Widgets\InventoryLowStockTable;
use App\Filament\Widgets\InventoryReportKpiStats;
use App\Filament\Widgets\InventoryStockStatusChart;
use App\Models\Inventory;
use App\Support\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ReportsInventory extends Page
{
    use HasFiltersForm;

    protected string $view = 'filament.pages.reports-inventory';
    protected static ?string $slug = 'reports/inventory';
    protected static ?string $title = 'Inventory Dashboard';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedChartBarSquare;

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

    public function persistsFiltersInSession(): bool
    {
        return false;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('productCategory')
                            ->label('Product category')
                            ->options(fn (): array => $this->getCategoryOptions())
                            ->searchable(),
                        Select::make('warehouseLocation')
                            ->label('Warehouse location')
                            ->options(fn (): array => $this->getWarehouseOptions())
                            ->searchable(),
                        Toggle::make('onlyLowStock')
                            ->label('Only low stock')
                            ->default(false)
                            ->inline(false),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InventoryReportKpiStats::class,
            InventoryStockStatusChart::class,
            InventoryLowStockTable::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function getCategoryOptions(): array
    {
        if (! CategoryResource::canViewAny()) {
            return [];
        }

        return CategoryResource::getEloquentQuery()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function getWarehouseOptions(): array
    {
        return Inventory::query()
            ->whereNotNull('warehouse_location')
            ->where('warehouse_location', '!=', '')
            ->distinct()
            ->orderBy('warehouse_location')
            ->pluck('warehouse_location', 'warehouse_location')
            ->all();
    }
}
