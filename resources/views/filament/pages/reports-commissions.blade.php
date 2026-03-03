<x-filament-panels::page>
    <x-filament::section heading="Commission Summary">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div class="rounded-lg border p-4">Entries: <strong>{{ $this->stats['entries'] ?? 0 }}</strong></div>
            <div class="rounded-lg border p-4">Accrued: <strong>${{ number_format($this->stats['accrued'] ?? 0, 2) }}</strong></div>
            <div class="rounded-lg border p-4">Paid Out: <strong>${{ number_format($this->stats['paid_out'] ?? 0, 2) }}</strong></div>
            <div class="rounded-lg border p-4">Payable: <strong>${{ number_format($this->stats['payable'] ?? 0, 2) }}</strong></div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Top Commission Earners">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2">User</th>
                        <th class="py-2">Total Commission</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->topEarners as $row)
                        <tr class="border-b">
                            <td class="py-2">{{ $row['user'] }}</td>
                            <td class="py-2">${{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="py-3 text-gray-500">No commission data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
