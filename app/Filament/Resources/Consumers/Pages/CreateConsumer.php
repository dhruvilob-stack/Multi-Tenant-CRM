<?php

namespace App\Filament\Resources\Consumers\Pages;

use App\Filament\Resources\Consumers\ConsumerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConsumer extends CreateRecord
{
    protected static string $resource = ConsumerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = 'consumer';
        $data['status'] = $data['status'] ?? 'active';

        return $data;
    }
}
