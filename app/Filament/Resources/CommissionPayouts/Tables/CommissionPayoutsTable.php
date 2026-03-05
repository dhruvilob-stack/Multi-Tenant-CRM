<?php

namespace App\Filament\Resources\CommissionPayouts\Tables;

use App\Models\CommissionPayout;
use App\Services\CommissionPayoutService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommissionPayoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payout_number')
                    ->label('Payout #')
                    ->searchable(),
                TextColumn::make('user.name')->label('Partner'),
                TextColumn::make('user.role')->label('Role')->badge(),
                TextColumn::make('user.partnerWallet.pending_balance')
                    ->label('Wallet Pending')
                    ->money('USD'),
                TextColumn::make('user.partnerWallet.total_paid')
                    ->label('Wallet Paid')
                    ->money('USD'),
                TextColumn::make('amount')->money(fn (CommissionPayout $record): string => (string) ($record->currency ?: 'USD')),
                TextColumn::make('status')->badge(),
                TextColumn::make('payment_method')->badge(),
                TextColumn::make('reference'),
                TextColumn::make('paid_at')->date(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('markCompleted')
                        ->label('Mark Completed')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn (CommissionPayout $record): bool => (string) $record->status !== 'completed')
                        ->requiresConfirmation()
                        ->action(function (CommissionPayout $record): void {
                            app(CommissionPayoutService::class)->markCompleted($record);
                            Notification::make()->success()->title('Payout completed and ledger updated')->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
