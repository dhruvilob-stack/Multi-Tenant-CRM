<?php

namespace App\Filament\Pages;

use App\Services\OrganizationCurrencyService;
use App\Support\SystemSettings;
use App\Support\UserRole;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Settings extends Page
{
    protected string $view = 'filament.pages.settings';
    protected static ?string $slug = 'settings';
    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public array $system = [];

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function saveSettings(): void
    {
        $currencyCodes = implode(',', array_keys(SystemSettings::currencyOptions()));

        $validated = validator(
            ['system' => $this->system],
            [
                'system.currency' => ['required', 'string', 'in:'.$currencyCodes],
                'system.payment_terms_days' => ['required', 'integer', 'min:1', 'max:365'],
                'system.default_tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
                'system.default_discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
                'system.late_fee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
                'system.tax_calculation_method' => ['required', 'in:exclusive,inclusive'],
                'system.low_stock_threshold' => ['required', 'numeric', 'min:0'],
                'system.allow_partial_payments' => ['required', 'boolean'],
                'system.auto_approve_invoices' => ['required', 'boolean'],
            ]
        )->validate();

        $organization = auth()->user()?->organization;

        if (! $organization) {
            Notification::make()->danger()->title('Organization not found')->send();
            return;
        }

        $settings = (array) ($organization->settings ?? []);
        $previousSystem = array_replace(SystemSettings::defaults(), (array) ($settings['system'] ?? []));
        $previousCurrency = strtoupper((string) ($previousSystem['currency'] ?? SystemSettings::BASE_CURRENCY));
        $nextCurrency = strtoupper((string) ($validated['system']['currency'] ?? SystemSettings::BASE_CURRENCY));

        if ($previousCurrency !== $nextCurrency) {
            app(OrganizationCurrencyService::class)->convertOrganizationMonetaryData(
                $organization,
                $previousCurrency,
                $nextCurrency,
            );
        }

        $settings['system'] = $validated['system'];

        $organization->forceFill(['settings' => $settings])->saveQuietly();

        Notification::make()
            ->success()
            ->title('System settings saved')
            ->body('Currency and module defaults were applied successfully.')
            ->send();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.admin.groups.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.admin.pages.settings.nav');
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::ORG_ADMIN], true);
    }

    private function loadSettings(): void
    {
        $organization = auth()->user()?->organization;
        $this->system = SystemSettings::forOrganization($organization);
    }
}
