<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2">
            <x-filament::section heading="Inbox">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="py-2">Feature</th>
                                <th class="py-2">Subject</th>
                                <th class="py-2">From</th>
                                <th class="py-2">Sent</th>
                                <th class="py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->messages as $m)
                                <tr class="border-b align-top {{ $m['read_at'] ? '' : 'font-semibold' }}">
                                    <td class="py-2">
                                        <button type="button" wire:click="toggleFeatured({{ $m['id'] }})" class="text-lg leading-none">
                                            {{ $m['featured'] ? '★' : '☆' }}
                                        </button>
                                    </td>
                                    <td class="py-2">{{ $m['subject'] }}</td>
                                    <td class="py-2">{{ $m['from'] }}</td>
                                    <td class="py-2">{{ $m['sent_at'] }}</td>
                                    <td class="py-2">
                                        <div class="flex gap-2">
                                            <x-filament::button size="xs" color="info" wire:click="openMail({{ $m['id'] }})">Open</x-filament::button>
                                            @if(!$m['read_at'])
                                                <x-filament::button size="xs" color="success" wire:click="markRead({{ $m['id'] }})">Read</x-filament::button>
                                            @endif
                                            <x-filament::button size="xs" color="gray" wire:click="moveToTrash({{ $m['id'] }})">Trash</x-filament::button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-3 text-gray-500">No mails in inbox.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>

        <div class="lg:col-span-1">
            <x-filament::section heading="Mail Viewer">
                @if($this->openedMail)
                    <div class="space-y-2 text-sm">
                        <div><strong>Subject:</strong> {{ $this->openedMail['subject'] }}</div>
                        <div><strong>From:</strong> {{ $this->openedMail['from'] }}</div>
                        <div><strong>Sent:</strong> {{ $this->openedMail['sent_at'] }}</div>
                        <div class="border rounded-lg p-3 bg-white max-h-[360px] overflow-auto">{!! $this->openedMail['body'] !!}</div>

                        @if(!empty($this->openedMail['attachments']))
                            <div>
                                <div class="font-medium mb-1">Attachments:</div>
                                <ul class="space-y-2">
                                    @foreach($this->openedMail['attachments'] as $attachment)
                                        <li class="text-xs break-all border rounded p-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <span>{{ $attachment['name'] }} ({{ $attachment['mime'] }})</span>
                                                <a href="{{ $attachment['download_url'] }}" class="text-primary-600 underline">Download</a>
                                            </div>
                                            @if($attachment['is_image'])
                                                <div class="mt-2">
                                                    <img src="{{ $attachment['preview_url'] }}" alt="{{ $attachment['name'] }}" class="max-h-40 rounded border" />
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <a href="{{ $this->openedMail['reply_url'] }}" class="inline-flex items-center px-3 py-1.5 rounded-md bg-primary-600 text-white text-xs">Reply</a>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Open a mail to view details and reply.</p>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
