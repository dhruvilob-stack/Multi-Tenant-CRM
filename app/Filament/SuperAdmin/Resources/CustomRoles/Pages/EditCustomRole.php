<?php

namespace App\Filament\SuperAdmin\Resources\CustomRoles\Pages;

use App\Filament\SuperAdmin\Resources\CustomRoles\CustomRoleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomRole extends EditRecord
{
    protected static string $resource = CustomRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $groups = [];

        foreach (($data['permissions'] ?? []) as $permission) {
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            $role = explode('.', $permission, 2)[0] ?? 'other';
            $groups[$role] ??= [];
            $groups[$role][] = $permission;
        }

        $data['permission_groups'] = $groups;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
