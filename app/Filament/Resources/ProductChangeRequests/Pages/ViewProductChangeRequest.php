<?php

namespace App\Filament\Resources\ProductChangeRequests\Pages;

use App\Filament\Resources\ProductChangeRequests\ProductChangeRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProductChangeRequest extends ViewRecord
{
    protected static string $resource = ProductChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
