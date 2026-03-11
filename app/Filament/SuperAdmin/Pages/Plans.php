<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Services\PlatformSettingsService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class Plans extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.super-admin.pages.plans';
    protected static ?string $slug = 'plans';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCreditCard;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(PlatformSettingsService::class)->all();
        $settings['subscription_plans'] = $this->plansForForm((array) ($settings['subscription_plans'] ?? []));

        $this->form->fill($settings);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.super_admin.groups.tenant_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.super_admin.pages.plans.nav');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Pricing')
                    ->schema([
                        \Filament\Forms\Components\Select::make('subscription_currency')
                            ->label('Currency')
                            ->options([
                                'USD' => 'USD',
                                'INR' => 'INR',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                                'AED' => 'AED',
                                'SGD' => 'SGD',
                            ])
                            ->required(),
                        TextInput::make('subscription_tax_rate')
                            ->label('GST / Tax Rate')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->required(),
                        TextInput::make('subscription_platform_fee')
                            ->label('Platform Fee')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])
                    ->columns(3),
                Section::make('Subscription Plans')
                    ->description('Drag to reorder plans left-to-right. Add new plans as needed.')
                    ->schema([
                        Repeater::make('subscription_plans')
                            ->label('Plans')
                            ->reorderableWithDragAndDrop()
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null) ? (string) $state['name'] : (filled($state['key'] ?? null) ? (string) $state['key'] : 'Plan'))
                            ->schema([
                                TextInput::make('key')
                                    ->label('Plan Key')
                                    ->required()
                                    ->maxLength(120)
                                    ->helperText('Lowercase, unique identifier (e.g. starter, pro-plus).'),
                                TextInput::make('name')
                                    ->label('Plan Name')
                                    ->required()
                                    ->maxLength(120),
                                TextInput::make('price')
                                    ->label('Monthly Price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                                Select::make('billing_cycle')
                                    ->label('Billing Cycle')
                                    ->options([
                                        'month' => 'Monthly',
                                        'year' => 'Yearly',
                                    ])
                                    ->required(),
                                Toggle::make('visible')
                                    ->label('Visible to tenants'),
                                Toggle::make('popular')
                                    ->label('Most Popular badge'),
                                TextInput::make('discount_percent')
                                    ->label('Discount %')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100),
                                TextInput::make('discount_label')
                                    ->label('Discount Label')
                                    ->maxLength(80)
                                    ->helperText('Optional badge text, e.g. "Save 20%"'),
                                TextInput::make('limits.users')
                                    ->label('Users Limit')
                                    ->numeric()
                                    ->minValue(0)
                                    ->helperText('Use 0 for unlimited'),
                                TextInput::make('limits.products')
                                    ->label('Products Limit')
                                    ->numeric()
                                    ->minValue(0)
                                    ->helperText('Use 0 for unlimited'),
                                Toggle::make('features.ai_email')
                                    ->label('AI Email'),
                                Toggle::make('features.inventory')
                                    ->label('Inventory'),
                                Toggle::make('features.analytics')
                                    ->label('Analytics'),
                            ])
                            ->columns(3),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_plan')
                ->label('Add Plan')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->modalHeading('Create Subscription Plan')
                ->modalSubmitActionLabel('Add Plan')
                ->schema([
                    TextInput::make('key')
                        ->label('Plan Key')
                        ->required()
                        ->maxLength(120)
                        ->helperText('Unique identifier (e.g. starter, pro-plus).'),
                    TextInput::make('name')
                        ->label('Plan Name')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('price')
                        ->label('Monthly Price')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    Select::make('billing_cycle')
                        ->label('Billing Cycle')
                        ->options([
                            'month' => 'Monthly',
                            'year' => 'Yearly',
                        ])
                        ->default('month')
                        ->required(),
                    Toggle::make('visible')
                        ->label('Visible to tenants')
                        ->default(true),
                    Toggle::make('popular')
                        ->label('Most Popular badge')
                        ->default(false),
                    TextInput::make('discount_percent')
                        ->label('Discount %')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
                    TextInput::make('discount_label')
                        ->label('Discount Label')
                        ->maxLength(80)
                        ->helperText('Optional badge text, e.g. "Save 20%"'),
                    TextInput::make('limits.users')
                        ->label('Users Limit')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->helperText('Use 0 for unlimited'),
                    TextInput::make('limits.products')
                        ->label('Products Limit')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->helperText('Use 0 for unlimited'),
                    Toggle::make('features.ai_email')
                        ->label('AI Email')
                        ->default(false),
                    Toggle::make('features.inventory')
                        ->label('Inventory')
                        ->default(false),
                    Toggle::make('features.analytics')
                        ->label('Analytics')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $state = $this->form->getState();
                    $plans = (array) ($state['subscription_plans'] ?? []);
                    $plans[] = $data;
                    data_set($state, 'subscription_plans', $plans);
                    $this->form->fill($state);
                }),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $normalizedPlans = [];
        $seenKeys = [];
        foreach ((array) ($state['subscription_plans'] ?? []) as $index => $plan) {
            if (! is_array($plan)) {
                continue;
            }

            $keySource = trim((string) ($plan['key'] ?? $plan['name'] ?? ''));
            $key = Str::slug($keySource !== '' ? $keySource : ('plan-'.($index + 1)), '-');
            if ($key === '') {
                $key = 'plan-'.($index + 1);
            }
            $baseKey = $key;
            $suffix = 2;
            while (in_array($key, $seenKeys, true)) {
                $key = $baseKey.'-'.$suffix;
                $suffix++;
            }
            $seenKeys[] = $key;

            $users = data_get($plan, 'limits.users');
            $products = data_get($plan, 'limits.products');
            $plan['limits']['users'] = $this->normalizeLimit($users);
            $plan['limits']['products'] = $this->normalizeLimit($products);
            $plan['key'] = $key;
            $plan['billing_cycle'] = (string) ($plan['billing_cycle'] ?? 'month');
            $plan['visible'] = (bool) ($plan['visible'] ?? true);
            $plan['popular'] = (bool) ($plan['popular'] ?? false);
            $plan['discount_percent'] = is_numeric($plan['discount_percent'] ?? null)
                ? (float) $plan['discount_percent']
                : null;
            $plan['discount_label'] = (string) ($plan['discount_label'] ?? '');
            $plan['order'] = (int) $index;

            $normalizedPlans[$key] = $plan;
        }

        $state['subscription_plans'] = $normalizedPlans;

        app(PlatformSettingsService::class)->save($state);

        Notification::make()
            ->success()
            ->title('Subscription plans updated')
            ->send();
    }

    private function normalizeLimit(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $limit = (int) $value;

        return $limit <= 0 ? null : $limit;
    }

    /**
     * @param array<string, mixed> $plans
     * @return array<int, array<string, mixed>>
     */
    private function plansForForm(array $plans): array
    {
        return collect($plans)
            ->filter(fn ($plan): bool => is_array($plan))
            ->map(fn (array $plan): array => $plan)
            ->sortBy(fn (array $plan): int => (int) ($plan['order'] ?? 0))
            ->values()
            ->all();
    }
}
