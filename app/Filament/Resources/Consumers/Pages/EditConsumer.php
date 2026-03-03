<?php

namespace App\Filament\Resources\Consumers\Pages;

use App\Filament\Resources\Consumers\ConsumerResource;
use App\Support\UserRole;
use Filament\Resources\Pages\EditRecord;

class EditConsumer extends EditRecord
{
    protected static string $resource = ConsumerResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        $data['role'] = UserRole::CONSUMER;

        if ($user && $user->role !== UserRole::SUPER_ADMIN) {
            $data['organization_id'] = $user->organization_id;
        }

        return $data;
    }
}
