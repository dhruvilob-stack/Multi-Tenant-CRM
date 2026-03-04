<?php

namespace App\Filament\Resources\Shop\Categories\Pages;

use App\Filament\Resources\Shop\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;
use App\Support\AccessMatrix;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user && ! AccessMatrix::isSuper($user)) {
            $data['organization_id'] = $user->organization_id;
        }

        return $data;
    }
}
