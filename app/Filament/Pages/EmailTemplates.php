<?php

namespace App\Filament\Pages;

use App\Support\UserRole;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class EmailTemplates extends Page
{
    protected string $view = 'filament.pages.email-templates';
    protected static ?string $slug = 'email-templates';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.email_templates.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::ORG_ADMIN], true);
    }
}
