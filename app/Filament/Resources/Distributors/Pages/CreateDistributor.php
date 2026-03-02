<?php

namespace App\Filament\Resources\Distributors\Pages;

use App\Filament\Resources\Distributors\DistributorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDistributor extends CreateRecord
{
    protected static string $resource = DistributorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = 'distributor';
        $data['status'] = $data['status'] ?? 'active';

        return $data;
    }
}
