<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ResourceNotification;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Events\ResourceNotificationCreated;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AuditNotificationService
{
    public function log(Model $model, string $event, array $before = [], array $after = []): AuditLog
    {
        $modelKey = (string) $model->getKey();

        return AuditLog::query()->create([
            'auditable_type' => $model::class,
            'auditable_id' => $modelKey,
            'event' => $event,
            'performed_by' => Auth::id() ? (string) Auth::user()->email : 'system',
            'performed_role' => Auth::user()?->role,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => Request::ip(),
        ]);
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

        foreach ($targetList as $recipient) {
            if (! $recipient || ! isset($recipient->id)) {
                continue;
            }

            $resolvedRedirectUrl = is_callable($redirectUrl)
                ? (string) $redirectUrl($recipient, $model)
                : $redirectUrl;

            $rows[] = $notification = ResourceNotification::query()->create([
                'notificationable_type' => $model::class,
                'notificationable_id' => $modelKey,
                'recipient_id' => $recipient->id,
                'recipient_role' => $recipient->role,
                'action' => $action,
                'message' => $message,
                'redirect_url' => $resolvedRedirectUrl,
            ]);

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

            ResourceNotificationCreated::dispatch($notification);
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
}
