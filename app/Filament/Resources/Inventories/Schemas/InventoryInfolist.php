<?php

namespace App\Filament\Resources\Inventories\Schemas;

use App\Support\SystemSettings;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Inventory Item')
                    ->schema([
                        TextEntry::make('product.name')->label('Product'),
                        TextEntry::make('owner.name')->label('Owner'),
                        TextEntry::make('warehouse_location')->placeholder('No warehouse location'),
                        TextEntry::make('updated_at')->dateTime()->placeholder('-'),
                    ])
                    ->columns(2)
                    ->columnSpan(4),

                Section::make('Stock Mapping')
                    ->schema([
                        TextEntry::make('sku')->label('SKU'),
                        TextEntry::make('barcode')->placeholder('No barcode'),
                        TextEntry::make('quantity_available')->label('Quantity')->numeric(),
                        TextEntry::make('security_stock')->numeric(),
                        TextEntry::make('quantity_reserved')->numeric(),
                    ])
                    ->columns(2)
                    ->columnSpan(4),

                Section::make('Pricing')
                    ->schema([
                        TextEntry::make('unit_price')->label('Inventory Price')->money(fn (): string => SystemSettings::currencyForCurrentUser()),
                        TextEntry::make('discount_percent')->label('Discount')->suffix('%'),
                        TextEntry::make('product.price')->label('Product Selling Price')->money(fn (): string => SystemSettings::currencyForCurrentUser()),
                        TextEntry::make('product.old_price')->label('Compare At Price')->money(fn (): string => SystemSettings::currencyForCurrentUser()),
                        IconEntry::make('product.is_visible')
                            ->label('Product Visible')
                            ->boolean(),
                    ])
                    ->columns(2)
                    ->columnSpan(4),
            ])
            ->columns(12);
    }
}
