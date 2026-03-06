<?php

namespace App\Filament\Pages;

use App\Support\NavigationPreferenceManager;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class NavigationTabsOrder extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.navigation-tabs-order';

    protected static ?string $slug = 'settings/navigation-order';

    protected static bool $shouldRegisterNavigation = false;

    protected static string | null | \BackedEnum $navigationIcon = Heroicon::OutlinedBars3BottomLeft;

    public ?array $data = [];

    public function mount(): void
    {
        $panel = filament()->getCurrentPanel();
        $userId = (int) (auth()->id() ?? 0);

        if (! $panel || $userId <= 0) {
            $this->form->fill(['items' => []]);
            return;
        }

        $items = app(NavigationPreferenceManager::class)->getCurrentItemsForUser($panel, $userId);

        $this->form->fill([
            'items' => $items,
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('items')
                    ->label('Drag and drop to set the sidebar order')
                    ->schema([
                        Hidden::make('key'),
                        TextInput::make('label')
                            ->label('Tab')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('group')
                            ->label('Group')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->reorderableWithDragAndDrop()
                    ->addable(false)
                    ->deletable(false),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $panel = filament()->getCurrentPanel();
        $userId = (int) (auth()->id() ?? 0);

        if (! $panel || $userId <= 0) {
            return;
        }

        $availableItems = app(NavigationPreferenceManager::class)->getCurrentItemsForUser($panel, $userId);
        $availableKeys = collect($availableItems)->pluck('key')->filter()->values()->all();
        $availableSet = array_fill_keys($availableKeys, true);

        $submitted = collect((array) ($this->data['items'] ?? []))
            ->pluck('key')
            ->filter(fn ($key): bool => is_string($key) && isset($availableSet[$key]))
            ->values();

        $missing = collect($availableKeys)->reject(fn (string $key): bool => $submitted->contains($key))->values();
        $orderedKeys = $submitted->concat($missing)->all();

        app(NavigationPreferenceManager::class)->saveOrderForUser($panel, $userId, $orderedKeys);

        Notification::make()
            ->success()
            ->title('Navigation order saved')
            ->body('Sidebar tabs are now arranged as per your preference.')
            ->send();
    }

    public function resetOrder(): void
    {
        $panel = filament()->getCurrentPanel();
        $userId = (int) (auth()->id() ?? 0);

        if (! $panel || $userId <= 0) {
            return;
        }

        app(NavigationPreferenceManager::class)->resetOrderForUser($panel, $userId);
        $this->mount();

        Notification::make()
            ->success()
            ->title('Navigation order reset')
            ->body('Default sidebar order is restored.')
            ->send();
    }
}
