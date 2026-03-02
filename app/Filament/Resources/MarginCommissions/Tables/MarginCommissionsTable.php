<?php

namespace App\Filament\Resources\MarginCommissions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MarginCommissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('Product'),
                TextColumn::make('category.name')->label('Category'),
                TextColumn::make('from_role')->badge(),
                TextColumn::make('to_role')->badge(),
                TextColumn::make('commission_type'),
                TextColumn::make('commission_value'),
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
