<div class="fi-resource-relation-manager space-y-4">
    <x-filament::section>
        <div class="w-full text-center text-base font-semibold tracking-wide">
            Audit Logs
        </div>
    </x-filament::section>

    <x-filament::section>
        {{ $this->content }}
    </x-filament::section>

    <x-filament-panels::unsaved-action-changes-alert />
</div>
