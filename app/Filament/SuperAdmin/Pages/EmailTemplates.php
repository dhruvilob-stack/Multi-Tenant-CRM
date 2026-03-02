<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class EmailTemplates extends Page
{
    protected string $view = 'filament.super-admin.pages.email-templates';
    protected static ?string $slug = 'email-templates';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedEnvelope;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.super_admin.groups.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.super_admin.pages.email_templates.nav');
    }
}

