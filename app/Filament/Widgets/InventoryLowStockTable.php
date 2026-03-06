<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inventories\InventoryResource;
use App\Filament\Widgets\Concerns\ResolvesPanelResourceAccess;
use App\Models\Inventory;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class InventoryLowStockTable extends TableWidget
{
    use InteractsWithPageFilters;
    use ResolvesPanelResourceAccess;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return static::canUseResource(InventoryResource::class);
    }

    public function getHeading(): ?string
    {
        return 'Inventory Details';
    }

    public function getDescription(): ?string
    {
        return 'Interactive list for stock records. Search product names, sort columns, and inspect low stock quickly.';
    }

    public function table(Table $table): Table
    {
        $threshold = (float) (((array) (auth()->user()?->organization?->settings ?? []))['system']['low_stock_threshold'] ?? 5);

        return $table
            ->query(
                $this->baseInventoryQuery()
                    ->with(['product:id,name,sku']),
            )
            ->defaultSort('quantity_available')
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->placeholder('Unmapped')
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('quantity_available')
                    ->label('Available')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                TextColumn::make('quantity_reserved')
                    ->label('Reserved')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),
                TextColumn::make('security_stock')
                    ->label('Safety Stock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('warehouse_location')
                    ->label('Warehouse')
                    ->searchable()
                    ->sortable()
                    ->placeholder('N/A'),
                TextColumn::make('stock_health')
                    ->label('Stock Status')
                    ->state(function (Inventory $record) use ($threshold): string {
                        $available = (float) $record->quantity_available;
                        if ($available <= 0) {
                            return 'Out of stock';
                        }
                        if ($available <= $threshold) {
                            return 'Low stock';
                        }

                        return 'Healthy';
                    })
                    ->badge()
                    ->color(function (Inventory $record) use ($threshold): string {
                        $available = (float) $record->quantity_available;
                        if ($available <= 0) {
                            return 'danger';
                        }
                        if ($available <= $threshold) {
                            return 'warning';
                        }

                        return 'success';
                    }),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated Date')
                    ->dateTime('M d, Y h:i A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }

    private function baseInventoryQuery(): Builder
    {
        return InventoryResource::getEloquentQuery();
    }
}
