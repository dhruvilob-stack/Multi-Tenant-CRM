<?php

namespace App\Filament\Resources\PartnerWallets\Tables;

use App\Filament\Resources\CommissionPayouts\CommissionPayoutResource;
use App\Models\PartnerWallet;
use App\Services\CommissionPayoutService;
use App\Services\PartnerWalletService;
use App\Support\UserRole;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class PartnerWalletsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Partner')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge(),
                TextColumn::make('total_earned')
                    ->label('Total Earned')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('total_paid')
                    ->label('Total Paid')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('pending_balance')
                    ->label('Pending Balance')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('available_balance')
                    ->label('Available Balance')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('last_synced_at')
                    ->label('Last Updated')
                    ->since(),
            ])
            ->defaultSort('pending_balance', 'desc')
            ->recordActions([
                ActionGroup::make([
                    Action::make('refreshWallet')
                        ->label('Refresh Balance')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (PartnerWallet $record): void {
                            app(PartnerWalletService::class)->syncForUser((int) $record->user_id);
                            Notification::make()->success()->title('Wallet refreshed')->send();
                        })
                        ->visible(fn (PartnerWallet $record): bool => self::canManageWallet($record)),
                    Action::make('requestPayout')
                        ->label('Request Payout')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->visible(fn (PartnerWallet $record): bool => self::canRequestPayout($record))
                        ->form([
                            \Filament\Forms\Components\TextInput::make('amount')
                                ->label('Request Amount')
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->default(fn (PartnerWallet $record): float => round((float) $record->available_balance, 2))
                                ->maxValue(fn (PartnerWallet $record): float => round((float) $record->available_balance, 2))
                                ->suffix('USD')
                                ->helperText(fn (PartnerWallet $record): string => 'Maximum requestable amount: $'.number_format((float) $record->available_balance, 2)),
                            \Filament\Forms\Components\Select::make('payment_method')
                                ->options([
                                    'bank_transfer' => 'Bank Transfer',
                                    'paypal' => 'PayPal',
                                    'stripe' => 'Stripe',
                                    'razorpay' => 'Razorpay',
                                ])
                                ->default('bank_transfer')
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('reference')
                                ->label('Reference (Optional)'),
                            \Filament\Forms\Components\Textarea::make('notes')
                                ->label('Request Notes')
                                ->placeholder('Any payout note for organization owner.')
                                ->columnSpanFull(),
                        ])
                        ->action(function (PartnerWallet $record, array $data): void {
                            $actor = auth()->user();
                            if (! $actor) {
                                return;
                            }

                            try {
                                $requestedAmount = (float) ($data['amount'] ?? 0);
                                $maxAvailable = round((float) $record->available_balance, 2);

                                if ($requestedAmount > $maxAvailable) {
                                    throw ValidationException::withMessages([
                                        'amount' => 'Requested payout amount cannot be more than available balance ($'.number_format($maxAvailable, 2).').',
                                    ]);
                                }

                                app(CommissionPayoutService::class)->create([
                                    'user_id' => $record->user_id,
                                    'amount' => $requestedAmount,
                                    'payment_method' => (string) ($data['payment_method'] ?? 'bank_transfer'),
                                    'reference' => (string) ($data['reference'] ?? ''),
                                    'notes' => (string) ($data['notes'] ?? ''),
                                    'status' => 'processing',
                                    'currency' => 'USD',
                                ], $actor);

                                Notification::make()
                                    ->success()
                                    ->title('Payout request submitted')
                                    ->body('Organization owner can now process this request from Payouts.')
                                    ->send();
                            } catch (ValidationException $exception) {
                                Notification::make()
                                    ->danger()
                                    ->title('Unable to request payout')
                                    ->body(collect($exception->errors())->flatten()->implode(' '))
                                    ->send();
                            }
                        }),
                    Action::make('openPayouts')
                        ->label('Open Payout Requests')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (): string => CommissionPayoutResource::getUrl('index'))
                        ->visible(fn (): bool => auth()->user()?->role === UserRole::ORG_ADMIN),
                ]),
            ]);
    }

    private static function canManageWallet(PartnerWallet $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true)) {
            return true;
        }

        return (int) $record->user_id === (int) $user->id;
    }

    private static function canRequestPayout(PartnerWallet $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (! in_array($user->role, [UserRole::MANUFACTURER, UserRole::DISTRIBUTOR, UserRole::VENDOR], true)) {
            return false;
        }

        return (int) $record->user_id === (int) $user->id && (float) $record->available_balance > 0;
    }
}
