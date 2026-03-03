<?php

namespace App\Filament\Resources\Inventories\Schemas;

use App\Models\Product;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->options(Product::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('owner_id')
                    ->label('Owner')
                    ->options(User::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Hidden::make('owner_type')
                    ->default(User::class)
                    ->dehydrated(true),
                TextInput::make('sku')
                    ->label('SKU (Stock Keeping Unit)')
                    ->required(),
                TextInput::make('barcode')
                    ->label('Barcode (ISBN, UPC, GTIN, etc.)'),
                TextInput::make('quantity_available')->numeric()->default(0)->required(),
                TextInput::make('security_stock')->numeric()->default(0)->required(),
                TextInput::make('quantity_reserved')->numeric()->default(0)->required(),
                TextInput::make('unit_price')
                    ->label('Inventory price')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('discount_percent')
                    ->label('Discount %')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100),
                TextInput::make('warehouse_location'),
            ]);
    }
}
