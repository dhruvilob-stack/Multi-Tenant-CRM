<?php

namespace App\Filament\Resources\Shop\Categories\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Details')
                    ->schema([
                        TextEntry::make('name')->weight('bold'),
                        TextEntry::make('parent.name')
                            ->label('Parent Category')
                            ->state(fn ($record): string => $record->parent?->name ?: 'Self')
                            ->badge()
                            ->color(fn ($record): string => $record->parent_id ? 'gray' : 'primary'),
                        IconEntry::make('is_visible')
                            ->label('Visible'),
                        TextEntry::make('products_count')
                            ->counts('products')
                            ->label('Products'),
                        TextEntry::make('inventories_count')
                            ->counts('inventories')
                            ->label('Inventory Rows'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make('Description')
                    ->schema([
                        TextEntry::make('description')
                            ->markdown()
                            ->placeholder('No description added yet.'),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
