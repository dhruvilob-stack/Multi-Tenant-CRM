<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\PlatformAuditLog;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class AuditLogs extends Page
{
    protected string $view = 'filament.super-admin.pages.audit-logs';
    protected static ?string $slug = 'audit-logs';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public string $tenantFilter = '';
    public string $eventFilter = '';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.super_admin.groups.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.super_admin.pages.audit_logs.nav');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLogs(): array
    {
        return PlatformAuditLog::query()
            ->when($this->tenantFilter !== '', fn ($q) => $q->where('tenant_slug', 'like', '%'.$this->tenantFilter.'%'))
            ->when($this->eventFilter !== '', fn ($q) => $q->where('event', 'like', '%'.$this->eventFilter.'%'))
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn (PlatformAuditLog $log): array => [
                'id' => $log->id,
                'tenant' => $log->tenant_slug ?: ($log->tenant_id ?: '-'),
                'event' => $log->event,
                'actor' => $log->actor_email ?: '-',
                'role' => $log->actor_role ?: '-',
                'auditable' => $log->auditable_type ? class_basename((string) $log->auditable_type).'#'.($log->auditable_id ?: '-') : '-',
                'route' => $log->route_name ?: '-',
                'method' => $log->method ?: '-',
                'ip' => $log->ip_address ?: '-',
                'at' => optional($log->created_at)->toDateTimeString(),
            ])
            ->all();
    }
}
