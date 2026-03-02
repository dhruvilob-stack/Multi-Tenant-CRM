<?php

namespace App\Filament\Pages;

use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class StockMovements extends Page
{
    protected string $view = 'filament.pages.stock-movements';
    protected static ?string $slug = 'inventory/movements';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.stock_movements.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::ORG_ADMIN,
            UserRole::MANUFACTURER,
            UserRole::DISTRIBUTOR,
            UserRole::VENDOR,
        ], true);
    }
}
