<?php

namespace App\Support;

use App\Models\Organization;

final class SystemSettings
{
    public const BASE_CURRENCY = 'USD';

    /**
     * @return array<string, string>
     */
    public static function currencyOptions(): array
    {
        return [
            'USD' => 'US Dollar (USD)',
            'INR' => 'Indian Rupee (INR)',
            'EUR' => 'Euro (EUR)',
            'GBP' => 'British Pound (GBP)',
            'AED' => 'UAE Dirham (AED)',
        ];
    }

    /**
     * Currency units per 1 USD.
     *
     * @return array<string, float>
     */
    public static function currencyRatesPerUsd(): array
    {
        return [
            'USD' => 1.0,
            'INR' => 91.0,
            'EUR' => 0.92,
            'GBP' => 0.78,
            'AED' => 3.67,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'currency' => self::BASE_CURRENCY,
            'payment_terms_days' => 15,
            'default_tax_percent' => 10.0,
            'default_discount_percent' => 0.0,
            'late_fee_percent' => 0.0,
            'tax_calculation_method' => 'exclusive',
            'low_stock_threshold' => 5.0,
            'allow_partial_payments' => true,
            'auto_approve_invoices' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function forOrganization(?Organization $organization): array
    {
        $settings = (array) ($organization?->settings ?? []);

        return array_replace(self::defaults(), (array) ($settings['system'] ?? []));
    }

    public static function currencyForOrganization(?Organization $organization): string
    {
        $system = self::forOrganization($organization);
        $currency = strtoupper((string) ($system['currency'] ?? self::BASE_CURRENCY));

        return array_key_exists($currency, self::currencyOptions()) ? $currency : self::BASE_CURRENCY;
    }

    public static function currencyForCurrentUser(): string
    {
        return self::currencyForOrganization(auth()->user()?->organization);
    }
}

