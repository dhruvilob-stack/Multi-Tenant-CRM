<?php

namespace App\Filament\Resources\Inventories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->placeholder('Unmapped'),
                TextColumn::make('sku')->label('SKU')->searchable(),
                TextColumn::make('barcode')->searchable()->toggleable(),
                TextColumn::make('owner.name')->label('Owner'),
                TextColumn::make('quantity_available')->numeric(),
                TextColumn::make('security_stock')->numeric(),
                TextColumn::make('quantity_reserved')->numeric(),
                TextColumn::make('unit_price')->money('USD'),
                TextColumn::make('discount_percent')->suffix('%'),
                TextColumn::make('warehouse_location'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
