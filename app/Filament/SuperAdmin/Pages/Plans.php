<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Services\PlatformSettingsService;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Plans extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.super-admin.pages.plans';
    protected static ?string $slug = 'plans';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCreditCard;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(app(PlatformSettingsService::class)->all());
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
                Section::make('Starter Plan')
                    ->schema($this->planSchema('starter'))
                    ->columns(3),
                Section::make('Pro Plan')
                    ->schema($this->planSchema('pro'))
                    ->columns(3),
                Section::make('Enterprise Plan')
                    ->schema($this->planSchema('enterprise'))
                    ->columns(3),
            ])
            ->statePath('data');
    }

    /**
     * @return array<int, \\Filament\\Forms\\Components\\Component>
     */
    private function planSchema(string $key): array
    {
        return [
            TextInput::make("subscription_plans.{$key}.name")
                ->label('Plan Name')
                ->required()
                ->maxLength(120),
            TextInput::make("subscription_plans.{$key}.price")
                ->label('Monthly Price')
                ->numeric()
                ->minValue(0)
                ->required(),
            Toggle::make("subscription_plans.{$key}.visible")
                ->label('Visible to tenants'),
            TextInput::make("subscription_plans.{$key}.limits.users")
                ->label('Users Limit')
                ->numeric()
                ->minValue(0)
                ->helperText('Use 0 for unlimited'),
            TextInput::make("subscription_plans.{$key}.limits.products")
                ->label('Products Limit')
                ->numeric()
                ->minValue(0)
                ->helperText('Use 0 for unlimited'),
            Toggle::make("subscription_plans.{$key}.features.ai_email")
                ->label('AI Email'),
            Toggle::make("subscription_plans.{$key}.features.inventory")
                ->label('Inventory'),
            Toggle::make("subscription_plans.{$key}.features.analytics")
                ->label('Analytics'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (['starter', 'pro', 'enterprise'] as $key) {
            $users = data_get($state, "subscription_plans.{$key}.limits.users");
            $products = data_get($state, "subscription_plans.{$key}.limits.products");
            data_set($state, "subscription_plans.{$key}.limits.users", $this->normalizeLimit($users));
            data_set($state, "subscription_plans.{$key}.limits.products", $this->normalizeLimit($products));
            data_set($state, "subscription_plans.{$key}.key", $key);
            data_set($state, "subscription_plans.{$key}.billing_cycle", 'month');
        }

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
}
