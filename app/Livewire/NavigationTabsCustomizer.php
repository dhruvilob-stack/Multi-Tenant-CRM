<?php

namespace App\Livewire;

use App\Support\NavigationPreferenceManager;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Livewire\Component;

class NavigationTabsCustomizer extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->loadItems();
    }

    public function render()
    {
        return view('livewire.navigation-tabs-customizer');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('groups')
                    ->label('Drag and drop to reorder groups and tabs')
                    ->schema([
                        Hidden::make('group_key'),
                        TextInput::make('group_label')
                            ->label('Group')
                            ->disabled()
                            ->columnSpanFull()
                            ->dehydrated(false),
                        Repeater::make('items')
                            ->label('Tabs')
                            ->schema([
                                Hidden::make('key'),
                                Hidden::make('group_key'),
                                TextInput::make('label')
                                    ->label('Tab')
                                    ->disabled()
                                    ->columnSpanFull()
                                    ->dehydrated(false),
                            ])
                            ->columns(1)
                            ->reorderableWithDragAndDrop()
                            ->addable(false)
                            ->deletable(false),
                    ])
                    ->columns(1)
                    ->reorderableWithDragAndDrop()
                    ->addable(false)
                    ->deletable(false),
            ])
            ->statePath('data');
    }

    public function open(): void
    {
        $this->loadItems();
    }

    public function save(): void
    {
        $panel = filament()->getCurrentPanel();
        $userId = (int) (auth()->id() ?? 0);

        if (! $panel || $userId <= 0) {
            return;
        }

        $availableGroups = app(NavigationPreferenceManager::class)->getCurrentGroupsForUser($panel, $userId);
        $availableByGroup = collect($availableGroups)
            ->mapWithKeys(fn (array $group): array => [
                $group['group_key'] => collect($group['items'])->pluck('key')->filter()->values()->all(),
            ])
            ->all();

        $submittedGroups = collect((array) ($this->data['groups'] ?? []))
            ->filter(fn ($group): bool => is_array($group))
            ->values();

        $groupOrder = $submittedGroups
            ->pluck('group_key')
            ->filter(fn ($groupKey): bool => is_string($groupKey) && isset($availableByGroup[$groupKey]))
            ->values()
            ->all();

        $missingGroups = collect(array_keys($availableByGroup))
            ->reject(fn (string $groupKey): bool => in_array($groupKey, $groupOrder, true))
            ->values()
            ->all();

        $groupOrder = [...$groupOrder, ...$missingGroups];

        $orderedKeys = [];
        foreach ($groupOrder as $groupKey) {
            $availableKeysInGroup = $availableByGroup[$groupKey] ?? [];
            $availableSet = array_fill_keys($availableKeysInGroup, true);

            $submittedGroup = $submittedGroups->first(
                fn (array $group): bool => ($group['group_key'] ?? null) === $groupKey
            );

            $submittedKeys = collect((array) ($submittedGroup['items'] ?? []))
                ->pluck('key')
                ->filter(fn ($key): bool => is_string($key) && isset($availableSet[$key]))
                ->values()
                ->all();

            $missingKeys = collect($availableKeysInGroup)
                ->reject(fn (string $key): bool => in_array($key, $submittedKeys, true))
                ->values()
                ->all();

            $orderedKeys = [...$orderedKeys, ...$submittedKeys, ...$missingKeys];
        }

        app(NavigationPreferenceManager::class)->saveOrderForUser($panel, $userId, $orderedKeys, $groupOrder);

        Notification::make()
            ->success()
            ->title('Navigation order saved')
            ->body('Tabs are arranged as per your order.')
            ->send();

        $this->js(sprintf(
            "window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: '%s' } })); window.location.reload();",
            $this->getModalId(),
        ));
    }

    public function resetOrder(): void
    {
        $panel = filament()->getCurrentPanel();
        $userId = (int) (auth()->id() ?? 0);

        if (! $panel || $userId <= 0) {
            return;
        }

        app(NavigationPreferenceManager::class)->resetOrderForUser($panel, $userId);
        $this->loadItems();

        Notification::make()
            ->success()
            ->title('Navigation order reset')
            ->body('Default tab order restored.')
            ->send();

        $this->js('window.location.reload()');
    }

    public function getModalId(): string
    {
        $panelId = filament()->getCurrentPanel()?->getId() ?? 'panel';
        $userId = (int) (auth()->id() ?? 0);

        return "navigation-tabs-customize-{$panelId}-{$userId}";
    }

    private function loadItems(): void
    {
        $panel = filament()->getCurrentPanel();
        $userId = (int) (auth()->id() ?? 0);

        if (! $panel || $userId <= 0) {
            $this->form->fill(['groups' => []]);

            return;
        }

        $groups = app(NavigationPreferenceManager::class)->getCurrentGroupsForUser($panel, $userId);

        $this->form->fill([
            'groups' => $groups,
        ]);
    }
}
