<?php

namespace App\Filament\Resources\CommissionPayouts\Pages;

use App\Filament\Resources\CommissionPayouts\CommissionPayoutResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCommissionPayout extends EditRecord
{
    protected static string $resource = CommissionPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
