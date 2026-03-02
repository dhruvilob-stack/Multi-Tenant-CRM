<?php

namespace App\Filament\Resources\CommissionPayouts\Pages;

use App\Filament\Resources\CommissionPayouts\CommissionPayoutResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommissionPayouts extends ListRecords
{
    protected static string $resource = CommissionPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn (): bool => static::$resource::canCreate()),
        ];
    }
}

