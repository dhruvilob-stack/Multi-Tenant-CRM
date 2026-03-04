<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Services\OrderWorkflowService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ReplicateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected ?string $statusBeforeSave = null;

    protected ?string $paymentStatusBeforeSave = null;

    protected function getHeaderActions(): array
    {
        return [
            ReplicateAction::make()
                ->requiresConfirmation()
                ->excludeAttributes(['id', 'order_number', 'status', 'created_at', 'updated_at'])
                ->mutateRecordDataUsing(function (array $data): array {
                    $data['order_number'] = 'OR-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
                    $data['status'] = 'new';

                    return $data;
                }),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $hasItemsPayload = array_key_exists('items', $data);
        $data = OrderForm::normalizeItemsAndTotals($data);
        $data['payment_reference_number'] = filled($data['payment_reference_number'] ?? null)
            ? (string) $data['payment_reference_number']
            : null;
        if ($hasItemsPayload) {
            $data['total_amount_billed'] = (float) ($data['total_amount'] ?? 0);
        } else {
            $existingTotal = (float) $this->record->items()->sum('line_total');
            $data['total_amount'] = round($existingTotal, 2);
            $data['total_amount_billed'] = round($existingTotal, 2);
        }

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->statusBeforeSave = (string) ($this->record->status ?? '');
        $this->paymentStatusBeforeSave = (string) ($this->record->payment_status ?? '');
    }

    protected function afterSave(): void
    {
        $record = $this->record?->fresh();

        if (! $record) {
            return;
        }

        $wasDeliveredAndPaid = $this->statusBeforeSave === 'delivered' && $this->paymentStatusBeforeSave === 'confirmed';
        $isDeliveredAndPaid = (string) $record->status === 'delivered' && (string) $record->payment_status === 'confirmed';

        if ($wasDeliveredAndPaid || ! $isDeliveredAndPaid) {
            return;
        }

        try {
            app(OrderWorkflowService::class)->syncDeliveredPaidOrderEffects($record);
        } catch (ValidationException $exception) {
            if (filled($this->statusBeforeSave)) {
                $record->update(['status' => $this->statusBeforeSave]);
            }

            Notification::make()
                ->danger()
                ->title('Delivery update failed')
                ->body(collect($exception->errors())->flatten()->implode(' '))
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl('view', ['record' => $this->record]);
    }
}
