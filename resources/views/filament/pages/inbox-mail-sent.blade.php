<x-filament-panels::page>
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
</x-filament-panels::page>
