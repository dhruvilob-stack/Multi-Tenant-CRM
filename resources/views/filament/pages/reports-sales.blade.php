<x-filament-panels::page>
    <x-filament::section heading="Sales Overview">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="rounded-lg border p-4">Total Orders: <strong>{{ $this->stats['orders_total'] ?? 0 }}</strong></div>
            <div class="rounded-lg border p-4">Delivered Orders: <strong>{{ $this->stats['orders_delivered'] ?? 0 }}</strong></div>
            <div class="rounded-lg border p-4">Total Quotations: <strong>{{ $this->stats['quotations_total'] ?? 0 }}</strong></div>
            <div class="rounded-lg border p-4">Converted Quotations: <strong>{{ $this->stats['quotations_converted'] ?? 0 }}</strong></div>
            <div class="rounded-lg border p-4">Invoice Amount: <strong>${{ number_format($this->stats['invoices_total_amount'] ?? 0, 2) }}</strong></div>
            <div class="rounded-lg border p-4">Paid Amount: <strong>${{ number_format($this->stats['invoices_paid_amount'] ?? 0, 2) }}</strong></div>
        </div>
    </x-filament::section>

    <x-filament::section heading="Recent Orders">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2">Order</th>
                        <th class="py-2">Vendor</th>
                        <th class="py-2">Consumer</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Total</th>
                        <th class="py-2">Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->recentOrders as $row)
                        <tr class="border-b">
                            <td class="py-2">{{ $row['order_number'] }}</td>
                            <td class="py-2">{{ $row['vendor'] }}</td>
                            <td class="py-2">{{ $row['consumer'] }}</td>
                            <td class="py-2">{{ ucfirst($row['status']) }}</td>
                            <td class="py-2">${{ number_format($row['total'], 2) }}</td>
                            <td class="py-2">{{ $row['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-3 text-gray-500">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
