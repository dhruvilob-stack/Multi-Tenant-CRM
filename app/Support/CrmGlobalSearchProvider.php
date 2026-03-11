<?php

namespace App\Support;

use CharrafiMed\GlobalSearchModal\GlobalSearchResults as ModalGlobalSearchResults;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrmGlobalSearchProvider implements GlobalSearchProvider
{
    protected array $tableColumns = [];

    public function getResults(string $query): ?ModalGlobalSearchResults
    {
        $search = trim($query);

        if ($search === '') {
            return ModalGlobalSearchResults::make();
        }

        $results = ModalGlobalSearchResults::make();
        $panel = Filament::getCurrentOrDefaultPanel();

        foreach ($panel->getResources() as $resource) {
            if (! is_string($resource) || ! class_exists($resource) || ! is_subclass_of($resource, Resource::class)) {
                continue;
            }

            if (method_exists($resource, 'canAccess') && ! $resource::canAccess()) {
                continue;
            }

            $resourceResults = $this->searchResource($resource, $search);

            if ($resourceResults->isEmpty()) {
                continue;
            }

            $results->category($resource::getNavigationLabel() ?: $resource::getPluralModelLabel(), $resourceResults);
        }

        $resourceNavigationResults = $this->searchResourceNavigation($search);
        if ($resourceNavigationResults->isNotEmpty()) {
            $results->category('Resources', $resourceNavigationResults);
        }

        $navigationResults = $this->searchNavigationPages($search);
        if ($navigationResults->isNotEmpty()) {
            $results->category('Navigation', $navigationResults);
        }

        return $results;
    }

    /**
     * @param class-string<Resource> $resource
     * @return Collection<int, GlobalSearchResult>
     */
    protected function searchResource(string $resource, string $search): Collection
    {
        /** @var Builder $eloquentQuery */
        $eloquentQuery = $resource::getGlobalSearchEloquentQuery();

        /** @var Model $model */
        $model = $resource::getModel()::query()->getModel();
        $table = $model->getTable();
        $searchableColumns = $this->resolveSearchableColumns($resource, $table, $model->getConnectionName());

        if ($searchableColumns === []) {
            return collect();
        }

        $eloquentQuery->where(function (Builder $builder) use ($searchableColumns, $search): void {
            foreach ($searchableColumns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $builder->{$method}($column, 'like', "%{$search}%");
            }
        });

        return $eloquentQuery
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(function (Model $record) use ($resource): ?GlobalSearchResult {
                $url = $resource::getGlobalSearchResultUrl($record);

                if (! filled($url)) {
                    return null;
                }

                return new GlobalSearchResult(
                    title: $this->resolveTitle($resource, $record),
                    url: $url,
                    details: $this->buildDefaultDetails($record),
                );
            })
            ->filter()
            ->values();
    }

    /**
     * @param class-string<Resource> $resource
     * @return array<int, string>
     */
    protected function resolveSearchableColumns(string $resource, string $table, ?string $connection = null): array
    {
        $resourceColumns = array_values(array_filter(
            $resource::getGloballySearchableAttributes(),
            fn (string $column): bool => ! Str::contains($column, '.'),
        ));

        $fallbackColumns = [];

        foreach ($this->getTableColumns($table, $connection) as $column) {
            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token', 'password'], true)) {
                continue;
            }

            if (Str::endsWith($column, '_id')) {
                continue;
            }

            $fallbackColumns[] = $column;
        }

        $combined = array_values(array_unique(array_merge($resourceColumns, $fallbackColumns)));

        return $this->onlyExistingColumns($table, $combined, $connection);
    }

    /**
     * @param class-string<Resource> $resource
     */
    protected function resolveTitle(string $resource, Model $record): string
    {
        try {
            $title = $resource::getGlobalSearchResultTitle($record);
            if (filled((string) $title)) {
                return (string) $title;
            }
        } catch (\Throwable) {
            // fallback
        }

        foreach ($this->getTableColumns($record->getTable(), $record->getConnectionName()) as $column) {
            if (preg_match('/(name|title|email|sku|code|number|reference|subject)$/', $column) !== 1) {
                continue;
            }

            $value = trim((string) ($record->getAttribute($column) ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return class_basename($record) . ' #' . $record->getKey();
    }

    /**
     * @return array<string, string>
     */
    protected function buildDefaultDetails(Model $record): array
    {
        $details = [];
        $tableColumns = $this->getTableColumns($record->getTable(), $record->getConnectionName());

        foreach (['status', 'role', 'email', 'created_at'] as $column) {
            if (! in_array($column, $tableColumns, true)) {
                continue;
            }

            $value = $record->getAttribute($column);
            if ($value === null || $value === '') {
                continue;
            }

            $details[Str::headline($column)] = (string) $value;
        }

        return $details;
    }

    /**
     * @return Collection<int, GlobalSearchResult>
     */
    protected function searchNavigationPages(string $search): Collection
    {
        $results = collect();
        $panel = Filament::getCurrentOrDefaultPanel();

        foreach ($panel->getPages() as $page) {
            if (! is_string($page) || ! class_exists($page) || ! is_subclass_of($page, Page::class)) {
                continue;
            }

            if (method_exists($page, 'canAccess') && ! $page::canAccess()) {
                continue;
            }

            $label = (string) ($page::getNavigationLabel() ?? class_basename($page));
            if (! Str::contains(Str::lower($label), Str::lower($search))) {
                continue;
            }

            $url = method_exists($page, 'getUrl') ? $page::getUrl() : null;
            if (! filled($url)) {
                continue;
            }

            $results->push(new GlobalSearchResult(
                title: $label,
                url: $url,
                details: ['Page' => $label],
            ));
        }

        return $results->values();
    }

    /**
     * @return Collection<int, GlobalSearchResult>
     */
    protected function searchResourceNavigation(string $search): Collection
    {
        $results = collect();
        $panel = Filament::getCurrentOrDefaultPanel();

        foreach ($panel->getResources() as $resource) {
            if (! is_string($resource) || ! class_exists($resource) || ! is_subclass_of($resource, Resource::class)) {
                continue;
            }

            if (method_exists($resource, 'canAccess') && ! $resource::canAccess()) {
                continue;
            }

            $label = (string) ($resource::getNavigationLabel() ?: $resource::getPluralModelLabel());
            if (! Str::contains(Str::lower($label), Str::lower($search))) {
                continue;
            }

            $url = $resource::getUrl('index');
            if (! filled($url)) {
                continue;
            }

            $results->push(new GlobalSearchResult(
                title: $label,
                url: $url,
                details: ['Resource' => $label],
            ));
        }

        return $results->values();
    }

    /**
     * @return array<int, string>
     */
    protected function getTableColumns(string $table, ?string $connection = null): array
    {
        $key = ($connection ?: 'default') . ':' . $table;

        if (! isset($this->tableColumns[$key])) {
            try {
                $schema = Schema::connection($connection);
                $this->tableColumns[$key] = $schema->getColumnListing($table);
            } catch (\Throwable) {
                $this->tableColumns[$key] = [];
            }
        }

        return $this->tableColumns[$key];
    }

    /**
     * @param array<int, string> $requested
     * @return array<int, string>
     */
    protected function onlyExistingColumns(string $table, array $requested, ?string $connection = null): array
    {
        $available = array_flip($this->getTableColumns($table, $connection));

        return array_values(array_filter($requested, fn (string $column): bool => isset($available[$column])));
    }
}
