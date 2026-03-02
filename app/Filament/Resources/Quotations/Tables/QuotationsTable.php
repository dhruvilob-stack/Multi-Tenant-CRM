<?php

namespace App\Filament\Resources\Quotations\Tables;

use App\Models\Quotation;
use App\Services\QuotationWorkflowService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_number')->searchable(),
                TextColumn::make('vendor.name')->label('Vendor'),
                TextColumn::make('distributor.name')->label('Distributor'),
                TextColumn::make('status')->badge(),
                TextColumn::make('grand_total')->money('USD'),
                TextColumn::make('valid_until')->date(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('send')
                    ->action(function (Quotation $record): void {
                        app(QuotationWorkflowService::class)->send($record);
                        Notification::make()->success()->title('Quotation sent')->send();
                    }),
                Action::make('negotiate')
                    ->action(function (Quotation $record): void {
                        app(QuotationWorkflowService::class)->negotiate($record);
                        Notification::make()->success()->title('Quotation negotiated')->send();
                    }),
                Action::make('confirm')
                    ->action(function (Quotation $record): void {
                        app(QuotationWorkflowService::class)->confirm($record);
                        Notification::make()->success()->title('Quotation confirmed and converted')->send();
                    }),
                Action::make('reject')
                    ->color('danger')
                    ->action(function (Quotation $record): void {
                        app(QuotationWorkflowService::class)->reject($record);
                        Notification::make()->success()->title('Quotation rejected')->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
