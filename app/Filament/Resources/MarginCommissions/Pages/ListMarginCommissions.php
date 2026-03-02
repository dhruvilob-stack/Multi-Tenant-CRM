<?php

namespace App\Filament\Resources\MarginCommissions\Pages;

use App\Filament\Resources\MarginCommissions\MarginCommissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarginCommissions extends ListRecords
{
    protected static string $resource = MarginCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn (): bool => static::$resource::canCreate()),
        ];
    }
}

