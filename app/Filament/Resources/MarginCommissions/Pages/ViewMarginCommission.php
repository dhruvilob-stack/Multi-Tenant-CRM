<?php

namespace App\Filament\Resources\MarginCommissions\Pages;

use App\Filament\Resources\MarginCommissions\MarginCommissionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMarginCommission extends ViewRecord
{
    protected static string $resource = MarginCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
