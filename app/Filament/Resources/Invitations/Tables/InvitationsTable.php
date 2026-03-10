<?php

namespace App\Filament\Resources\Invitations\Tables;

use App\Filament\Resources\Invitations\InvitationResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvitationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invitee_email')->searchable(),
                TextColumn::make('role')->badge(),
                TextColumn::make('inviter.name')->label('Inviter'),
                TextColumn::make('organization.name')->label('Organization'),
                TextColumn::make('expires_at')->dateTime(),
                TextColumn::make('accepted_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record): bool => InvitationResource::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
