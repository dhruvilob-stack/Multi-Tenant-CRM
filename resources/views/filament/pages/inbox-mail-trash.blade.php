<x-filament-panels::page>
    <x-filament::section heading="Trash">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="py-2">Subject</th>
                        <th class="py-2">From</th>
                        <th class="py-2">Sent At</th>
                        <th class="py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->messages as $m)
                        <tr wire:key="trash-mail-{{ $m['id'] }}" class="border-b">
                            <td class="py-2">{{ $m['subject'] }}</td>
                            <td class="py-2">{{ $m['from'] }}</td>
                            <td class="py-2">{{ $m['sent_at'] }}</td>
                            <td class="py-2">
                                <div class="flex gap-2">
                                    <x-filament::button size="xs" color="info" wire:click="openMail({{ $m['id'] }})">Open</x-filament::button>
                                    <x-filament::button size="xs" color="success" wire:click="restore({{ $m['id'] }})">Restore</x-filament::button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-3 text-gray-500">Trash is empty.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
