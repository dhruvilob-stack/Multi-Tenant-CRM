<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

class PlatformSettingsService
{
    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'system_name' => 'CRM Control Center',
            'support_email' => 'support@example.com',
            'default_currency' => 'USD',
            'timezone' => config('app.timezone', 'UTC'),
            'platform_maintenance_mode' => false,
            'tenant_auto_user_sync' => true,
            'enforce_strict_tenant_isolation' => true,
            'audit_log_retention_days' => 180,
            'audit_log_realtime_poll_seconds' => 5,
            'enable_usage_alerts' => true,
            'usage_alert_user_limit' => 500,
            'usage_alert_storage_limit_gb' => 100,
            'subscription_currency' => 'USD',
            'subscription_tax_rate' => 0.18,
            'subscription_platform_fee' => 49,
            'subscription_plans' => [
                'starter' => [
                    'key' => 'starter',
                    'name' => 'Starter',
                    'price' => 999,
                    'billing_cycle' => 'month',
                    'visible' => true,
                    'limits' => [
                        'users' => 5,
                        'products' => 1000,
                    ],
                    'features' => [
                        'ai_email' => false,
                        'inventory' => true,
                        'analytics' => false,
                    ],
                ],
                'pro' => [
                    'key' => 'pro',
                    'name' => 'Pro',
                    'price' => 2499,
                    'billing_cycle' => 'month',
                    'visible' => true,
                    'limits' => [
                        'users' => 25,
                        'products' => 10000,
                    ],
                    'features' => [
                        'ai_email' => true,
                        'inventory' => true,
                        'analytics' => true,
                    ],
                ],
                'enterprise' => [
                    'key' => 'enterprise',
                    'name' => 'Enterprise',
                    'price' => 4999,
                    'billing_cycle' => 'month',
                    'visible' => true,
                    'limits' => [
                        'users' => null,
                        'products' => null,
                    ],
                    'features' => [
                        'ai_email' => true,
                        'inventory' => true,
                        'analytics' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $record = PlatformSetting::query()->first();
        $saved = (array) ($record?->settings ?? []);

        return array_replace($this->defaults(), $saved);
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function save(array $settings): void
    {
        $record = PlatformSetting::query()->firstOrNew(['id' => 1]);
        $record->settings = array_replace($this->defaults(), $settings);
        $record->save();
        Cache::forget('platform.timezone');
    }
}
