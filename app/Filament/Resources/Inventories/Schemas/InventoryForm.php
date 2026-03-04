<?php

namespace App\Filament\Resources\Inventories\Schemas;

use App\Models\Product;
use App\Models\User;
use App\Support\AccessMatrix;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class InventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        $authUser = auth()->user();

        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Product (Optional)')
                    ->relationship(
                        'product',
                        'name',
                        fn (Builder $query) => $query->when(
                            $authUser && ! AccessMatrix::isSuper($authUser),
                            fn (Builder $scoped) => $scoped->whereHas(
                                'manufacturer',
                                fn (Builder $manufacturer) => $manufacturer->where('organization_id', $authUser->organization_id),
                            ),
                        ),
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Create inventory first without product. Later map it from Shop > Products using "Reference Inventory Record".'),
                Select::make('owner_id')
                    ->label('Owner')
                    ->options(
                        AccessMatrix::scopeOrganization(
                            User::query()->orderBy('name'),
                            $authUser,
                        )->pluck('name', 'id'),
                    )
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
