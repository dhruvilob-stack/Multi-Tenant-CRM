<x-filament-panels::page>
    <x-filament::section :heading="__('filament.super_admin.pages.audit_logs.heading')">
        <p class="text-sm text-gray-600">{{ __('filament.super_admin.pages.audit_logs.description') }}</p>
    </x-filament::section>

    <x-filament::section heading="Live Audit Feed">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div>
                <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Tenant</label>
                <input wire:model.live.debounce.300ms="tenantFilter" class="fi-input mt-1 block w-full" placeholder="swiftbasket" />
            </div>
            <div>
                <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Event</label>
                <input wire:model.live.debounce.300ms="eventFilter" class="fi-input mt-1 block w-full" placeholder="created / updated / deleted" />
            </div>
            <div class="flex items-end">
                <div class="text-xs text-gray-500">
                    Auto-refresh every 5s
                </div>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto" wire:poll.5s>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left">
                        <th class="py-2">At</th>
                        <th class="py-2">Tenant</th>
                        <th class="py-2">Event</th>
                        <th class="py-2">Actor</th>
                        <th class="py-2">Role</th>
                        <th class="py-2">Target</th>
                        <th class="py-2">Route</th>
                        <th class="py-2">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @php($rows = $this->getLogs())
                    @forelse($rows as $row)
                        <tr wire:key="pal-{{ $row['id'] }}" class="border-b align-top">
                            <td class="py-2 whitespace-nowrap">{{ $row['at'] }}</td>
                            <td class="py-2 whitespace-nowrap">{{ $row['tenant'] }}</td>
                            <td class="py-2 whitespace-nowrap">{{ $row['event'] }}</td>
                            <td class="py-2 whitespace-nowrap">{{ $row['actor'] }}</td>
                            <td class="py-2 whitespace-nowrap">{{ $row['role'] }}</td>
                            <td class="py-2 whitespace-nowrap">{{ $row['auditable'] }}</td>
                            <td class="py-2 whitespace-nowrap">{{ $row['method'] }} {{ $row['route'] }}</td>
                            <td class="py-2 whitespace-nowrap">{{ $row['ip'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-3 text-gray-500">No audit logs yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
