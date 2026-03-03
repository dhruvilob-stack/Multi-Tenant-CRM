<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\OrderWorkflowService;
use App\Support\UserRole;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        $data['status'] = 'pending';
        $data['payment_status'] = (string) ($data['payment_status'] ?? 'pending');

        if ($user && $user->role !== UserRole::SUPER_ADMIN) {
            if (! isset($data['vendor_id']) && $user->role === UserRole::VENDOR) {
                $data['vendor_id'] = $user->id;
            }
        }

        return $data;
    }

    protected function afterCreate(): void
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
                ->title('Order saved, but invoice not generated')
                ->body(collect($exception->errors())->flatten()->implode(' '))
                ->send();
        }
    }
}
