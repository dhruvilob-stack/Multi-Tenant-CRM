<?php

namespace App\Filament\Resources\MarginCommissions\Tables;

use App\Support\UserRole;
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
                TextColumn::make('rule_type')
                    ->label('Rule Type')
                    ->badge(),
                TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable(),
                TextColumn::make('from_role')
                    ->label('Role')
                    ->badge(),
                TextColumn::make('commission_type'),
                TextColumn::make('commission_value'),
                TextColumn::make('is_active')
                    ->label('Active')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->role === UserRole::ORG_ADMIN),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->role === UserRole::ORG_ADMIN),
                ]),
            ]);
    }
}
