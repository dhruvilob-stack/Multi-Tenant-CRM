<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ResourceNotification;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use App\Events\ResourceNotificationCreated;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;
use App\Services\PlatformAuditLogger;

class AuditNotificationService
{
    public function log(Model $model, string $event, array $before = [], array $after = []): AuditLog
    {
        $modelKey = (string) $model->getKey();
        $auditableKeyColumn = $this->resolvePolymorphicKeyColumn('audit_logs', 'auditable');
        $payload = [
            'auditable_type' => $model::class,
            'event' => $event,
            'performed_by' => Auth::id() ? (string) Auth::user()->email : 'system',
            'performed_role' => Auth::user()?->role,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => Request::ip(),
        ];

        if ($auditableKeyColumn) {
            $payload[$auditableKeyColumn] = $modelKey;
        }

        if (! $auditableKeyColumn || ! $this->canPersistPolymorphicKey('audit_logs', $auditableKeyColumn, $modelKey)) {
            app(PlatformAuditLogger::class)->mirrorFromTenant($model, $payload);

            return new AuditLog($payload);
        }

        try {
            $row = AuditLog::query()->create($payload);
            app(PlatformAuditLogger::class)->mirrorFromTenant($model, $payload);

            return $row;
        } catch (QueryException) {
            app(PlatformAuditLogger::class)->mirrorFromTenant($model, $payload);
            return new AuditLog($payload);
        }
    }

    /**
     * @param Model $model
     * @param iterable|Arrayable|Collection|callable $recipients
     * @param string|callable $redirectUrl
     * @return ResourceNotification[]
     */
    public function notify(
        Model $model,
        string $action,
        string $message,
        string|callable $redirectUrl,
        iterable|Arrayable|Collection|callable $recipients
    ): array
    {
        $modelKey = (string) $model->getKey();
        $rows = [];
        $targetList = $this->normalizeRecipients(
            is_callable($recipients) ? $recipients($model) : $recipients
        );
        $notificationKeyColumn = $this->resolvePolymorphicKeyColumn('resource_notifications', 'notificationable');
        $canPersistResourceNotification = $this->canPersistPolymorphicKey(
            'resource_notifications',
            (string) $notificationKeyColumn,
            $modelKey
        );

        foreach ($targetList as $recipient) {
            if (! $recipient || ! isset($recipient->id)) {
                continue;
            }

            $resolvedRedirectUrl = is_callable($redirectUrl)
                ? (string) $redirectUrl($recipient, $model)
                : $redirectUrl;

            $notification = null;

            if ($canPersistResourceNotification && $notificationKeyColumn) {
                try {
                    $notificationPayload = [
                        'notificationable_type' => $model::class,
                        'recipient_id' => $recipient->id,
                        'recipient_role' => $recipient->role,
                        'action' => $action,
                        'message' => $message,
                        'redirect_url' => $resolvedRedirectUrl,
                    ];
                    $notificationPayload[$notificationKeyColumn] = $modelKey;

                    $notification = ResourceNotification::query()->create($notificationPayload);

                    $rows[] = $notification;
                } catch (QueryException) {
                    $notification = null;
                }
            }

            $filamentNotification = FilamentNotification::make()
                ->title($message)
                ->body($message)
                ->success()
                ->duration(7000)
                ->actions([
                    Action::make('view')
                        ->label(__('View'))
                        ->button()
                        ->url($resolvedRedirectUrl),
                ])
                ->viewData([
                    'section' => Str::kebab(class_basename($model)),
                    'redirect_url' => $resolvedRedirectUrl,
                    'resource_id' => $model->getKey(),
                ])
                ->sendToDatabase($recipient, isEventDispatched: true);

            $filamentNotification->broadcast($recipient);

            if ($notification) {
                ResourceNotificationCreated::dispatch($notification);
            }
        }

        return $rows;
    }

    public function markAsRead(ResourceNotification $notification): void
    {
        $notification->update(['read' => true]);
    }

    /**
     * @param mixed $recipients
     * @return array<int, mixed>
     */
    protected function normalizeRecipients(mixed $recipients): array
    {
        if ($recipients instanceof Collection) {
            return $recipients->all();
        }

        if ($recipients instanceof Arrayable) {
            return $recipients->toArray();
        }

        if (is_array($recipients)) {
            return $recipients;
        }

        if ($recipients instanceof \Traversable) {
            return iterator_to_array($recipients, false);
        }

        return [];
    }

    protected function canPersistPolymorphicKey(string $table, string $column, string $key): bool
    {
        try {
            if ($column === '' || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                return false;
            }

            if (is_numeric($key)) {
                return true;
            }

            $type = Schema::getColumnType($table, $column);

            return in_array($type, ['string', 'varchar', 'text', 'char'], true);
        } catch (Throwable) {
            return false;
        }
    }

    protected function resolvePolymorphicKeyColumn(string $table, string $prefix): ?string
    {
        try {
            if (! Schema::hasTable($table)) {
                return null;
            }

            $candidates = [
                "{$prefix}_id",
                "{$prefix}_uuid",
            ];

            foreach ($candidates as $candidate) {
                if (Schema::hasColumn($table, $candidate)) {
                    return $candidate;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
