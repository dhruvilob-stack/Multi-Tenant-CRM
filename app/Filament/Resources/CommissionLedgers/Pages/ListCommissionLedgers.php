<?php

namespace App\Filament\Resources\CommissionLedgers\Pages;

use App\Filament\Resources\CommissionLedgers\CommissionLedgerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommissionLedgers extends ListRecords
{
    protected static string $resource = CommissionLedgerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn (): bool => static::$resource::canCreate()),
        ];
    }
}

