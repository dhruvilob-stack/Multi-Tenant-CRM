<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PanelResourcesOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Resources Overview';

    protected function getColumns(): int
    {
        return 4;
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $panel = Filament::getCurrentPanel();

        if (! $panel) {
            return [];
        }

        $colors = ['primary', 'success', 'info', 'warning', 'danger'];
        $stats = [];
        $colorIndex = 0;

        foreach ($panel->getResources() as $resource) {
            if (! is_string($resource) || ! class_exists($resource) || ! is_subclass_of($resource, Resource::class)) {
                continue;
            }

            if (method_exists($resource, 'canAccess') && ! $resource::canAccess()) {
                continue;
            }

            try {
                $count = $resource::getEloquentQuery()->count();
            } catch (\Throwable) {
                continue;
            }

            $label = (string) ($resource::getNavigationLabel() ?: $resource::getPluralModelLabel());

            $stats[] = Stat::make($label, number_format((int) $count))
                ->description('Total records')
                ->color($colors[$colorIndex % count($colors)])
                ->icon('heroicon-o-rectangle-stack');

            $colorIndex++;
        }

        return $stats;
    }
}

