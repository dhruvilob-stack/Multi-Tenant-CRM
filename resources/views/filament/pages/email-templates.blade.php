<x-filament-panels::page>
    <form wire:submit="saveTemplates" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end">
            <x-filament::button type="submit" color="primary">
                Save Templates
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
