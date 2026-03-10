<x-filament-panels::page>
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
                        <tr wire:key="inbox-mail-{{ $m['id'] }}" class="border-b align-top {{ $m['read_at'] ? '' : 'font-semibold' }}">
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
                                    @if(!$m['read_at'])
                                        <x-filament::button size="xs" color="success" wire:click="readMail({{ $m['id'] }})">Read</x-filament::button>
                                    @else
                                        <x-filament::button size="xs" color="info" wire:click="openMail({{ $m['id'] }})">View</x-filament::button>
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
</x-filament-panels::page>
