<x-filament-panels::page>
    <x-filament::section heading="Stock Movements">
        <p class="text-sm text-gray-600">Latest inventory create/update/delete events captured through audit logs.</p>
    </x-filament::section>

    <x-filament::section>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2">Inventory ID</th>
                        <th class="py-2">Event</th>
                        <th class="py-2">Performed By</th>
                        <th class="py-2">Role</th>
                        <th class="py-2">IP</th>
                        <th class="py-2">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->movements as $row)
                        <tr class="border-b">
                            <td class="py-2">{{ $row['inventory_id'] }}</td>
                            <td class="py-2">{{ ucfirst($row['event']) }}</td>
                            <td class="py-2">{{ $row['performed_by'] }}</td>
                            <td class="py-2">{{ $row['performed_role'] }}</td>
                            <td class="py-2">{{ $row['ip'] }}</td>
                            <td class="py-2">{{ $row['at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-3 text-gray-500">No stock movement logs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
