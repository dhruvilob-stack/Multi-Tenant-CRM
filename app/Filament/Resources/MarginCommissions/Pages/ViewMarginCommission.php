<?php

namespace App\Filament\Resources\MarginCommissions\Pages;

use App\Filament\Resources\MarginCommissions\MarginCommissionResource;
use App\Support\UserRole;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMarginCommission extends ViewRecord
{
    protected static string $resource = MarginCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth('tenant')->user()?->role === UserRole::ORG_ADMIN),
        ];
    }
}
