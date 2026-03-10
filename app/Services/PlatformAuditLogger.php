<?php

namespace App\Services;

use App\Models\PlatformAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class PlatformAuditLogger
{
    /**
     * Mirror tenant audit logs into landlord for super-admin monitoring.
     *
     * @param  array<string, mixed>  $payload
     */
    public function mirrorFromTenant(Model $model, array $payload): void
    {
        try {
            DB::connection('landlord')->getPdo();
        } catch (\Throwable) {
            return;
        }

        $tenantId = (string) (session('tenant_id') ?? '');
        $tenantSlug = (string) (session('tenant_slug') ?? Request::route('tenant') ?? '');

        $actor = Auth::user();

        try {
            PlatformAuditLog::query()->create([
                'tenant_id' => $tenantId !== '' ? $tenantId : null,
                'tenant_slug' => $tenantSlug !== '' ? $tenantSlug : null,
                'event' => (string) ($payload['event'] ?? 'unknown'),
                'auditable_type' => $model::class,
                'auditable_id' => (string) $model->getKey(),
                'actor_id' => $actor?->id,
                'actor_email' => $actor?->email ?: (string) ($payload['performed_by'] ?? null),
                'actor_role' => $actor?->role ?: (string) ($payload['performed_role'] ?? null),
                'before' => $this->safeJson($payload['before'] ?? null),
                'after' => $this->safeJson($payload['after'] ?? null),
                'ip_address' => (string) (Request::ip() ?? ''),
                'user_agent' => (string) (Request::userAgent() ?? ''),
                'route_name' => (string) (Request::route()?->getName() ?? ''),
                'url' => (string) Request::fullUrl(),
                'method' => (string) Request::method(),
            ]);
        } catch (\Throwable) {
            // Never break tenant writes if landlord logging fails.
        }
    }

    private function safeJson(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}

