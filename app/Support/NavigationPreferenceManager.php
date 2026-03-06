<?php

namespace App\Support;

use App\Models\UserNavigationPreference;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class NavigationPreferenceManager
{
    private const UNGROUPED_KEY = '__ungrouped__';

    /**
     * @return array<int, array{key: string, label: string, group: string}>
     */
    public function getCurrentItemsForUser(Panel $panel, int $userId): array
    {
        return collect($this->getCurrentGroupsForUser($panel, $userId))
            ->flatMap(fn (array $group): array => $group['items'])
            ->map(fn (array $item): array => [
                'key' => $item['key'],
                'label' => $item['label'],
                'group' => $item['group_label'],
            ])
            ->all();
    }

    /**
     * @return array<int, array{group_key: string, group_label: string, items: array<int, array{key: string, label: string, group_key: string, group_label: string}>}>
     */
    public function getCurrentGroupsForUser(Panel $panel, int $userId): array
    {
        $items = collect(filament()->getNavigation())
            ->flatMap(fn (NavigationGroup $group): array => $group->getItems())
            ->filter(fn (NavigationItem $item): bool => $item->isVisible() && (filled($item->getUrl()) || filled($item->getChildItems())))
            ->values();

        $sortedItems = $this->sortItemsForUser($panel, $items, $userId);
        $savedGroupOrder = $this->getSavedGroupOrder($panel, $userId);

        $grouped = [];

        foreach ($sortedItems as $item) {
            $groupLabel = $this->normalizeGroup($item->getGroup());
            $groupKey = $this->groupKeyFromLabel($groupLabel);

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'group_key' => $groupKey,
                    'group_label' => $this->groupLabelFromKey($groupKey),
                    'items' => [],
                ];
            }

            $grouped[$groupKey]['items'][] = [
                'key' => $this->itemKey($item),
                'label' => $item->getLabel(),
                'group_key' => $groupKey,
                'group_label' => $this->groupLabelFromKey($groupKey),
            ];
        }

        $groupedCollection = collect($grouped);

        if ($savedGroupOrder === []) {
            return $groupedCollection->values()->all();
        }

        $indexByGroup = [];
        foreach ($savedGroupOrder as $index => $groupKey) {
            $indexByGroup[$groupKey] = $index;
        }

        return $groupedCollection
            ->sortBy(fn (array $group): int => $indexByGroup[$group['group_key']] ?? 10000)
            ->values()
            ->all();
    }

    public function saveOrderForUser(Panel $panel, int $userId, array $keys, array $groupOrder = []): void
    {
        $normalized = collect($keys)
            ->filter(fn ($key): bool => is_string($key) && $key !== '')
            ->values()
            ->all();

        $normalizedGroups = collect($groupOrder)
            ->filter(fn ($groupKey): bool => is_string($groupKey) && $groupKey !== '')
            ->values()
            ->all();

        UserNavigationPreference::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'panel_id' => $panel->getId(),
            ],
            [
                'order_keys' => [
                    'item_keys' => $normalized,
                    'group_order' => $normalizedGroups,
                ],
            ],
        );
    }

    public function resetOrderForUser(Panel $panel, int $userId): void
    {
        UserNavigationPreference::query()
            ->where('user_id', $userId)
            ->where('panel_id', $panel->getId())
            ->delete();
    }

    public function applyToBuilderForCurrentUser(NavigationBuilder $builder, Panel $panel, NavigationManager $navigationManager): NavigationBuilder
    {
        $navigationManager->mountNavigation();

        $preparedItems = collect($navigationManager->getNavigationItems())
            ->filter(fn (NavigationItem $item): bool => $item->isVisible())
            ->sortBy(fn (NavigationItem $item): int => (int) ($item->getSort() ?? 0))
            ->values();

        $parentItems = $preparedItems->groupBy(fn (NavigationItem $item): string => $item->getParentItem() ?? '');
        $items = $parentItems->get('', collect())
            ->keyBy(fn (NavigationItem $item): string => $item->getLabel());

        $parentItems
            ->except([''])
            ->each(function (Collection $parentItemItems, string $parentItemLabel) use ($items): void {
                if (! $items->has($parentItemLabel)) {
                    return;
                }

                $items->get($parentItemLabel)->childItems($parentItemItems);
            });

        $items = $items
            ->filter(fn (NavigationItem $item): bool => filled($item->getChildItems()) || filled($item->getUrl()))
            ->values();

        $userId = (int) (auth()->id() ?? 0);
        if ($userId <= 0) {
            return $builder
                ->groups($navigationManager->getNavigationGroups())
                ->items($items->all());
        }

        $sortedItems = $this->sortItemsForUser($panel, $items, $userId);
        $navigationGroups = $this->buildNavigationGroups(
            $navigationManager->getNavigationGroups(),
            $sortedItems,
            $this->getSavedGroupOrder($panel, $userId),
        );

        return $builder->groups($navigationGroups);
    }

    /**
     * @param Collection<int, NavigationItem> $items
     * @return Collection<int, NavigationItem>
     */
    private function sortItemsForUser(Panel $panel, Collection $items, int $userId): Collection
    {
        $storedKeys = $this->getSavedPayload($panel, $userId)['item_keys'];
        if ($storedKeys === []) {
            return $items->sortBy(fn (NavigationItem $item): int => (int) ($item->getSort() ?? 0))->values();
        }

        $indexByKey = [];
        foreach ($storedKeys as $index => $key) {
            $indexByKey[(string) $key] = $index;
        }

        return $items
            ->sortBy(function (NavigationItem $item) use ($indexByKey): int {
                $key = $this->itemKey($item);

                return $indexByKey[$key] ?? (10000 + (int) ($item->getSort() ?? 0));
            })
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private function getSavedPayload(Panel $panel, int $userId): array
    {
        $stored = UserNavigationPreference::query()
            ->where('user_id', $userId)
            ->where('panel_id', $panel->getId())
            ->value('order_keys');

        if (! is_array($stored)) {
            return [
                'item_keys' => [],
                'group_order' => [],
            ];
        }

        if (array_is_list($stored)) {
            return [
                'item_keys' => collect($stored)
                    ->filter(fn ($key): bool => is_string($key) && $key !== '')
                    ->values()
                    ->all(),
                'group_order' => [],
            ];
        }

        return [
            'item_keys' => collect($stored['item_keys'] ?? [])
                ->filter(fn ($key): bool => is_string($key) && $key !== '')
                ->values()
                ->all(),
            'group_order' => collect($stored['group_order'] ?? [])
                ->filter(fn ($key): bool => is_string($key) && $key !== '')
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getSavedGroupOrder(Panel $panel, int $userId): array
    {
        return collect($this->getSavedPayload($panel, $userId)['group_order'])
            ->filter(fn ($key): bool => is_string($key) && $key !== '')
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, NavigationItem> $sortedItems
     * @return array<int, NavigationGroup>
     */
    private function buildNavigationGroups(array $registeredGroups, Collection $sortedItems, array $savedGroupOrder = []): array
    {
        $groupOrder = [];
        $groupTemplates = [];

        foreach ($registeredGroups as $registeredGroup) {
            if ($registeredGroup instanceof NavigationGroup) {
                $label = (string) ($registeredGroup->getLabel() ?? '');
                $key = $this->groupKeyFromLabel($label);
                $groupOrder[] = $key;
                $groupTemplates[$key] = clone $registeredGroup;
                continue;
            }

            if (! is_string($registeredGroup)) {
                continue;
            }

            $groupOrder[] = $this->groupKeyFromLabel($registeredGroup);
        }

        $grouped = [];
        foreach ($sortedItems as $item) {
            $groupKey = $this->groupKeyFromLabel($this->normalizeGroup($item->getGroup()));
            $grouped[$groupKey][] = $item;
        }

        $finalGroupOrder = $groupOrder;
        if ($savedGroupOrder !== []) {
            $known = array_fill_keys($groupOrder, true);
            $saved = collect($savedGroupOrder)
                ->filter(fn (string $groupKey): bool => isset($known[$groupKey]) || isset($grouped[$groupKey]))
                ->values()
                ->all();

            $finalGroupOrder = array_values(array_unique([
                ...$saved,
                ...$groupOrder,
            ]));
        }

        $navigationGroups = [];

        foreach ($finalGroupOrder as $groupKey) {
            if (! isset($grouped[$groupKey])) {
                continue;
            }

            if ($groupKey === self::UNGROUPED_KEY) {
                // Keep ungrouped items (e.g. Dashboard) with their own icons.
                $navigationGroups[] = NavigationGroup::make()->items($grouped[$groupKey]);
                unset($grouped[$groupKey]);
                continue;
            }

            $group = $groupTemplates[$groupKey] ?? NavigationGroup::make($this->groupLabelFromKey($groupKey));
            $group->icon($this->resolveGroupIcon($this->groupLabelFromKey($groupKey)));
            $group->items($this->stripItemIcons($grouped[$groupKey]));
            $navigationGroups[] = $group;

            unset($grouped[$groupKey]);
        }

        foreach ($grouped as $groupKey => $items) {
            if ($groupKey === self::UNGROUPED_KEY) {
                $navigationGroups[] = NavigationGroup::make()->items($items);
                continue;
            }

            $group = NavigationGroup::make($this->groupLabelFromKey($groupKey));
            $group->icon($this->resolveGroupIcon($this->groupLabelFromKey($groupKey)));
            $group->items($this->stripItemIcons($items));
            $navigationGroups[] = $group;
        }

        return $navigationGroups;
    }

    public function itemKey(NavigationItem $item): string
    {
        $url = $item->getUrl();
        if (filled($url)) {
            $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

            return 'url:'.$path;
        }

        return 'item:'.Str::slug($this->normalizeGroup($item->getGroup()).'|'.$item->getLabel());
    }

    private function normalizeGroup(string|UnitEnum|null $group): string
    {
        if ($group instanceof UnitEnum) {
            return $group->name;
        }

        return (string) ($group ?? '');
    }

    private function groupKeyFromLabel(string $label): string
    {
        return $label === '' ? self::UNGROUPED_KEY : $label;
    }

    private function groupLabelFromKey(string $groupKey): string
    {
        return $groupKey === self::UNGROUPED_KEY ? 'Ungrouped' : $groupKey;
    }

    /**
     * @param array<int, NavigationItem> $items
     * @return array<int, NavigationItem>
     */
    private function stripItemIcons(array $items): array
    {
        return array_map(function (NavigationItem $item): NavigationItem {
            $clone = clone $item;
            $clone->icon(null);
            $clone->activeIcon(null);

            return $clone;
        }, $items);
    }

    private function resolveGroupIcon(string $groupLabel): Heroicon
    {
        $label = Str::lower($groupLabel);

        return match (true) {
            Str::contains($label, ['configuration', 'config']) => Heroicon::OutlinedCog6Tooth,
            Str::contains($label, ['finance', 'billing', 'wallet', 'commission']) => Heroicon::OutlinedBanknotes,
            Str::contains($label, ['structure', 'organization', 'org']) => Heroicon::OutlinedBuildingOffice2,
            Str::contains($label, ['catalog', 'catealog']) => Heroicon::OutlinedTag,
            Str::contains($label, ['sales', 'shop']) => Heroicon::OutlinedShoppingCart,
            Str::contains($label, ['dashboard']) => Heroicon::OutlinedHome,
            Str::contains($label, ['inventory', 'stock']) => Heroicon::OutlinedArchiveBox,
            Str::contains($label, ['report', 'analytics']) => Heroicon::OutlinedChartBar,
            Str::contains($label, ['user', 'partner', 'team', 'hr']) => Heroicon::OutlinedUsers,
            Str::contains($label, ['mail', 'message', 'inbox']) => Heroicon::OutlinedEnvelope,
            Str::contains($label, ['order']) => Heroicon::OutlinedShoppingCart,
            Str::contains($label, ['quote', 'quotation']) => Heroicon::OutlinedDocumentText,
            Str::contains($label, ['setting', 'system', 'admin']) => Heroicon::OutlinedCog6Tooth,
            default => Heroicon::OutlinedSquares2x2,
        };
    }
}
