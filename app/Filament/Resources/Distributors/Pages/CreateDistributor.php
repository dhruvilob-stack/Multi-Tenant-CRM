<?php

namespace App\Filament\Resources\Distributors\Pages;

use App\Filament\Resources\Users\Concerns\StoresPanelLoginPrefillPassword;
use App\Filament\Resources\Distributors\DistributorResource;
use App\Models\User;
use App\Support\UserRole;
use Filament\Resources\Pages\CreateRecord;

class CreateDistributor extends CreateRecord
{
    use StoresPanelLoginPrefillPassword;

    protected static string $resource = DistributorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->capturePanelPrefillPassword($data);
        $user = auth()->user();

        $data['role'] = UserRole::DISTRIBUTOR;
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
