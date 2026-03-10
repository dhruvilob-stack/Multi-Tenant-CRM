<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\Widgets\PlatformAuditEventsByTenantChart;
use App\Filament\Widgets\TenantHealthStats;
use App\Filament\Widgets\TenantStatusDistributionChart;
use App\Services\PlatformSettingsService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.super-admin.pages.settings';
    protected static ?string $slug = 'settings';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(
            app(PlatformSettingsService::class)->all()
        );
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.super_admin.groups.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.super_admin.pages.settings.nav');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Platform Defaults')
                    ->schema([
                        TextInput::make('system_name')
                            ->label('System Name')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('support_email')
                            ->label('Support Email')
                            ->email()
                            ->required(),
                        Select::make('default_currency')
                            ->label('Default Currency')
                            ->options([
                                'USD' => 'US Dollar (USD)',
                                'INR' => 'Indian Rupee (INR)',
                                'EUR' => 'Euro (EUR)',
                                'GBP' => 'British Pound (GBP)',
                                'AED' => 'UAE Dirham (AED)',
                            ])
                            ->required(),
                        TextInput::make('timezone')
                            ->label('Timezone')
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Monitoring & Tenant Controls')
                    ->schema([
                        Toggle::make('platform_maintenance_mode')
                            ->label('Platform Maintenance Mode'),
                        Toggle::make('tenant_auto_user_sync')
                            ->label('Auto Sync Tenant Users'),
                        Toggle::make('enforce_strict_tenant_isolation')
                            ->label('Enforce Tenant Isolation'),
                        Toggle::make('enable_usage_alerts')
                            ->label('Enable Usage Alerts'),
                        TextInput::make('audit_log_retention_days')
                            ->numeric()
                            ->minValue(7)
                            ->maxValue(3650)
                            ->required(),
                        TextInput::make('audit_log_realtime_poll_seconds')
                            ->numeric()
                            ->minValue(3)
                            ->maxValue(60)
                            ->required(),
                        TextInput::make('usage_alert_user_limit')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        TextInput::make('usage_alert_storage_limit_gb')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        app(PlatformSettingsService::class)->save($state);

        Notification::make()
            ->success()
            ->title('Global settings saved')
            ->send();
    }

    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         TenantHealthStats::class,
    //         TenantStatusDistributionChart::class,
    //         PlatformAuditEventsByTenantChart::class,
    //     ];
    // }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }
}
