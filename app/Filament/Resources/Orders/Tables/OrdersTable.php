<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Support\ResourceDataExchange;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use App\Support\SystemSettings;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('consumer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('payment_status')->badge(),
                TextColumn::make('currency')->badge()->toggleable(),
                TextColumn::make('created_at')->label('Order date')->date()->sortable(),
                TextColumn::make('total_amount_billed')
                    ->label('Total Amount Billed')
                    ->money(fn(Order $record): string => (string) ($record->currency ?? SystemSettings::currencyForCurrentUser()))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('processOrder')
                        ->label('Move to Processing')
                        ->color('warning')
                        ->icon(Heroicon::ArrowPath)
                        ->visible(fn(Order $record): bool => $record->status === 'new')
                        ->action(function (Order $record): void {
                            try {
                                app(OrderWorkflowService::class)->process($record);
                                Notification::make()->success()->title('Order moved to processing')->send();
                            } catch (ValidationException $exception) {
                                Notification::make()->danger()->title('Order processing failed')->body(collect($exception->errors())->flatten()->implode(' '))->send();
                            }
                        }),
                    Action::make('shipOrder')
                        ->label('Ship')
                        ->color('info')
                        ->icon(Heroicon::Truck)
                        ->visible(fn(Order $record): bool => $record->status === 'processing')
                        ->action(function (Order $record): void {
                            try {
                                app(OrderWorkflowService::class)->ship($record);
                                Notification::make()->success()->title('Order shipped')->send();
                            } catch (ValidationException $exception) {
                                Notification::make()->danger()->title('Shipping blocked')->body(collect($exception->errors())->flatten()->implode(' '))->send();
                            }
                        }),
                    Action::make('deliverOrder')
                        ->label('Deliver')
                        ->color('success')
                        ->icon(Heroicon::CheckBadge)
                        ->visible(fn(Order $record): bool => $record->status === 'shipped')
                        ->action(function (Order $record): void {
                            try {
                                app(OrderWorkflowService::class)->deliver($record);
                                Notification::make()->success()->title('Order delivered, stock and purchase updated')->send();
                            } catch (ValidationException $exception) {
                                Notification::make()->danger()->title('Delivery blocked')->body(collect($exception->errors())->flatten()->implode(' '))->send();
                            }
                        }),
                    Action::make('markPaidAndGenerateInvoice')
                        ->label('Send Mail & Generate Invoice')
                        ->color('white')
                        ->icon(Heroicon::Check)
                        ->visible(fn(Order $record): bool => $record->status === 'delivered' && $record->payment_status === 'confirmed')
                        ->action(function (Order $record): void {
                            try {
                                $invoice = app(OrderWorkflowService::class)->sendDeliveryMailAndEnsureInvoice($record);
                                Notification::make()
                                    ->success()
                                    ->title('Delivery mail sent and invoice ready')
                                    ->body('Invoice: '.$invoice->invoice_number)
                                    ->send();
                            } catch (ValidationException $exception) {
                                Notification::make()
                                    ->danger()
                                    ->title('Cannot send mail or generate invoice')
                                    ->body(collect($exception->errors())->flatten()->implode(' '))
                                    ->send();
                            }
                        }),
                    Action::make('cancelOrder')
                        ->label('Cancel')
                        ->color('danger')
                        ->visible(fn(Order $record): bool => !in_array($record->status, ['delivered', 'cancelled'], true))
                        ->requiresConfirmation()
                        ->action(function (Order $record): void {
                            $record->update(['status' => 'cancelled']);
                            Notification::make()->title('Order cancelled')->danger()->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                ...ResourceDataExchange::toolbarActions('orders'),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
