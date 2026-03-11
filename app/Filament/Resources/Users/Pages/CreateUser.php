<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Concerns\StoresPanelLoginPrefillPassword;
use App\Models\User;
use App\Filament\Resources\Users\UserResource;
use App\Services\UserAccessMailService;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use StoresPanelLoginPrefillPassword;

    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->capturePanelPrefillPassword($data);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record instanceof User) {
            $this->savePanelPrefillPassword($this->record);
            $password = $this->getPanelPrefillPassword();
            if ($password) {
                app(UserAccessMailService::class)->sendForUser($this->record, $password);
            }
        }
    }
}
