<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2">
            <x-filament::section heading="Sent Mail Records">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="py-2">Subject</th>
                                <th class="py-2">To</th>
                                <th class="py-2">Sent At</th>
                                <th class="py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->messages as $m)
                                <tr class="border-b align-top">
                                    <td class="py-2">{{ $m['subject'] }}</td>
                                    <td class="py-2">{{ $m['to'] }}</td>
                                    <td class="py-2">{{ $m['sent_at'] }}</td>
                                    <td class="py-2">
                                        <x-filament::button size="xs" color="info" wire:click="openMail({{ $m['id'] }})">Open</x-filament::button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-3 text-gray-500">No sent mails found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>

        <div class="lg:col-span-1">
            <x-filament::section heading="Sent Mail Viewer">
                @if($this->openedMail)
                    <div class="space-y-2 text-sm">
                        <div><strong>Subject:</strong> {{ $this->openedMail['subject'] }}</div>
                        <div><strong>Sent:</strong> {{ $this->openedMail['sent_at'] }}</div>
                        <div><strong>To:</strong> {{ implode(', ', $this->openedMail['to']) }}</div>
                        @if(!empty($this->openedMail['cc']))
                            <div><strong>CC:</strong> {{ implode(', ', $this->openedMail['cc']) }}</div>
                        @endif
                        @if(!empty($this->openedMail['bcc']))
                            <div><strong>BCC:</strong> {{ implode(', ', $this->openedMail['bcc']) }}</div>
                        @endif
                        <div class="border rounded-lg p-3 bg-white max-h-[360px] overflow-auto">{!! $this->openedMail['body'] !!}</div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Open a sent mail record to view details.</p>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>

