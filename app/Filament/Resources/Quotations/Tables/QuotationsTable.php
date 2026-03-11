<?php

namespace App\Filament\Resources\Quotations\Tables;

use App\Models\Quotation;
use App\Services\QuotationWorkflowService;
use App\Support\QuotationStatus;
use App\Support\SystemSettings;
use App\Support\UserRole;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_number')
                    ->label('Quotation #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('distributor.name')
                    ->label('Distributor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Current Stage')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => self::statusColor($state))
                    ->description(fn (Quotation $record): string => self::statusDescription($record->status)),
                TextColumn::make('next_step')
                    ->label('Next Action')
                    ->state(fn (Quotation $record): string => self::nextActionLabel($record))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->money(fn (): string => SystemSettings::currencyForCurrentUser())
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date()
                    ->sortable(),
                TextColumn::make('order.order_number')
                    ->label('Created Order')
                    ->placeholder('Not converted yet')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (Quotation $record): bool => self::canEditQuotation($record)),
                    Action::make('send')
                        ->label('Send to Distributor')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->requiresConfirmation()
                        ->visible(fn (Quotation $record): bool => self::canSendQuotation($record))
                        ->action(function (Quotation $record): void {
                            self::runWorkflowAction(
                                fn () => app(QuotationWorkflowService::class)->send($record),
                                'Quotation sent to distributor',
                            );
                        }),
                    Action::make('counter_offer')
                        ->label('Send Counter Offer')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (Quotation $record): bool => self::canCounterOffer($record))
                        ->requiresConfirmation()
                        ->action(function (Quotation $record): void {
                            self::runWorkflowAction(
                                fn () => app(QuotationWorkflowService::class)->send($record),
                                'Counter offer sent to distributor',
                            );
                        }),
                    Action::make('negotiate')
                        ->label('Negotiate')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Quotation $record): bool => self::canNegotiateQuotation($record))
                        ->form([
                            TextInput::make('requested_qty')
                                ->label('Requested Quantity')
                                ->numeric()
                                ->minValue(1)
                                ->placeholder('Example: 25'),
                            TextInput::make('requested_unit_price')
                                ->label(fn (): string => 'Requested Price Per Unit ('.SystemSettings::currencyForCurrentUser().')')
                                ->numeric()
                                ->minValue(0)
                                ->placeholder('Example: 42'),
                            Textarea::make('message')
                                ->label('Negotiation Message')
                                ->placeholder('Ask for new quantity, price, or both in simple words.')
                                ->required(),
                        ])
                        ->action(function (Quotation $record, array $data): void {
                            self::runWorkflowAction(
                                function () use ($record, $data): void {
                                    $details = [];

                                    if (filled($data['requested_qty'] ?? null)) {
                                        $details[] = 'Requested Qty: '.$data['requested_qty'];
                                    }

                                    if (filled($data['requested_unit_price'] ?? null)) {
                                        $details[] = 'Requested Price/Unit: $'.$data['requested_unit_price'];
                                    }

                                    if (filled($data['message'] ?? null)) {
                                        $details[] = 'Message: '.trim((string) $data['message']);
                                    }

                                    $noteLine = '['.now()->toDateTimeString().'] Negotiation request - '.implode(' | ', $details);
                                    $existingNotes = (string) ($record->notes ?? '');

                                    $record->update([
                                        'notes' => trim($existingNotes.($existingNotes !== '' ? "\n" : '').$noteLine),
                                    ]);

                                    app(QuotationWorkflowService::class)->negotiate($record->refresh());
                                },
                                'Negotiation request sent to vendor',
                            );
                        }),
                    Action::make('confirm')
                        ->label('Confirm and Convert to Order')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Quotation $record): bool => self::canConfirmQuotation($record))
                        ->action(function (Quotation $record): void {
                            self::runWorkflowAction(
                                function () use ($record): void {
                                    $order = app(QuotationWorkflowService::class)->convertToOrder($record);
                                    Notification::make()
                                        ->success()
                                        ->title('Quotation converted to order')
                                        ->body('Order ID: '.$order->order_number)
                                        ->send();
                                },
                            );
                        }),
                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Quotation $record): bool => self::canRejectQuotation($record))
                        ->action(function (Quotation $record): void {
                            self::runWorkflowAction(
                                fn () => app(QuotationWorkflowService::class)->reject($record),
                                'Quotation rejected',
                            );
                        }),
                    Action::make('cancel')
                        ->label('Cancel Quotation')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Quotation $record): bool => self::canCancelQuotation($record))
                        ->action(function (Quotation $record): void {
                            self::runWorkflowAction(
                                fn () => app(QuotationWorkflowService::class)->reject($record),
                                'Quotation cancelled',
                            );
                        }),
                ])->label('Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function runWorkflowAction(callable $callback, ?string $successTitle = null): void
    {
        try {
            $callback();

            if (filled($successTitle)) {
                Notification::make()->success()->title($successTitle)->send();
            }
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Action cannot be completed')
                ->body(collect($exception->errors())->flatten()->implode(' '))
                ->send();
        }
    }

    private static function canEditQuotation(Quotation $record): bool
    {
        $user = auth('tenant')->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true)) {
            return true;
        }

        return $user->role === UserRole::VENDOR
            && (int) $record->vendor_id === (int) $user->id
            && in_array($record->status, [QuotationStatus::DRAFT, QuotationStatus::NEGOTIATED], true);
    }

    private static function canSendQuotation(Quotation $record): bool
    {
        $user = auth('tenant')->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true)) {
            return in_array($record->status, [QuotationStatus::DRAFT, QuotationStatus::NEGOTIATED], true);
        }

        return $user->role === UserRole::VENDOR
            && (int) $record->vendor_id === (int) $user->id
            && in_array($record->status, [QuotationStatus::DRAFT, QuotationStatus::NEGOTIATED], true);
    }

    private static function canCounterOffer(Quotation $record): bool
    {
        $user = auth('tenant')->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true)) {
            return $record->status === QuotationStatus::NEGOTIATED;
        }

        return $user->role === UserRole::VENDOR
            && (int) $record->vendor_id === (int) $user->id
            && $record->status === QuotationStatus::NEGOTIATED;
    }

    private static function canNegotiateQuotation(Quotation $record): bool
    {
        $user = auth('tenant')->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true)) {
            return $record->status === QuotationStatus::SENT;
        }

        return $user->role === UserRole::DISTRIBUTOR
            && (int) $record->distributor_id === (int) $user->id
            && $record->status === QuotationStatus::SENT;
    }

    private static function canConfirmQuotation(Quotation $record): bool
    {
        $user = auth('tenant')->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true)) {
            return in_array($record->status, [QuotationStatus::SENT, QuotationStatus::NEGOTIATED], true);
        }

        return $user->role === UserRole::DISTRIBUTOR
            && (int) $record->distributor_id === (int) $user->id
            && in_array($record->status, [QuotationStatus::SENT, QuotationStatus::NEGOTIATED], true);
    }

    private static function canRejectQuotation(Quotation $record): bool
    {
        $user = auth('tenant')->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true)) {
            return in_array($record->status, [QuotationStatus::SENT, QuotationStatus::NEGOTIATED], true);
        }

        return $user->role === UserRole::DISTRIBUTOR
            && (int) $record->distributor_id === (int) $user->id
            && in_array($record->status, [QuotationStatus::SENT, QuotationStatus::NEGOTIATED], true);
    }

    private static function canCancelQuotation(Quotation $record): bool
    {
        $user = auth('tenant')->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ORG_ADMIN], true)) {
            return in_array($record->status, [QuotationStatus::DRAFT, QuotationStatus::SENT, QuotationStatus::NEGOTIATED], true);
        }

        return $user->role === UserRole::VENDOR
            && (int) $record->vendor_id === (int) $user->id
            && in_array($record->status, [QuotationStatus::DRAFT, QuotationStatus::SENT, QuotationStatus::NEGOTIATED], true);
    }

    private static function statusLabel(?string $status): string
    {
        return match ($status) {
            QuotationStatus::DRAFT => 'Draft',
            QuotationStatus::SENT => 'Sent',
            QuotationStatus::NEGOTIATED => 'Negotiating',
            QuotationStatus::CONFIRMED => 'Confirmed',
            QuotationStatus::REJECTED => 'Rejected',
            QuotationStatus::CONVERTED => 'Converted',
            default => Str::headline((string) $status),
        };
    }

    private static function statusColor(?string $status): string
    {
        return match ($status) {
            QuotationStatus::DRAFT => 'gray',
            QuotationStatus::SENT => 'info',
            QuotationStatus::NEGOTIATED => 'warning',
            QuotationStatus::CONFIRMED => 'success',
            QuotationStatus::REJECTED => 'danger',
            QuotationStatus::CONVERTED => 'success',
            default => 'gray',
        };
    }

    private static function statusDescription(?string $status): string
    {
        return match ($status) {
            QuotationStatus::DRAFT => 'Vendor created quotation and can still edit.',
            QuotationStatus::SENT => 'Distributor has received the quotation for review.',
            QuotationStatus::NEGOTIATED => 'Distributor requested changes; vendor should respond.',
            QuotationStatus::CONFIRMED => 'Accepted and waiting for conversion.',
            QuotationStatus::REJECTED => 'Distributor declined or vendor cancelled the quotation.',
            QuotationStatus::CONVERTED => 'Quotation is converted into sales order.',
            default => 'Status updated',
        };
    }

    private static function nextActionLabel(Quotation $record): string
    {
        return match ($record->status) {
            QuotationStatus::DRAFT => 'Vendor should Send',
            QuotationStatus::SENT => 'Distributor can Negotiate, Confirm, or Reject',
            QuotationStatus::NEGOTIATED => 'Vendor should Counter Offer or Cancel',
            QuotationStatus::CONFIRMED => 'Convert to Order',
            QuotationStatus::REJECTED => 'No next action',
            QuotationStatus::CONVERTED => 'Order processing continues',
            default => 'Review quotation',
        };
    }
}
