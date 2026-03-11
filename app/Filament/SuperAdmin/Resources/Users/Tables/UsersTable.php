<?php

namespace App\Filament\SuperAdmin\Resources\Users\Tables;

use App\Services\TenantUserDeletionService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->badge(),
                TextColumn::make('organization.name')->label('Organization')->sortable(),
                TextColumn::make('customRole.name')->label('Custom Role')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('invitation_accepted_at')->label('Invitation Accepted')->dateTime(),
                TextColumn::make('created_at')->dateTime()->sortable(),
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
                    DeleteBulkAction::make()
                        ->action(function ($records): void {
                            app(TenantUserDeletionService::class)->deleteMany($records);
                        }),
                ]),
            ]);
    }
}
