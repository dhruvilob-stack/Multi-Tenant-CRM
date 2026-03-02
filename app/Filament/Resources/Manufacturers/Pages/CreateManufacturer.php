<?php

namespace App\Filament\Resources\Manufacturers\Pages;

use App\Filament\Resources\Manufacturers\ManufacturerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManufacturer extends CreateRecord
{
    protected static string $resource = ManufacturerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = 'manufacturer';
        $data['status'] = $data['status'] ?? 'active';

        return $data;
    }
}
