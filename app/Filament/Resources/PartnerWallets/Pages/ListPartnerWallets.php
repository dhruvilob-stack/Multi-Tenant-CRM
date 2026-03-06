<?php

namespace App\Filament\Resources\PartnerWallets\Pages;

use App\Filament\Resources\PartnerWallets\PartnerWalletResource;
use Filament\Resources\Pages\ListRecords;

class ListPartnerWallets extends ListRecords
{
    protected static string $resource = PartnerWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
