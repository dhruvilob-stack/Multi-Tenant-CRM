<?php

namespace App\Filament\SuperAdmin\Resources\CustomRoles\Pages;

use App\Filament\SuperAdmin\Resources\CustomRoles\CustomRoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomRoles extends ListRecords
{
    protected static string $resource = CustomRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

