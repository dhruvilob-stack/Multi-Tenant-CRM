<?php

namespace App\Filament\Concerns;

use App\Models\DashboardWidgetPreference;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HasDashboardArrangement
{
    /**
     * Set true on a dashboard page if you want to include every discovered panel widget.
     * Default false keeps the dashboard stable by using only page-defined widgets.
     */
    protected bool $includeDiscoveredDashboardWidgets = false;

    /**
     * @var array<int, array{widget: class-string<Widget>, label: string, visible: bool}>
     */
    public array $dashboardWidgetsForm = [];

    public function mountHasDashboardArrangement(): void
    {
        $this->dashboardWidgetsForm = $this->loadWidgetLayout();
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('customizeDashboard')
                ->label('Customize Dashboard')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('warning')
                ->modalWidth('3xl')
                ->slideOver()
                ->schema([
                    Repeater::make('widgets')
                        ->label('Drag to reorder widgets and toggle visibility')
                        ->schema([
                            Hidden::make('widget'),
                            TextInput::make('label')
                                ->label('Widget')
                                ->disabled()
                                ->dehydrated(false),
                            Toggle::make('visible')
                                ->label('Visible')
                                ->default(true),
                        ])
                        ->columns(2)
                        ->reorderableWithDragAndDrop()
                        ->addable(false)
                        ->deletable(false),
                ])
                ->fillForm(fn (): array => ['widgets' => $this->dashboardWidgetsForm])
                ->action(function (array $data): void {
                    $this->dashboardWidgetsForm = $this->sanitizeWidgetLayout(Arr::get($data, 'widgets', []));
                    $this->saveWidgetLayout($this->dashboardWidgetsForm);
                    $this->dashboardWidgetsForm = $this->loadWidgetLayout();

                    Notification::make()
                        ->title('Dashboard preferences saved')
                        ->success()
                        ->send();

                    $this->redirect($this->resolveDashboardUrl(), navigate: true);
                }),
            Action::make('resetDashboard')
                ->label('Reset Layout')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->resetWidgetLayout();
                    $this->dashboardWidgetsForm = $this->loadWidgetLayout();

                    Notification::make()
                        ->title('Dashboard layout reset')
                        ->success()
                        ->send();

                    $this->redirect($this->resolveDashboardUrl(), navigate: true);
                }),
        ];
    }

    /**
     * @return array<class-string<Widget>>
     */
    public function getWidgets(): array
    {
        $layout = $this->mergeNewWidgetsIntoLayout($this->dashboardWidgetsForm ?: $this->loadWidgetLayout());

        return collect($layout)
            ->filter(fn (array $item): bool => (bool) ($item['visible'] ?? false))
            ->pluck('widget')
            ->filter(fn ($widget): bool => is_string($widget) && class_exists($widget) && $widget::canView())
            ->values()
            ->all();
    }

    /**
     * @return array<class-string<Widget>>
     */
    protected function getDefaultDashboardWidgets(): array
    {
        return [];
    }

    /**
     * @return array<class-string<Widget>>
     */
    protected function getAvailableDashboardWidgets(): array
    {
        $widgets = $this->getDefaultDashboardWidgets();

        if ($this->includeDiscoveredDashboardWidgets) {
            $widgets = [
                ...$widgets,
                ...Filament::getWidgets(),
            ];
        }

        $widgets = array_values(array_unique($widgets));

        return collect($widgets)
            ->filter(fn ($widget): bool => is_string($widget) && class_exists($widget) && is_subclass_of($widget, Widget::class))
            ->values()
            ->all();
    }

    protected function resetWidgetLayout(): void
    {
        $user = Filament::auth()->user();
        $panelId = Filament::getCurrentPanel()?->getId();

        if (! $user || ! filled($panelId)) {
            return;
        }

        DashboardWidgetPreference::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('panel_id', $panelId)
            ->where('page', static::class)
            ->delete();

        $this->dashboardWidgetsForm = $this->defaultWidgetLayout();
    }

    /**
     * @return array<int, array{widget: class-string<Widget>, label: string, visible: bool}>
     */
    protected function loadWidgetLayout(): array
    {
        $user = Filament::auth()->user();
        $panelId = Filament::getCurrentPanel()?->getId();

        if (! $user || ! filled($panelId)) {
            return $this->defaultWidgetLayout();
        }

        $stored = DashboardWidgetPreference::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('panel_id', $panelId)
            ->where('page', static::class)
            ->value('widgets');

        $layout = $this->sanitizeWidgetLayout(is_array($stored) ? $stored : []);
        $layout = $this->mergeNewWidgetsIntoLayout($layout);

        return $layout === [] ? $this->defaultWidgetLayout() : $layout;
    }

    /**
     * @param array<int, array{widget?: mixed, label?: mixed, visible?: mixed}> $layout
     * @return array<int, array{widget: class-string<Widget>, label: string, visible: bool}>
     */
    protected function mergeNewWidgetsIntoLayout(array $layout): array
    {
        $available = $this->getAvailableDashboardWidgets();
        $availableSet = array_fill_keys($available, true);

        $normalized = [];
        foreach ($layout as $item) {
            $widget = is_string($item['widget'] ?? null) ? $item['widget'] : null;
            if (! $widget || ! isset($availableSet[$widget])) {
                continue;
            }

            $normalized[] = [
                'widget' => $widget,
                'label' => is_string($item['label'] ?? null) && $item['label'] !== '' ? $item['label'] : $this->widgetLabel($widget),
                'visible' => (bool) ($item['visible'] ?? true),
            ];
        }

        $existing = array_fill_keys(array_column($normalized, 'widget'), true);
        foreach ($available as $widget) {
            if (isset($existing[$widget])) {
                continue;
            }

            $normalized[] = [
                'widget' => $widget,
                'label' => $this->widgetLabel($widget),
                'visible' => true,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array{widget: class-string<Widget>, label: string, visible: bool}>
     */
    protected function defaultWidgetLayout(): array
    {
        return array_map(
            fn (string $widget): array => [
                'widget' => $widget,
                'label' => $this->widgetLabel($widget),
                'visible' => true,
            ],
            $this->getAvailableDashboardWidgets(),
        );
    }

    /**
     * @param array<int, array{widget?: mixed, label?: mixed, visible?: mixed}> $layout
     * @return array<int, array{widget: class-string<Widget>, label: string, visible: bool}>
     */
    protected function sanitizeWidgetLayout(array $layout): array
    {
        return $this->mergeNewWidgetsIntoLayout($layout);
    }

    /**
     * @param array<int, array{widget: class-string<Widget>, label: string, visible: bool}> $layout
     */
    protected function saveWidgetLayout(array $layout): void
    {
        $user = Filament::auth()->user();
        $panelId = Filament::getCurrentPanel()?->getId();

        if (! $user || ! filled($panelId)) {
            return;
        }

        DashboardWidgetPreference::query()->updateOrCreate(
            [
                'user_id' => $user->getAuthIdentifier(),
                'panel_id' => $panelId,
                'page' => static::class,
            ],
            [
                'widgets' => $layout,
            ],
        );
    }

    protected function widgetLabel(string $widget): string
    {
        return Str::headline(class_basename($widget));
    }

    protected function resolveDashboardUrl(): string
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        return static::getUrl(panel: $panelId);
    }
}
