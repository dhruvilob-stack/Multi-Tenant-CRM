<x-filament-panels::page>
    <form wire:submit="saveSettings" class="space-y-6">
        <x-filament::section heading="System Settings">
            <p class="text-sm text-gray-600">Configure defaults used by quotation, invoice, inventory, and payment workflows.</p>
        </x-filament::section>

        <x-filament::section heading="Financial Defaults">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-filament::input.wrapper>
                    <x-filament::input type="text" wire:model.defer="system.currency" placeholder="Currency (e.g. USD)" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="number" min="1" wire:model.defer="system.payment_terms_days" placeholder="Payment terms (days)" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="number" min="0" max="100" step="0.01" wire:model.defer="system.default_tax_percent" placeholder="Default tax %" />
                </x-filament::input.wrapper>
                <x-filament::input.wrapper>
                    <x-filament::input type="number" min="0" step="0.001" wire:model.defer="system.low_stock_threshold" placeholder="Low stock threshold" />
                </x-filament::input.wrapper>
            </div>
        </x-filament::section>

        <x-filament::section heading="Workflow Controls">
            <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.defer="system.allow_partial_payments" class="rounded border-gray-300" />
                    Allow partial payments
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.defer="system.auto_approve_invoices" class="rounded border-gray-300" />
                    Auto-approve invoices on generation
                </label>
            </div>
        </x-filament::section>

        <x-filament::button type="submit">
            Save Settings
        </x-filament::button>
    </form>
</x-filament-panels::page>
