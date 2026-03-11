<x-filament-panels::page>
    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit">Save Plans</x-filament::button>
        </div>
    </form>

    @php($plans = app(\App\Services\PlanCatalogService::class)->visible())

    <x-filament::section heading="Plan Feature Matrix" class="mt-6">
        <div class="grid gap-4 md:grid-cols-3">
            @foreach($plans as $plan)
                <x-filament::section :heading="($plan['name'] ?? 'Plan')" :description="sprintf('%s / %s', number_format((float) ($plan['price'] ?? 0), 2), $plan['billing_cycle'] ?? 'month')">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span>Users</span>
                            <span>
                                {{ ($plan['limits']['users'] ?? null) ? (string) $plan['limits']['users'] : 'Unlimited' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Products</span>
                            <span>
                                {{ ($plan['limits']['products'] ?? null) ? (string) $plan['limits']['products'] : 'Unlimited' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>AI Email</span>
                            <span>{{ ($plan['features']['ai_email'] ?? false) ? 'Yes' : 'No' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Inventory</span>
                            <span>{{ ($plan['features']['inventory'] ?? false) ? 'Yes' : 'No' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Analytics</span>
                            <span>{{ ($plan['features']['analytics'] ?? false) ? 'Yes' : 'No' }}</span>
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
