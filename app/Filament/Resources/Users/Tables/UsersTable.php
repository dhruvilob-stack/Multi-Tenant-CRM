<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Support\ResourceDataExchange;
use App\Models\User;
use App\Support\UserRole;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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
                TextColumn::make('sr_no')
                    ->label('S.No')
                    ->rowIndex(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('contact_email')->label('Actual Gmail')->searchable(),
                TextColumn::make('role')->badge(),
                TextColumn::make('customRole.name')->label('Custom Role')->badge(),
                TextColumn::make('organization.name')->label('Organization'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('login_as')
                    ->label('Login As')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (User $record): string => route('tenant.impersonate.start', [
                        'tenant' => (string) request()->route('tenant'),
                        'user' => $record,
                    ]))
                    ->visible(fn (User $record): bool => auth('tenant')->user()?->role === UserRole::ORG_ADMIN
                        && (int) $record->id !== (int) auth('tenant')->id()
                        && in_array((string) $record->role, [UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, UserRole::VENDOR, UserRole::CONSUMER], true)),
                DeleteAction::make()
                    ->visible(fn (): bool => in_array(auth('tenant')->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true)),
            ])
            ->toolbarActions([
                ...ResourceDataExchange::toolbarActions('users'),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => in_array(auth('tenant')->user()?->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true)),
                ]),
            ]);
    }
}
