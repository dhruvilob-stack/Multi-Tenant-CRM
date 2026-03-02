<?php

namespace App\Filament\Resources\Inventories\Schemas;

use App\Models\Product;
use App\Models\User;
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
                TextInput::make('owner_type')->default('App\\Models\\User')->required(),
                TextInput::make('quantity_available')->numeric()->default(0)->required(),
                TextInput::make('quantity_reserved')->numeric()->default(0)->required(),
                TextInput::make('warehouse_location'),
            ]);
    }
}
