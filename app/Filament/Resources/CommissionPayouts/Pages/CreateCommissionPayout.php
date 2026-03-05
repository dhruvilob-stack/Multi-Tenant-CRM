<?php

namespace App\Filament\Resources\CommissionPayouts\Pages;

use App\Filament\Resources\CommissionPayouts\CommissionPayoutResource;
use App\Models\CommissionPayout;
use App\Services\CommissionPayoutService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCommissionPayout extends CreateRecord
{
    protected static string $resource = CommissionPayoutResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var CommissionPayout $payout */
        $payout = app(CommissionPayoutService::class)->create($data, auth()->user());

        return $payout;
    }
}
