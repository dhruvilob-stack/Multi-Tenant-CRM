<?php

namespace App\Filament\Resources\MarginCommissions\Pages;

use App\Filament\Resources\MarginCommissions\MarginCommissionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMarginCommission extends EditRecord
{
    protected static string $resource = MarginCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
