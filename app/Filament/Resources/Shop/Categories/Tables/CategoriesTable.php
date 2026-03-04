<?php

namespace App\Filament\Resources\Shop\Categories\Tables;

use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),
                TextColumn::make('parent.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->sortable(),
                TextColumn::make('inventories_count')
                    ->counts('inventories')
                    ->label('Inventory Rows')
                    ->sortable(),
                IconColumn::make('is_visible')
                    ->label('Visibility')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last modified at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('toggle_visibility')
                    ->link()
                    ->icon(Heroicon::Eye)
                    ->color('gray')
                    ->label(fn(Category $record): string => $record->is_visible ? 'Hide' : 'Show')
                    ->action(fn(Category $record) => $record->update(['is_visible' => !$record->is_visible])),
                EditAction::make(),
            ]);

    }
}
