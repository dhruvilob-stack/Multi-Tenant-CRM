<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = 'vendor';
        $data['status'] = $data['status'] ?? 'active';

        return $data;
    }
}
