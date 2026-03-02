<?php

namespace App\Filament\Resources\CommissionLedgers\Pages;

use App\Filament\Resources\CommissionLedgers\CommissionLedgerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommissionLedger extends ViewRecord
{
    protected static string $resource = CommissionLedgerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
