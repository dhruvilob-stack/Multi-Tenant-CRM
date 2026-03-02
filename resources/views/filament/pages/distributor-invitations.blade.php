<x-filament-panels::page>
    @php($records = \App\Models\Invitation::query()->whereHas('inviter', fn($q) => $q->where('role', 'distributor')->whereKey(auth()->id()))->latest()->limit(20)->get())
    <x-filament::section :heading="__('filament.admin.pages.distributor_invitations.heading')">
        <div class="space-y-2">
            @forelse ($records as $inv)
                <div class="text-sm">{{ $inv->invitee_email }} ({{ $inv->role }}) - {{ __('filament.admin.pages.invitations.expires') }} {{ $inv->expires_at }}</div>
            @empty
                <div class="text-sm text-gray-600">{{ __('filament.admin.pages.invitations.empty') }}</div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-panels::page>
