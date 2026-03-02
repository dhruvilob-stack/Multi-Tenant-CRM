<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class AuditLogs extends Page
{
    protected string $view = 'filament.super-admin.pages.audit-logs';
    protected static ?string $slug = 'audit-logs';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.super_admin.groups.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.super_admin.pages.audit_logs.nav');
    }
}

