<x-filament-panels::page>
    <form wire:submit="saveSettings" class="space-y-6">
        <x-filament::section heading="System Settings">
            <p class="text-sm text-gray-600">Organization Admin can control currency and module defaults from one place.</p>
        </x-filament::section>

        <x-filament::section heading="How These Settings Affect Modules">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="py-2 text-left font-semibold">Module</th>
                            <th class="py-2 text-left font-semibold">How Settings Are Used</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        <tr>
                            <td class="py-2">Quotations</td>
                            <td class="py-2">Default tax and discount</td>
                        </tr>
                        <tr>
                            <td class="py-2">Invoices</td>
                            <td class="py-2">Currency and late fee</td>
                        </tr>
                        <tr>
                            <td class="py-2">Payments</td>
                            <td class="py-2">Partial payment rule</td>
                        </tr>
                        <tr>
                            <td class="py-2">Inventory</td>
                            <td class="py-2">Price currency</td>
                        </tr>
                        <tr>
                            <td class="py-2">Orders</td>
                            <td class="py-2">Tax calculation</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section heading="Financial Defaults">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="space-y-1 text-sm">
                    <span class="font-medium">Currency</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.defer="system.currency">
                            @foreach (\App\Support\SystemSettings::currencyOptions() as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-medium">Payment Terms (Days)</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" min="1" wire:model.defer="system.payment_terms_days" />
                    </x-filament::input.wrapper>
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-medium">Default Tax (%)</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" min="0" max="100" step="0.01" wire:model.defer="system.default_tax_percent" />
                    </x-filament::input.wrapper>
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-medium">Default Discount (%)</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" min="0" max="100" step="0.01" wire:model.defer="system.default_discount_percent" />
                    </x-filament::input.wrapper>
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-medium">Late Fee (%)</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" min="0" max="100" step="0.01" wire:model.defer="system.late_fee_percent" />
                    </x-filament::input.wrapper>
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-medium">Tax Calculation Method</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.defer="system.tax_calculation_method">
                            <option value="exclusive">Tax Exclusive</option>
                            <option value="inclusive">Tax Inclusive</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-medium">Low Stock Threshold</span>
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" min="0" step="0.001" wire:model.defer="system.low_stock_threshold" />
                    </x-filament::input.wrapper>
                </label>
                <label class="space-y-1 text-sm md:col-span-2">
                    <span class="font-medium">Allow Partial Payments</span>
                    <span class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-white/10 px-3 py-2 text-sm">
                        <input type="checkbox" wire:model.defer="system.allow_partial_payments" class="rounded border-gray-300" />
                        Enabled
                    </span>
                </label>
                <label class="space-y-1 text-sm md:col-span-2">
                    <span class="font-medium">Auto-Approve Invoices</span>
                    <span class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-white/10 px-3 py-2 text-sm">
                        <input type="checkbox" wire:model.defer="system.auto_approve_invoices" class="rounded border-gray-300" />
                        Enabled
                    </span>
                </label>
            </div>
        </x-filament::section>

     

        <x-filament::button type="submit">
            Save Settings
        </x-filament::button>
    </form>
</x-filament-panels::page>
