<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use App\Services\InvoiceWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('currency'),
                TextColumn::make('grand_total')->money('USD'),
                TextColumn::make('received_amount')->money('USD'),
                TextColumn::make('balance')->money('USD'),
                TextColumn::make('due_date')->date(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Invoice $record): string => route('admin.invoices.pdf', ['id' => $record->id]))
                    ->openUrlInNewTab(),
                Action::make('approve')
                    ->action(function (Invoice $record): void {
                        app(InvoiceWorkflowService::class)->approve($record);
                        Notification::make()->success()->title('Invoice approved')->send();
                    }),
                Action::make('mark-paid')
                    ->action(function (Invoice $record): void {
                        app(InvoiceWorkflowService::class)->markPaid($record);
                        Notification::make()->success()->title('Invoice marked paid')->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
