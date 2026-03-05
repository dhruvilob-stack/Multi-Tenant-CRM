<?php

namespace App\Filament\Resources\CommissionPayouts\Pages;

use App\Filament\Resources\CommissionPayouts\CommissionPayoutResource;
use App\Services\CommissionPayoutService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCommissionPayout extends EditRecord
{
    protected static string $resource = CommissionPayoutResource::class;
    protected ?string $previousStatus = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->previousStatus = (string) ($data['status'] ?? '');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $current = (string) ($this->record->status ?? '');
        if ($current === 'completed' && $this->previousStatus !== 'completed') {
            app(CommissionPayoutService::class)->markCompleted($this->record);
        }

        $this->previousStatus = $current;
    }
}
