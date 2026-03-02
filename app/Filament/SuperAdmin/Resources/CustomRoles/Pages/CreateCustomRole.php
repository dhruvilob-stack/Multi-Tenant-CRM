<?php

namespace App\Filament\SuperAdmin\Resources\CustomRoles\Pages;

use App\Filament\SuperAdmin\Resources\CustomRoles\CustomRoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomRole extends CreateRecord
{
    protected static string $resource = CustomRoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $groups = $data['permission_groups'] ?? [];

        $data['permissions'] = collect($groups)
            ->filter(fn ($permissions): bool => is_array($permissions))
            ->flatMap(fn (array $permissions): array => $permissions)
            ->filter()
            ->unique()
            ->values()
            ->all();

        unset($data['permission_groups']);

        return $data;
    }
}
