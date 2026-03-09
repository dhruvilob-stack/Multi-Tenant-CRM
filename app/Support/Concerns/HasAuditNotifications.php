<?php

namespace App\Support\Concerns;

use App\Models\User;
use App\Services\AuditNotificationService;
use App\Support\UserRole;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Throwable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use function http_build_query;

trait HasAuditNotifications
{
    protected static function bootHasAuditNotifications(): void
    {
        static::created(fn (Model $model) => $model->handleAuditEvent('created'));
        static::updated(fn (Model $model) => $model->handleAuditEvent('updated'));
        static::deleted(fn (Model $model) => $model->handleAuditEvent('deleted'));
    }

    protected function handleAuditEvent(string $event): void
    {
        $service = app(AuditNotificationService::class);
        $before = $event === 'updated' ? $this->getOriginal() : [];
        $after = $event === 'deleted' ? [] : $this->getAttributes();

        $service->log($this, $event, $before, $after);

        $recipients = $this->auditRecipients();
        if ($recipients->isEmpty()) {
            return;
        }

        $message = __('notifications.resource_event', [
            'resource' => Str::headline(class_basename($this)),
            'event' => __('notifications.events.'.$event),
            'label' => $this->getAuditLabel(),
        ]);
        $redirectUrl = fn (User $recipient): string => $this->notificationRedirectUrlForRecipient($recipient, $event);

        $service->notify($this, $event, $message, $redirectUrl, $recipients);
    }

    protected function getAuditLabel(): string
    {
        return (string) ($this->name ?? $this->title ?? $this->id ?? '');
    }

    protected function auditRecipients(): Collection
    {
        $users = collect();

        if ($parent = $this->getAuditParent()) {
            $users->push($parent);
        }

        if ($organizationId = $this->resolveOrganizationId()) {
            $orgAdmins = $this->landlordUsersQuery()
                ->where('organization_id', $organizationId)
                ->whereIn('role', [UserRole::ORG_ADMIN, UserRole::SUPER_ADMIN])
                ->get();

            $users = $users->merge($orgAdmins);
        }

        $superAdmins = $this->landlordUsersQuery()
            ->where('role', UserRole::SUPER_ADMIN)
            ->get();

        $users = $users->merge($superAdmins);

        if (method_exists($this, 'inviter')) {
            $inviter = $this->inviter()->getResults();
            if ($inviter) {
                $users->push($inviter);
            }
        }

        return $users
            ->filter()
            ->unique('id')
            ->reject(fn (User $user) => $user->id === Auth::id());
    }

    protected function getAuditParent(): ?User
    {
        if (isset($this->parent_id) && $this->parent_id) {
            return $this->landlordUsersQuery()->find($this->parent_id);
        }

        if (method_exists($this, 'parent')) {
            return $this->parent()->getResults();
        }

        return null;
    }

    protected function landlordUsersQuery(): Builder
    {
        return User::on(config('tenancy.landlord_connection', 'landlord'));
    }

    protected function resolveOrganizationId(): ?int
    {
        if (isset($this->organization_id) && $this->organization_id) {
            return (int) $this->organization_id;
        }

        if (method_exists($this, 'organization')) {
            return (int) ($this->organization?->getKey() ?? 0) ?: null;
        }

        return null;
    }

    protected function notificationRedirectUrlForRecipient(User $recipient, string $event): string
    {
        [$panelId, $resourceClass] = $this->resolvePanelAndResourceForRecipient($recipient);
        if ($resourceClass !== null) {
            $indexUrl = $this->resolveResourceIndexUrlForRecipient($resourceClass, $panelId, $recipient);

            if ($indexUrl === null) {
                return $panelId === 'super-admin' ? url('/super-admin') : url('/');
            }

            if ($event === 'deleted') {
                return $indexUrl;
            }

            $separator = Str::contains($indexUrl, '?') ? '&' : '?';

            return "{$indexUrl}{$separator}" . http_build_query([
                'highlight_id' => $this->getKey(),
            ]);
        }

        $slug = Str::kebab(Str::plural(class_basename($this)));
        $panelPath = 'admin';
        $indexUrl = url("/{$panelPath}/{$slug}");

        if ($event === 'deleted') {
            return $indexUrl;
        }

        $separator = Str::contains($indexUrl, '?') ? '&' : '?';

        return "{$indexUrl}{$separator}" . http_build_query([
            'highlight_id' => $this->getKey(),
        ]);
    }

    /**
     * @param class-string<Resource> $resourceClass
     */
    protected function resolveResourceIndexUrlForRecipient(string $resourceClass, string $panelId, User $recipient): ?string
    {
        $params = [];

        if ($panelId === 'admin') {
            $tenant = $this->resolveTenantSlugForRecipient($recipient);
            if (blank($tenant)) {
                return null;
            }

            $params['tenant'] = $tenant;
        }

        try {
            return $resourceClass::getUrl('index', $params, panel: $panelId);
        } catch (Throwable) {
            return null;
        }
    }

    protected function resolveTenantSlugForRecipient(User $recipient): ?string
    {
        $routeTenant = request()->route('tenant');
        if (is_string($routeTenant) && $routeTenant !== '' && ! str_contains($routeTenant, '{')) {
            return $routeTenant;
        }

        $organization = $recipient->organization;
        if (! $organization) {
            return null;
        }

        $tenant = $organization->tenant;
        if (! $tenant) {
            return null;
        }

        return (string) ($tenant->slug ?: $tenant->id ?: null);
    }

    /**
     * @return class-string<Resource>|null
     */
    protected function resolveFilamentResourceForModel(string $panelId): ?string
    {
        $panel = Filament::getPanel($panelId);

        foreach ($panel->getResources() as $resource) {
            if (! is_string($resource) || ! class_exists($resource) || ! is_subclass_of($resource, Resource::class)) {
                continue;
            }

            if (! is_a($this, $resource::getModel())) {
                continue;
            }

            return $resource;
        }

        return null;
    }

    /**
     * @return array{0: string, 1: class-string<Resource>|null}
     */
    protected function resolvePanelAndResourceForRecipient(User $recipient): array
    {
        $preferredPanels = $recipient->isRole(UserRole::SUPER_ADMIN)
            ? ['super-admin', 'admin']
            : ['admin'];

        foreach ($preferredPanels as $panelId) {
            $resource = $this->resolveFilamentResourceForModel($panelId);

            if ($resource !== null) {
                return [$panelId, $resource];
            }
        }

        return ['admin', null];
    }
}
