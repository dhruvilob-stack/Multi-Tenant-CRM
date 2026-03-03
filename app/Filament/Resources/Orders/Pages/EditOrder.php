<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\OrderWorkflowService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Status transitions are controlled via dedicated workflow actions.
        unset($data['status']);

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record || ! $this->record->payment_method || $this->record->invoice_id) {
            return;
        }

        if ((string) $this->record->payment_status !== 'confirmed') {
            return;
        }

        try {
            $invoice = app(OrderWorkflowService::class)->markPaidAndGenerateInvoice($this->record->refresh());

            Notification::make()
                ->success()
                ->title('Invoice auto-generated')
                ->body('Invoice '.$invoice->invoice_number.' created and marked paid.')
                ->send();
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Order updated, but invoice not generated')
                ->body(collect($exception->errors())->flatten()->implode(' '))
                ->send();
        }
    }
}
