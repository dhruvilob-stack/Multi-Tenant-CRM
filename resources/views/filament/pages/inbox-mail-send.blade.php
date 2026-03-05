<x-filament-panels::page>
    <form wire:submit="sendMail" class="space-y-6">
        <x-filament::section heading="Compose Mail" description="Create and send organization mail.">
            <div class="space-y-4">
                <div>
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="toQuery">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">To</span>
                    </label>

                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($this->to as $email)
                            <span class="fi-badge fi-color-gray inline-flex items-center gap-2 rounded-md px-2 py-1 text-xs">
                                {{ $email }}
                                <button type="button" wire:click="removeRecipient('{{ $email }}')" class="text-gray-500">x</button>
                            </span>
                        @endforeach
                    </div>

                    <div class="mt-2 flex gap-2">
                        <input
                            id="toQuery"
                            type="text"
                            wire:model.live.debounce.250ms="toQuery"
                            wire:keydown.enter.prevent="addRecipientFromQuery"
                            placeholder="Search by user name or email..."
                            class="fi-input block w-full"
                        />
                        <x-filament::button type="button" color="gray" wire:click="addRecipientFromQuery">Add</x-filament::button>
                    </div>

                    @if(!empty($this->toSuggestions))
                        <div class="mt-2 rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
                            @foreach($this->toSuggestions as $item)
                                <button
                                    type="button"
                                    class="block w-full border-b border-gray-100 px-3 py-2 text-left text-sm last:border-b-0 dark:border-white/10"
                                    wire:click="addRecipient('{{ $item['email'] }}')"
                                >
                                    {{ $item['label'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div>
                        <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="subject">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">Subject</span>
                        </label>
                        <input
                            id="subject"
                            type="text"
                            wire:model.defer="subject"
                            class="fi-input mt-2 block w-full"
                            placeholder="Enter mail subject"
                        />
                    </div>

                    <div>
                        <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="template_key">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">Template</span>
                        </label>
                        <div class="mt-2 flex gap-2">
                            <select id="template_key" wire:model.defer="template_key" class="fi-select-input block w-full">
                                <option value="">Select Template</option>
                                @foreach($this->templateOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-filament::button type="button" color="gray" wire:click="applyTemplate">Use</x-filament::button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div>
                        <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="order_id">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">Order</span>
                        </label>
                        <select id="order_id" wire:model.defer="order_id" class="fi-select-input mt-2 block w-full">
                            <option value="">Optional</option>
                            @foreach($this->orderOptions() as $id => $orderNo)
                                <option value="{{ $id }}">{{ $orderNo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="invoice_id">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">Invoice</span>
                        </label>
                        <select id="invoice_id" wire:model.defer="invoice_id" class="fi-select-input mt-2 block w-full">
                            <option value="">Optional</option>
                            @foreach($this->invoiceOptions() as $id => $invoiceNo)
                                <option value="{{ $id }}">{{ $invoiceNo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Mail Body">
            {{ $this->form }}
        </x-filament::section>

        <x-filament::section heading="Attachments & Options">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div>
                    <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3" for="media_files">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">Media Attachments</span>
                    </label>
                    <input id="media_files" type="file" multiple wire:model="media_files" class="fi-input mt-2 block w-full" />
                    @error('media_files.*')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2 text-sm">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model.defer="attach_invoice_pdf" class="rounded border-gray-300" />
                        <span>Attach Invoice PDF</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model.defer="attach_sales_report" class="rounded border-gray-300" />
                        <span>Attach Sales Dashboard PDF</span>
                    </label>
                </div>
            </div>
        </x-filament::section>

        <div class="flex justify-end">
            <x-filament::button type="submit" size="lg">Send Mail</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
