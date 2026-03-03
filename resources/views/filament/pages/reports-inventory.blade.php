<x-filament-panels::page>
    <x-filament::section heading="Inventory Overview">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div class="rounded-lg border p-4">Inventory Rows: <strong>{{ $this->stats['records'] ?? 0 }}</strong></div>
            <div class="rounded-lg border p-4">Total Available: <strong>{{ number_format($this->stats['total_available'] ?? 0, 3) }}</strong></div>
            <div class="rounded-lg border p-4">Total Reserved: <strong>{{ number_format($this->stats['total_reserved'] ?? 0, 3) }}</strong></div>
            <div class="rounded-lg border p-4">Unique Products: <strong>{{ $this->stats['unique_products'] ?? 0 }}</strong></div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Low Stock Products">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2">Product</th>
                        <th class="py-2">SKU</th>
                        <th class="py-2">Available</th>
                        <th class="py-2">Reserved</th>
                        <th class="py-2">Location</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->lowStock as $row)
                        <tr class="border-b">
                            <td class="py-2">{{ $row['product'] }}</td>
                            <td class="py-2">{{ $row['sku'] }}</td>
                            <td class="py-2">{{ number_format($row['available'], 3) }}</td>
                            <td class="py-2">{{ number_format($row['reserved'], 3) }}</td>
                            <td class="py-2">{{ $row['location'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-3 text-gray-500">No low stock records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
