<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Users\Concerns\StoresPanelLoginPrefillPassword;
use App\Filament\Resources\Vendors\VendorResource;
use App\Models\User;
use App\Support\UserRole;
use Filament\Resources\Pages\EditRecord;

class EditVendor extends EditRecord
{
    use StoresPanelLoginPrefillPassword;

    protected static string $resource = VendorResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->capturePanelPrefillPassword($data);
        $user = auth('tenant')->user();

        $data['role'] = UserRole::VENDOR;

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
