<?php

namespace App\Filament\Resources\ProductChangeRequests\Pages;

use App\Filament\Resources\ProductChangeRequests\ProductChangeRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductChangeRequest extends CreateRecord
{
    protected static string $resource = ProductChangeRequestResource::class;
}
