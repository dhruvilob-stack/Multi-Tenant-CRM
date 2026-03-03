<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use MDDev\DynamicDashboard\DashboardModelHelper;
use MDDev\DynamicDashboard\Models\Dashboard;
use MDDev\DynamicDashboard\Models\DashboardGrid;
use MDDev\DynamicDashboard\Models\DashboardWidget;

trait EnsuresResourceTabDashboards
{
    /**
     * @param array<string, array<class-string<Widget>>> $groupWidgetMap
     * @param array<class-string<Widget>> $fallbackWidgets
     */
    protected function ensureResourceTabDashboards(array $groupWidgetMap, array $fallbackWidgets = []): void
    {
        $panel = Filament::getCurrentPanel();

        if (! $panel) {
            return;
        }

        /** @var array<int, class-string<Resource>> $resources */
        $resources = collect($panel->getResources())
            ->filter(fn (mixed $resource): bool => is_string($resource) && class_exists($resource) && is_subclass_of($resource, Resource::class))
            ->values()
            ->all();

        if ($resources === []) {
            return;
        }

        $groups = collect($resources)
            ->map(fn (string $resource): string => (string) ($resource::getNavigationGroup() ?: 'General'))
            ->unique()
            ->values()
            ->all();

        if ($groups === []) {
            return;
        }

        $defaultGrid = DashboardGrid::query()->default()->first();
        $defaultBlockId = $defaultGrid?->rootBlocks()->orderBy('ordering')->value('id');

        $dashboardsQuery = DashboardModelHelper::model()::query()->where('page', static::class);
        $existingByName = $dashboardsQuery->get()->keyBy('name');

        foreach ($groups as $index => $group) {
            $name = $group . ' Dashboard';

            /** @var Dashboard $dashboard */
            $dashboard = $existingByName->get($name) ?? DashboardModelHelper::model()::query()->create([
                'name' => $name,
                'description' => sprintf('Custom dashboard for the "%s" navigation tab.', $group),
                'page' => static::class,
                'dashboard_grid_id' => $defaultGrid?->id,
                'is_active' => true,
                'is_locked' => false,
                'ordering' => $index + 1,
            ]);

            $widgetTypes = array_values(array_filter(array_unique([
                ...$fallbackWidgets,
                ...($groupWidgetMap[$group] ?? []),
            ]), fn (string $widget): bool => class_exists($widget) && is_subclass_of($widget, Widget::class)));

            if ($dashboard->widgets()->exists() || $widgetTypes === []) {
                continue;
            }

            foreach ($widgetTypes as $widgetIndex => $widgetType) {
                DashboardWidget::query()->create([
                    'dashboard_id' => $dashboard->id,
                    'dashboard_grid_block_id' => $defaultBlockId,
                    'name' => method_exists($widgetType, 'getWidgetLabel')
                        ? $widgetType::getWidgetLabel()
                        : Str::headline(class_basename($widgetType)),
                    'type' => $widgetType,
                    'columns' => 6,
                    'is_active' => true,
                    'display_title' => true,
                    'ordering' => $widgetIndex + 1,
                    'settings' => [],
                ]);
            }
        }
    }

    public function getAvailableDashboards(): Builder
    {
        return DashboardModelHelper::model()::query()
            ->where('is_active', true)
            ->where('page', static::class)
            ->orderBy('ordering');
    }
}
