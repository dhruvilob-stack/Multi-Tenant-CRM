<?php

namespace App\Filament\Resources\ProductChangeRequests\Pages;

use App\Filament\Resources\ProductChangeRequests\ProductChangeRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProductChangeRequest extends EditRecord
{
    protected static string $resource = ProductChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
