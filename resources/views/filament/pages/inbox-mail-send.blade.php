<x-filament-panels::page>
    <style>
        .mail-shell {
            background:
                radial-gradient(circle at 8% 0%, rgba(56, 189, 248, .15), transparent 42%),
                radial-gradient(circle at 90% 8%, rgba(16, 185, 129, .12), transparent 34%),
                linear-gradient(140deg, #f8fbff 0%, #f0fdfa 56%, #fff7ed 100%);
            border: 1px solid #bfdbfe;
            border-radius: 22px;
            padding: 20px;
            box-shadow: 0 20px 40px rgba(2, 132, 199, .08);
        }

        .mail-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 14px;
            padding: 10px 14px;
            background: linear-gradient(90deg, #1d4ed8, #0ea5e9);
            border-radius: 14px;
            color: #fff;
        }

        .mail-topbar h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: .2px;
        }

        .mail-topbar p {
            margin: 0;
            opacity: .92;
            font-size: 12px;
        }

        .mail-card {
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 8px 20px rgba(30, 64, 175, .06);
        }

        .mail-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .mail-title-badge {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: linear-gradient(135deg, #2563eb, #0ea5e9);
            box-shadow: 0 0 0 5px #dbeafe;
        }

        .field-shell {
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 10px;
            background: linear-gradient(180deg, #fff, #f8fafc);
        }

        .mail-chip {
            background: #eff6ff;
            border: 1px solid #93c5fd;
            color: #1e3a8a;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
        }

        .chip-remove {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fee2e2;
            color: #b91c1c;
            font-size: 11px;
            line-height: 1;
        }

        .mail-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #fff;
            padding: 8px 10px;
            font-size: 14px;
            outline: none;
        }

        .mail-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, .14);
        }

        .suggestion-box {
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 22px rgba(37, 99, 235, .14);
            max-height: 220px;
            overflow: auto;
        }

        .suggestion-item {
            width: 100%;
            text-align: left;
            padding: 10px 12px;
            font-size: 13px;
            border-bottom: 1px solid #eff6ff;
        }

        .suggestion-item:hover {
            background: #eff6ff;
        }

        .mail-footer {
            display: flex;
            align-items: center;
            justify-content: end;
            margin-top: 14px;
        }

        .send-btn {
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(37, 99, 235, .22);
        }
    </style>

    <form wire:submit="sendMail" class="space-y-5">
        <div class="mail-shell">
            <div class="mail-topbar">
                <div>
                    <h2>Organization Mail Composer</h2>
                    <p>Design rich internal communication with templates, media and smart suggestions.</p>
                </div>
                <x-filament::badge color="success">Interactive Composer</x-filament::badge>
            </div>

            <div class="mail-card">
                <div class="mail-title"><span class="mail-title-badge"></span>Recipients & Subject</div>

                <div>
                    <label class="text-sm font-medium text-slate-700">To</label>
                    <div class="field-shell mt-2">
                        <div class="flex flex-wrap gap-2 mb-2">
                            @foreach($this->to as $email)
                                <span class="mail-chip inline-flex items-center gap-2">
                                    {{ $email }}
                                    <button type="button" class="chip-remove" wire:click="removeRecipient('{{ $email }}')">x</button>
                                </span>
                            @endforeach
                        </div>

                        <div class="flex gap-2">
                            <input
                                type="text"
                                wire:model.live.debounce.250ms="toQuery"
                                wire:keydown.enter.prevent="addRecipientFromQuery"
                                placeholder="Search by user name or email..."
                                class="mail-input"
                            />
                            <x-filament::button type="button" color="info" wire:click="addRecipientFromQuery">Add</x-filament::button>
                        </div>

                        @if(!empty($this->toSuggestions))
                            <div class="suggestion-box mt-2">
                                @foreach($this->toSuggestions as $item)
                                    <button
                                        type="button"
                                        class="suggestion-item"
                                        wire:click="addRecipient('{{ $item['email'] }}')"
                                    >
                                        {{ $item['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="text-sm text-slate-700">Subject</label>
                        <input type="text" wire:model.defer="subject" class="mail-input mt-1" placeholder="Enter mail subject" />
                    </div>
                    <div>
                        <label class="text-sm text-slate-700">Template</label>
                        <div class="flex gap-2 mt-1">
                            <select wire:model.defer="template_key" class="mail-input">
                                <option value="">Select Template</option>
                                @foreach($this->templateOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-filament::button type="button" size="sm" color="info" wire:click="applyTemplate">Use</x-filament::button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="text-sm text-slate-700">Order ID</label>
                        <select wire:model.defer="order_id" class="mail-input mt-1">
                            <option value="">Optional</option>
                            @foreach($this->orderOptions() as $id => $orderNo)
                                <option value="{{ $id }}">{{ $orderNo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-slate-700">Invoice ID</label>
                        <select wire:model.defer="invoice_id" class="mail-input mt-1">
                            <option value="">Optional</option>
                            @foreach($this->invoiceOptions() as $id => $invoiceNo)
                                <option value="{{ $id }}">{{ $invoiceNo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="mail-card mt-4">
                <div class="mail-title"><span class="mail-title-badge"></span>Mail Body (Rich Text Style)</div>
                <div class="border border-sky-200 rounded-2xl p-3 bg-gradient-to-b from-sky-50 to-white">
                    {{ $this->form }}
                </div>
            </div>

            <div class="mail-card mt-4">
                <div class="mail-title"><span class="mail-title-badge"></span>Attachments & Output</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm">Media Attachments (images/docs)</label>
                        <input type="file" multiple wire:model="media_files" class="mail-input mt-1" />
                        @error('media_files.*')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-col gap-2 justify-center text-sm">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model.defer="attach_invoice_pdf" class="rounded border-gray-300" />
                            Attach Invoice PDF
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model.defer="attach_sales_report" class="rounded border-gray-300" />
                            Attach Sales Dashboard PDF
                        </label>
                    </div>
                </div>
            </div>

            <div class="mail-footer">
                <x-filament::button type="submit" size="lg" class="send-btn">Send Mail</x-filament::button>
            </div>
        </div>
    </form>

</x-filament-panels::page>
