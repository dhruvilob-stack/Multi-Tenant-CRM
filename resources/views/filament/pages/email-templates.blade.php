<x-filament-panels::page>
    <form wire:submit="saveTemplates" class="space-y-6">
        <x-filament::section heading="Email Templates">
            <p class="text-sm text-gray-600">Manage invitation, quotation, invoice, and payment email templates for this organization.</p>
        </x-filament::section>

        <x-filament::section heading="Invitation Template">
            <x-filament::input.wrapper>
                <x-filament::input type="text" wire:model.defer="templates.invitation.subject" placeholder="Subject" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="mt-3">
                <textarea wire:model.defer="templates.invitation.body" rows="4" class="w-full rounded-lg border-gray-300"></textarea>
            </x-filament::input.wrapper>
        </x-filament::section>

        <x-filament::section heading="Quotation Template">
            <x-filament::input.wrapper>
                <x-filament::input type="text" wire:model.defer="templates.quotation.subject" placeholder="Subject" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="mt-3">
                <textarea wire:model.defer="templates.quotation.body" rows="4" class="w-full rounded-lg border-gray-300"></textarea>
            </x-filament::input.wrapper>
        </x-filament::section>

        <x-filament::section heading="Invoice Template">
            <x-filament::input.wrapper>
                <x-filament::input type="text" wire:model.defer="templates.invoice.subject" placeholder="Subject" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="mt-3">
                <textarea wire:model.defer="templates.invoice.body" rows="4" class="w-full rounded-lg border-gray-300"></textarea>
            </x-filament::input.wrapper>
        </x-filament::section>

        <x-filament::section heading="Payment Template">
            <x-filament::input.wrapper>
                <x-filament::input type="text" wire:model.defer="templates.payment.subject" placeholder="Subject" />
            </x-filament::input.wrapper>
            <x-filament::input.wrapper class="mt-3">
                <textarea wire:model.defer="templates.payment.body" rows="4" class="w-full rounded-lg border-gray-300"></textarea>
            </x-filament::input.wrapper>
        </x-filament::section>

        <x-filament::button type="submit">
            Save Templates
        </x-filament::button>
    </form>
</x-filament-panels::page>
