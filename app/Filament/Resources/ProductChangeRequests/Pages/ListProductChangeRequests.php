<?php

namespace App\Filament\Resources\ProductChangeRequests\Pages;

use App\Filament\Resources\ProductChangeRequests\ProductChangeRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductChangeRequests extends ListRecords
{
    protected static string $resource = ProductChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn (): bool => static::$resource::canCreate()),
        ];
    }
}

