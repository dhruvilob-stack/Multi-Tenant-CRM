<?php

namespace App\Filament\Resources\CommissionLedgers\Pages;

use App\Filament\Resources\CommissionLedgers\CommissionLedgerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCommissionLedger extends EditRecord
{
    protected static string $resource = CommissionLedgerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
