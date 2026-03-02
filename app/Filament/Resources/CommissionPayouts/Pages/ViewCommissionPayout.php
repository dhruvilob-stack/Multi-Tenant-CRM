<?php

namespace App\Filament\Resources\CommissionPayouts\Pages;

use App\Filament\Resources\CommissionPayouts\CommissionPayoutResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommissionPayout extends ViewRecord
{
    protected static string $resource = CommissionPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
