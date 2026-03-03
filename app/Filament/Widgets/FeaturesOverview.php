<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class FeaturesOverview extends Widget
{
    protected string $view = 'filament.widgets.features-overview';

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = false;

    /**
     * @return array<int, array{name: string, icon: string, color: string, features: array<int, array{name: string, description: string, url: string, resource: string}>}>
     */
    public function getCategories(): array
    {
        $resources = $this->accessibleResources();
        $records = $this->sampleRecords($resources);

        return array_values(array_filter(array_map(
            fn (?array $category): ?array => $category && count($category['features']) > 0 ? $category : null,
            [
                $this->tablesCategory($resources),
                $this->formsCategory($resources, $records),
                $this->filtersCategory($resources),
                $this->actionsCategory($resources),
                $this->infolistsCategory($resources, $records),
                $this->pageActionsCategory($resources, $records),
                $this->navigationCategory($resources),
            ],
        )));
    }

    /**
     * @param list<class-string<Resource>> $resources
     * @return array{name: string, icon: string, color: string, features: list<array{name: string, description: string, url: string, resource: string}>}
     */
    protected function tablesCategory(array $resources): array
    {
        $features = [];

        foreach ($resources as $resource) {
            $features[] = $this->feature(
                $resource,
                'Searchable & sortable',
                'Use list tables with search, sortable columns, and record-level actions.',
                'index',
            );

            $features[] = $this->feature(
                $resource,
                'Column controls',
                'Use column visibility and table controls from the listing page.',
                'index',
            );
        }

        return [
            'name' => 'Tables & Columns',
            'icon' => 'heroicon-o-table-cells',
            'color' => 'blue',
            'features' => array_values(array_filter($features)),
        ];
    }

    /**
     * @param list<class-string<Resource>> $resources
     * @return array{name: string, icon: string, color: string, features: list<array{name: string, description: string, url: string, resource: string}>}
     */
    protected function filtersCategory(array $resources): array
    {
        $features = [];

        foreach ($resources as $resource) {
            $features[] = $this->feature(
                $resource,
                'Filters',
                'Open filter controls from the table toolbar to narrow data.',
                'index',
            );
        }

        return [
            'name' => 'Filters',
            'icon' => 'heroicon-o-funnel',
            'color' => 'violet',
            'features' => array_values(array_filter($features)),
        ];
    }

    /**
     * @param list<class-string<Resource>> $resources
     * @return array{name: string, icon: string, color: string, features: list<array{name: string, description: string, url: string, resource: string}>}
     */
    protected function actionsCategory(array $resources): array
    {
        $features = [];

        foreach ($resources as $resource) {
            $features[] = $this->feature(
                $resource,
                'Table Actions',
                'Use row actions, bulk actions, and confirmation modals from the table.',
                'index',
            );
        }

        return [
            'name' => 'Table Actions',
            'icon' => 'heroicon-o-bolt',
            'color' => 'amber',
            'features' => array_values(array_filter($features)),
        ];
    }

    /**
     * @param list<class-string<Resource>> $resources
     * @param array<class-string<Resource>, Model|null> $records
     * @return array{name: string, icon: string, color: string, features: list<array{name: string, description: string, url: string, resource: string}>}
     */
    protected function pageActionsCategory(array $resources, array $records): array
    {
        $features = [];

        foreach ($resources as $resource) {
            $record = $records[$resource] ?? null;

            $features[] = $this->feature(
                $resource,
                'Header Actions',
                'Use page header actions on list pages for quick workflows.',
                'index',
            );

            if ($record) {
                $features[] = $this->feature(
                    $resource,
                    'Record Header Actions',
                    'Use edit/view page header actions for this record.',
                    'edit',
                    ['record' => $record],
                );
            }
        }

        return [
            'name' => 'Page & Header Actions',
            'icon' => 'heroicon-o-rectangle-stack',
            'color' => 'rose',
            'features' => array_values(array_filter($features)),
        ];
    }

    /**
     * @param list<class-string<Resource>> $resources
     * @param array<class-string<Resource>, Model|null> $records
     * @return array{name: string, icon: string, color: string, features: list<array{name: string, description: string, url: string, resource: string}>}
     */
    protected function formsCategory(array $resources, array $records): array
    {
        $features = [];

        foreach ($resources as $resource) {
            if ($this->canCreate($resource)) {
                $features[] = $this->feature(
                    $resource,
                    'Create Form',
                    'Open create form to manage this resource.',
                    'create',
                );
            }
        }

        return [
            'name' => 'Forms',
            'icon' => 'heroicon-o-pencil-square',
            'color' => 'emerald',
            'features' => array_values(array_filter($features)),
        ];
    }

    /**
     * @param list<class-string<Resource>> $resources
     * @param array<class-string<Resource>, Model|null> $records
     * @return array{name: string, icon: string, color: string, features: list<array{name: string, description: string, url: string, resource: string}>}
     */
    protected function infolistsCategory(array $resources, array $records): array
    {
        $features = [];

        foreach ($resources as $resource) {
            $record = $records[$resource] ?? null;

            if (! $record) {
                continue;
            }

            $features[] = $this->feature(
                $resource,
                'Infolist / View',
                'Open read-only detail view for existing record information.',
                'view',
                ['record' => $record],
            );
        }

        return [
            'name' => 'Infolists',
            'icon' => 'heroicon-o-eye',
            'color' => 'cyan',
            'features' => array_values(array_filter($features)),
        ];
    }

    /**
     * @param list<class-string<Resource>> $resources
     * @return array{name: string, icon: string, color: string, features: list<array{name: string, description: string, url: string, resource: string}>}
     */
    protected function navigationCategory(array $resources): array
    {
        $features = [];

        foreach ($resources as $resource) {
            $features[] = $this->feature(
                $resource,
                'Navigation Entry',
                'Resource is visible in role-based panel navigation.',
                'index',
            );
        }

        return [
            'name' => 'Navigation & Pages',
            'icon' => 'heroicon-o-squares-2x2',
            'color' => 'gray',
            'features' => array_values(array_filter($features)),
        ];
    }

    /**
     * @param class-string<Resource> $resource
     * @param array<string, mixed> $params
     * @return array{name: string, description: string, url: string, resource: string}|null
     */
    protected function feature(string $resource, string $name, string $description, string $page = 'index', array $params = []): ?array
    {
        if (! class_exists($resource) || ! is_subclass_of($resource, Resource::class)) {
            return null;
        }

        $url = $this->resourceUrl($resource, $page, $params);

        if (! $url) {
            return null;
        }

        return [
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'resource' => $this->resourceLabel($resource),
        ];
    }

    /**
     * @return list<class-string<Resource>>
     */
    protected function accessibleResources(): array
    {
        $panel = Filament::getCurrentPanel();

        if (! $panel) {
            return [];
        }

        $resources = [];

        foreach ($panel->getResources() as $resource) {
            if (! is_string($resource) || ! class_exists($resource) || ! is_subclass_of($resource, Resource::class)) {
                continue;
            }

            if (! $this->canUseResource($resource)) {
                continue;
            }

            $resources[] = $resource;
        }

        if ($resources !== []) {
            return array_values(array_unique($resources));
        }

        // Fallback to all panel resources to avoid blank widget in edge-case permission setups.
        return array_values(array_filter(
            $panel->getResources(),
            fn (mixed $resource): bool => is_string($resource) && class_exists($resource) && is_subclass_of($resource, Resource::class),
        ));
    }

    /**
     * @param list<class-string<Resource>> $resources
     * @return array<class-string<Resource>, Model|null>
     */
    protected function sampleRecords(array $resources): array
    {
        $records = [];

        foreach ($resources as $resource) {
            $records[$resource] = $this->firstRecordForResource($resource);
        }

        return $records;
    }

    /**
     * @param class-string<Resource> $resource
     */
    protected function canUseResource(string $resource): bool
    {
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
     * @param class-string<Resource> $resource
     */
    protected function canCreate(string $resource): bool
    {
        try {
            return ! method_exists($resource, 'canCreate') || $resource::canCreate();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param class-string<Resource> $resource
     */
    protected function canEdit(string $resource, Model $record): bool
    {
        try {
            return ! method_exists($resource, 'canEdit') || $resource::canEdit($record);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param class-string<Resource> $resource
     */
    protected function firstRecordForResource(string $resource): ?Model
    {
        try {
            /** @var Model|null $record */
            $record = $resource::getEloquentQuery()->first();

            return $record;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param class-string<Resource> $resource
     * @param array<string, mixed> $params
     */
    protected function resourceUrl(string $resource, string $page, array $params = []): ?string
    {
        try {
            $pages = array_keys($resource::getPages());

            if (! in_array($page, $pages, true)) {
                $page = in_array('index', $pages, true) ? 'index' : (string) Arr::first($pages);
            }

            if ($page === '') {
                return null;
            }

            return $resource::getUrl($page, $params);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param class-string<Resource> $resource
     */
    protected function resourceLabel(string $resource): string
    {
        try {
            $label = $resource::getNavigationLabel() ?: $resource::getPluralModelLabel();

            return (string) ($label ?: Str::headline(class_basename($resource)));
        } catch (\Throwable) {
            return Str::headline(class_basename($resource));
        }
    }
}
