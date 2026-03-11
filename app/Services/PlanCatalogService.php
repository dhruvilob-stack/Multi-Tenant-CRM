<?php

namespace App\Services;

class PlanCatalogService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $settings = app(PlatformSettingsService::class)->all();
        $plans = (array) ($settings['subscription_plans'] ?? []);

        return collect($plans)
            ->filter(fn ($plan): bool => is_array($plan))
            ->map(fn (array $plan): array => $this->normalizePlan($plan, $settings))
            ->sortBy(fn (array $plan): array => [
                (int) ($plan['order'] ?? 0),
                (string) ($plan['name'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function visible(): array
    {
        return collect($this->all())
            ->filter(fn (array $plan): bool => (bool) ($plan['visible'] ?? true))
            ->values()
            ->all();
    }

    public function find(string $key): ?array
    {
        return collect($this->all())
            ->first(fn (array $plan): bool => (string) ($plan['key'] ?? '') === $key);
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $settings = app(PlatformSettingsService::class)->all();

        return [
            'currency' => (string) ($settings['subscription_currency'] ?? 'USD'),
            'tax_rate' => (float) ($settings['subscription_tax_rate'] ?? 0),
            'platform_fee' => (float) ($settings['subscription_platform_fee'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $plan
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function normalizePlan(array $plan, array $settings): array
    {
        $defaults = [
            'key' => '',
            'name' => 'Plan',
            'price' => 0,
            'billing_cycle' => 'month',
            'visible' => true,
            'popular' => false,
            'order' => 0,
            'discount_percent' => null,
            'discount_label' => null,
            'limits' => [
                'users' => null,
                'products' => null,
            ],
            'features' => [
                'ai_email' => false,
                'inventory' => false,
                'analytics' => false,
            ],
        ];

        $plan = array_replace_recursive($defaults, $plan);
        $plan['price'] = (float) ($plan['price'] ?? 0);
        $plan['currency'] = (string) ($plan['currency'] ?? ($settings['subscription_currency'] ?? 'USD'));
        $plan['billing_cycle'] = (string) ($plan['billing_cycle'] ?? 'month');
        $plan['popular'] = (bool) ($plan['popular'] ?? false);
        $plan['order'] = (int) ($plan['order'] ?? 0);
        $plan['discount_percent'] = is_numeric($plan['discount_percent'] ?? null)
            ? (float) $plan['discount_percent']
            : null;
        $plan['discount_label'] = (string) ($plan['discount_label'] ?? '');

        $plan['limits'] = array_replace($defaults['limits'], (array) ($plan['limits'] ?? []));
        foreach (['users', 'products'] as $limitKey) {
            $value = $plan['limits'][$limitKey] ?? null;
            if ($value === 0 || $value === '0') {
                $plan['limits'][$limitKey] = null;
            }
        }
        $plan['features'] = array_replace($defaults['features'], (array) ($plan['features'] ?? []));

        return $plan;
    }
}
