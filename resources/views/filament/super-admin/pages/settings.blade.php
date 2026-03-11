<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{-- <x-filament::section :heading="__('filament.super_admin.pages.settings.heading')">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('filament.super_admin.pages.settings.description') }}
            </p>
        </x-filament::section> --}}

        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        <div class="flex justify-end">
            <x-filament::button type="submit">
                Save Settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
