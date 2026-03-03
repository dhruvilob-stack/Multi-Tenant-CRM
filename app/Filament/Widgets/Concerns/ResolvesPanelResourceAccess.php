<?php

namespace App\Filament\Widgets\Concerns;

use Filament\Facades\Filament;
use Filament\Resources\Resource;

trait ResolvesPanelResourceAccess
{
    /**
     * @param class-string<Resource> $resource
     */
    protected static function canUseResource(string $resource): bool
    {
        $panel = Filament::getCurrentPanel();

        if (! $panel) {
            return false;
        }

        if (! in_array($resource, $panel->getResources(), true)) {
            return false;
        }

        try {
            if (method_exists($resource, 'shouldRegisterNavigation') && ! $resource::shouldRegisterNavigation()) {
                return false;
            }

            if (method_exists($resource, 'canAccess') && ! $resource::canAccess()) {
                return false;
            }

            if (method_exists($resource, 'canViewAny') && ! $resource::canViewAny()) {
                return false;
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param list<class-string<Resource>> $resources
     */
    protected static function canUseAnyResource(array $resources): bool
    {
        foreach ($resources as $resource) {
            if (static::canUseResource($resource)) {
                return true;
            }
        }

        return false;
    }
}
