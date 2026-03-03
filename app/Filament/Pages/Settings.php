<?php

namespace App\Filament\Pages;

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
        $validated = validator(
            ['system' => $this->system],
            [
                'system.currency' => ['required', 'string', 'max:10'],
                'system.payment_terms_days' => ['required', 'integer', 'min:1', 'max:365'],
                'system.default_tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
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
        $settings['system'] = $validated['system'];

        $organization->forceFill(['settings' => $settings])->saveQuietly();

        Notification::make()->success()->title('System settings saved')->send();
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
        $defaults = [
            'currency' => 'USD',
            'payment_terms_days' => 15,
            'default_tax_percent' => 10,
            'low_stock_threshold' => 5,
            'allow_partial_payments' => true,
            'auto_approve_invoices' => false,
        ];

        $organization = auth()->user()?->organization;
        $settings = (array) ($organization?->settings ?? []);

        $this->system = array_replace($defaults, (array) ($settings['system'] ?? []));
    }
}
