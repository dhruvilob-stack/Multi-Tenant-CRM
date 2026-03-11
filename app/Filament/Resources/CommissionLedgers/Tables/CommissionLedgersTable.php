<?php

namespace App\Filament\Resources\CommissionLedgers\Tables;

use App\Models\CommissionLedger;
use App\Support\UserRole;
use App\Support\SystemSettings;
use App\Services\PartnerWalletService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
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
                TextColumn::make('basis_amount')->money(fn (): string => SystemSettings::currencyForCurrentUser()),
                TextColumn::make('commission_amount')->money(fn (): string => SystemSettings::currencyForCurrentUser()),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (): bool => auth('tenant')->user()?->role === UserRole::ORG_ADMIN),
                    Action::make('updateStatus')
                        ->label('Update Status')
                        ->icon('heroicon-o-check-badge')
                        ->visible(fn (): bool => auth('tenant')->user()?->role === UserRole::ORG_ADMIN)
                        ->form([
                            Select::make('status')
                                ->options([
                                    'accrued' => 'Accrued',
                                    'approved' => 'Approved',
                                    'paid' => 'Paid',
                                    'rejected' => 'Rejected',
                                ])
                                ->required(),
                        ])
                        ->fillForm(fn (CommissionLedger $record): array => [
                            'status' => (string) $record->status,
                        ])
                        ->action(function (CommissionLedger $record, array $data): void {
                            $status = (string) ($data['status'] ?? 'accrued');
                            $updates = ['status' => $status];

                            if ($status === 'paid') {
                                $updates['paid_amount'] = (float) $record->commission_amount;
                            }

                            $record->update($updates);

                            if (is_numeric($record->from_user_id) && (int) $record->from_user_id > 0) {
                                app(PartnerWalletService::class)->syncForUser((int) $record->from_user_id);
                            }

                            Notification::make()
                                ->success()
                                ->title('Commission status updated')
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth('tenant')->user()?->role === UserRole::ORG_ADMIN),
                ]),
            ]);
    }
}
