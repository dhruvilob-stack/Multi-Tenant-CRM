<div class="fi-sidebar-customize-tabs">
    <x-filament::modal
        :id="$this->getModalId()"
        heading="Customize Tabs"
        description="Use up/down arrows to reorder. Tabs move only within their group."
        width="4xl"
        teleport="body"
    >
        <x-slot name="trigger">
            <x-filament::button
                color="primary"
                :icon="\Filament\Support\Icons\Heroicon::OutlinedPencilSquare"
                size="sm"
                title="Customize Tabs"
                wire:click="open"
                class="fi-sidebar-customize-tabs-trigger"
            >
                <span class="fi-sidebar-customize-tabs-trigger-label">Customize Tabs</span>
            </x-filament::button>
        </x-slot>

        <form wire:submit.prevent="save" class="space-y-4">
            {{ $this->form }}

            <div class="flex items-center justify-end gap-2">
                <x-filament::button type="button" color="gray" wire:click="resetOrder">
                    Reset
                </x-filament::button>

                <x-filament::button type="submit">
                    Save
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>
</div>
