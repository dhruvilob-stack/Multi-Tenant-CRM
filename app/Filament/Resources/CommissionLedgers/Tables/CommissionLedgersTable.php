<?php

namespace App\Filament\Resources\CommissionLedgers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommissionLedgersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice.invoice_number')->label('Invoice'),
                TextColumn::make('from_role')->badge(),
                TextColumn::make('to_role')->badge(),
                TextColumn::make('commission_type'),
                TextColumn::make('commission_rate'),
                TextColumn::make('basis_amount')->money('USD'),
                TextColumn::make('commission_amount')->money('USD'),
                TextColumn::make('status')->badge(),
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
