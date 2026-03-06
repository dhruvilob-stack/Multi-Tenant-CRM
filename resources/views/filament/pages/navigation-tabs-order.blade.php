<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <x-filament::section
            heading="Customize Navigation Tabs"
            description="Drag and reorder the sidebar tabs based on your workflow. Save once to apply."
        >
            {{ $this->form }}
        </x-filament::section>

        <div class="flex items-center justify-end gap-3">
            <x-filament::button type="button" color="gray" wire:click="resetOrder">
                Reset Default Order
            </x-filament::button>

            <x-filament::button type="submit">
                Save Navigation Order
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
