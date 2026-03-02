<?php

namespace App\Filament\Resources\MarginCommissions\Schemas;

use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MarginCommissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->options(Product::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('category_id')
                    ->options(Category::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Select::make('from_role')->options([
                    'manufacturer' => 'Manufacturer',
                    'distributor' => 'Distributor',
                    'vendor' => 'Vendor',
                ])->required(),
                Select::make('to_role')->options([
                    'distributor' => 'Distributor',
                    'vendor' => 'Vendor',
                    'consumer' => 'Consumer',
                ])->required(),
                Select::make('commission_type')
                    ->options(['percentage' => 'Percentage', 'fixed' => 'Fixed'])
                    ->default('percentage')
                    ->required(),
                TextInput::make('commission_value')->numeric()->required()->default(0),
            ]);
    }
}
