<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Support\ResourceDataExchange;
use App\Models\Order;
use App\Services\OrderWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->searchable(),
                TextColumn::make('consumer.name')->label('Consumer'),
                TextColumn::make('vendor.name')->label('Vendor'),
                TextColumn::make('invoice.invoice_number')->label('Invoice'),
                TextColumn::make('status')->badge(),
                TextColumn::make('payment_method')->badge(),
                TextColumn::make('payment_status')->badge(),
                TextColumn::make('total_amount')->money('USD'),
                TextColumn::make('paid_at')->dateTime()->label('Paid At'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('markPaidAndGenerateInvoice')
                    ->label('Mark Paid + Generate Invoice')
                    ->color('info')
                    ->visible(fn (Order $record): bool => ! $record->invoice_id && $record->status !== 'cancelled' && $record->payment_status === 'confirmed')
                    ->action(function (Order $record): void {
                        try {
                            $invoice = app(OrderWorkflowService::class)->markPaidAndGenerateInvoice($record);
                            Notification::make()
                                ->success()
                                ->title('Payment captured and invoice generated')
                                ->body('Invoice: '.$invoice->invoice_number)
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot generate invoice')
                                ->body(collect($exception->errors())->flatten()->implode(' '))
                                ->send();
                        }
                    }),
                Action::make('confirmOrder')
                    ->label('Confirm')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === 'pending')
                    ->action(function (Order $record): void {
                        try {
                            app(OrderWorkflowService::class)->confirm($record);
                            Notification::make()->success()->title('Order confirmed')->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title('Order confirmation failed')->body(collect($exception->errors())->flatten()->implode(' '))->send();
                        }
                    }),
                Action::make('processOrder')
                    ->label('Move to Processing')
                    ->color('warning')
                    ->visible(fn (Order $record): bool => $record->status === 'confirmed')
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
                    ->visible(fn (Order $record): bool => $record->status === 'processing')
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
                    ->visible(fn (Order $record): bool => $record->status === 'shipped')
                    ->action(function (Order $record): void {
                        try {
                            app(OrderWorkflowService::class)->deliver($record);
                            Notification::make()->success()->title('Order delivered')->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title('Delivery blocked')->body(collect($exception->errors())->flatten()->implode(' '))->send();
                        }
                    }),
            ])
            ->toolbarActions([
                ...ResourceDataExchange::toolbarActions('orders'),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
