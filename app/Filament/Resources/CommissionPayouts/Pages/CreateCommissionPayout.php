<?php

namespace App\Filament\Resources\CommissionPayouts\Pages;

use App\Filament\Resources\CommissionPayouts\CommissionPayoutResource;
use App\Models\CommissionPayout;
use App\Services\CommissionPayoutService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateCommissionPayout extends CreateRecord
{
    protected static string $resource = CommissionPayoutResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            /** @var CommissionPayout $payout */
            $payout = app(CommissionPayoutService::class)->create($data, auth('tenant')->user());
        } catch (ValidationException $e) {
            Notification::make()
                ->danger()
                ->title('Unable to create payout')
                ->body(collect($e->errors())->flatten()->implode(' '))
                ->send();
            throw $e;
        }

        return $payout;
    }
}
