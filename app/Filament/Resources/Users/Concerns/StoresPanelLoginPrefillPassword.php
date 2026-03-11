<?php

namespace App\Filament\Resources\Users\Concerns;

use App\Models\User;
use App\Support\PanelLoginPrefillStore;
use App\Support\TenantUserMirror;

trait StoresPanelLoginPrefillPassword
{
    protected ?string $panelPrefillPassword = null;

    protected function capturePanelPrefillPassword(array $data): void
    {
        $password = (string) ($data['password'] ?? '');
        $this->panelPrefillPassword = $password !== '' ? $password : null;
    }

    protected function savePanelPrefillPassword(User $user): void
    {
        TenantUserMirror::syncToLandlord($user);

        if (! $this->panelPrefillPassword) {
            return;
        }

        PanelLoginPrefillStore::saveForUser($user, $this->panelPrefillPassword);
    }

    protected function getPanelPrefillPassword(): ?string
    {
        return $this->panelPrefillPassword;
    }
}
