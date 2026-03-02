<?php

namespace App\Filament\SuperAdmin\Resources\CustomRoles\Pages;

use App\Filament\SuperAdmin\Resources\CustomRoles\CustomRoleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomRole extends ViewRecord
{
    protected static string $resource = CustomRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

