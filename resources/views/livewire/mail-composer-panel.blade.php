<div class="space-y-3">
    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
        <div>
            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">To</label>
            <input wire:model.defer="to" class="fi-input mt-1 block w-full" placeholder="recipient@example.com, another@example.com" />
            @error('to') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Cc</label>
                <input wire:model.defer="cc" class="fi-input mt-1 block w-full" placeholder="cc@example.com" />
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Bcc</label>
                <input wire:model.defer="bcc" class="fi-input mt-1 block w-full" placeholder="bcc@example.com" />
            </div>
        </div>
    </div>

    <div>
        <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Subject</label>
        <input wire:model.defer="subject" class="fi-input mt-1 block w-full" placeholder="Subject" />
        @error('subject') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
    </div>

    <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
        <div class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-300">Use Mail Template</div>
        <div class="grid grid-cols-1 gap-2 lg:grid-cols-4">
            <select wire:model="templateKey" class="fi-select-input block w-full lg:col-span-3">
                <option value="">Select template...</option>
                @foreach($templateOptions as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <x-filament::button type="button" size="sm" color="gray" wire:click="applySelectedTemplate">Apply Template</x-filament::button>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 p-2 dark:border-white/10">
        {{ $this->form }}
        @error('data.content') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
    </div>

    <div id="mail-ai-inline-suggest" class="hidden text-xs text-gray-500"></div>

    <div
        class="rounded-xl border border-gray-200 p-3 dark:border-white/10"
        x-data="{
            captureSelectedPlaceholder() {
                const selected = (window.getSelection?.().toString?.() || '').trim()
                if (selected.startsWith('{{') && selected.endsWith('}}')) {
                    $wire.set('selectedPlaceholder', selected)
                } else {
                    $wire.set('selectedPlaceholder', '')
                }
            }
        }"
    >
        <div class="mb-2 text-xs font-medium text-gray-600 dark:text-gray-300">Quick Add Record Link</div>
        <div class="grid grid-cols-1 gap-2 lg:grid-cols-4">
            <select wire:model.live="recordType" class="fi-select-input block w-full">
                <option value="product">Product</option>
                <option value="inventory">Inventory</option>
                <option value="order">Order</option>
                <option value="category">Category</option>
            </select>
            <input wire:model.live.debounce.300ms="recordSearch" class="fi-input lg:col-span-2" placeholder="Search record..." />
            <x-filament::button
                type="button"
                size="sm"
                color="info"
                x-on:click="captureSelectedPlaceholder()"
                wire:click="insertRecordLink"
            >
                Insert Link
            </x-filament::button>
        </div>
        <select wire:model="recordSelection" class="fi-select-input mt-2 block w-full">
            @foreach($recordOptions as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex items-center justify-between gap-2 border-t border-gray-200 pt-2 dark:border-white/10">
        <div class="flex flex-wrap items-center gap-2">
            <x-filament::button
                type="button"
                size="sm"
                color="info"
                wire:click="generateAiFullDraft"
                wire:loading.attr="disabled"
                wire:target="generateAiFullDraft"
            >
                AI Mail
            </x-filament::button>
            <x-filament::button type="button" size="sm" color="primary" wire:click="sendMail">Send</x-filament::button>
            <span class="text-xs text-gray-500" wire:loading wire:target="generateAiFullDraft">Generating AI content...</span>
        </div>
    </div>
</div>
