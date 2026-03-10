<?php

namespace App\Filament\Resources\Manufacturers\Pages;

use App\Filament\Resources\Users\Concerns\StoresPanelLoginPrefillPassword;
use App\Filament\Resources\Manufacturers\ManufacturerResource;
use App\Models\User;
use App\Support\UserRole;
use Filament\Resources\Pages\CreateRecord;

class CreateManufacturer extends CreateRecord
{
    use StoresPanelLoginPrefillPassword;

    protected static string $resource = ManufacturerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->capturePanelPrefillPassword($data);
        $user = auth()->user();

        $data['role'] = UserRole::MANUFACTURER;
        $data['status'] = $data['status'] ?? 'active';

        if ($user && $user->role !== UserRole::SUPER_ADMIN) {
            $data['organization_id'] = $user->organization_id;
        }

        $data['parent_id'] = $data['parent_id'] ?? $user?->id;

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record instanceof User) {
            $this->savePanelPrefillPassword($this->record);
        }
    }
}
