<?php

namespace App\Filament\Resources\Distributors\Pages;

use App\Filament\Resources\Users\Concerns\StoresPanelLoginPrefillPassword;
use App\Filament\Resources\Distributors\DistributorResource;
use App\Models\User;
use App\Support\UserRole;
use Filament\Resources\Pages\EditRecord;

class EditDistributor extends EditRecord
{
    use StoresPanelLoginPrefillPassword;

    protected static string $resource = DistributorResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->capturePanelPrefillPassword($data);
        $user = auth()->user();

        $data['role'] = UserRole::DISTRIBUTOR;

        if ($user && $user->role !== UserRole::SUPER_ADMIN) {
            $data['organization_id'] = $user->organization_id;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record instanceof User) {
            $this->savePanelPrefillPassword($this->record);
        }
    }
}
