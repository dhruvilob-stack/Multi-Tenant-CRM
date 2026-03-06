<?php

namespace App\Filament\Resources\Shop\Products\Schemas;

use App\Support\SystemSettings;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->schema([
                        TextEntry::make('name')
                            ->weight('bold'),
                        TextEntry::make('slug'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('manufacturer.name')
                            ->label('Brand / Manufacturer')
                            ->placeholder('No manufacturer'),
                        TextEntry::make('category.name')
                            ->placeholder('No category'),
                        TextEntry::make('sku')->label('SKU'),
                        TextEntry::make('barcode')->placeholder('No barcode'),
                    ])
                    ->columns(2)
                    ->columnSpan(4),

                Section::make('Pricing & Inventory')
                    ->schema([
                        TextEntry::make('price')->money(fn (): string => SystemSettings::currencyForCurrentUser()),
                        TextEntry::make('old_price')->label('Compare at price')->money(fn (): string => SystemSettings::currencyForCurrentUser()),
                        TextEntry::make('cost')->money(fn (): string => SystemSettings::currencyForCurrentUser()),
                        TextEntry::make('qty')->label('Quantity'),
                        TextEntry::make('security_stock'),
                    ])
                    ->columns(2)
                    ->columnSpan(4),

                Section::make('Visibility & Publishing')
                    ->schema([
                        IconEntry::make('is_visible')->label('Visibility'),
                        IconEntry::make('featured'),
                        IconEntry::make('backorder'),
                        IconEntry::make('requires_shipping'),
                        TextEntry::make('published_at')->date(),
                    ])
                    ->columns(2)
                    ->columnSpan(4),

                Section::make('Description')
                    ->schema([
                        TextEntry::make('description')
                            ->markdown()
                            ->placeholder('No description added yet.'),
                    ])
                    ->columnSpanFull(),

                Section::make('Media Gallery')
                    ->schema([
                        ViewEntry::make('images')
                            ->label('')
                            ->view('filament.infolists.product-image-gallery'),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(12);
    }
}
