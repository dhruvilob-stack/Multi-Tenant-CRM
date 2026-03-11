<div>
    @if($shouldShow)
        <x-filament::modal
            :id="$this->getModalId()"
            heading="Choose Your Subscription"
            :close-button="false"
            :close-by-clicking-away="false"
            :close-by-escaping="false"
            width="7xl"
            teleport="body"
        >
            @if($step === 'plans')
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach($plans as $plan)
                        <x-filament::section :heading="($plan['name'] ?? 'Plan')" :description="sprintf('%s / %s', number_format((float) ($plan['price'] ?? 0), 2), $plan['billing_cycle'] ?? 'month')">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span>Users</span>
                                    <span>{{ ($plan['limits']['users'] ?? null) ? (string) $plan['limits']['users'] : 'Unlimited' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Products</span>
                                    <span>{{ ($plan['limits']['products'] ?? null) ? (string) $plan['limits']['products'] : 'Unlimited' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>AI Email</span>
                                    <span>{{ ($plan['features']['ai_email'] ?? false) ? 'Yes' : 'No' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Inventory</span>
                                    <span>{{ ($plan['features']['inventory'] ?? false) ? 'Yes' : 'No' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Analytics</span>
                                    <span>{{ ($plan['features']['analytics'] ?? false) ? 'Yes' : 'No' }}</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <x-filament::button type="button" wire:click="selectPlan('{{ $plan['key'] }}')">
                                    Start
                                </x-filament::button>
                            </div>
                        </x-filament::section>
                    @endforeach
                </div>
            @else
                <div class="space-y-4">
                    <x-filament::section heading="Selected Plan">
                        @php($plan = collect($plans)->first(fn (array $p) => ($p['key'] ?? '') === $selectedPlanKey))
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-lg font-semibold">{{ $plan['name'] ?? 'Plan' }}</div>
                                <div class="text-sm text-gray-500">{{ number_format((float) ($plan['price'] ?? 0), 2) }} / {{ $plan['billing_cycle'] ?? 'month' }}</div>
                            </div>
                            <x-filament::button color="gray" type="button" wire:click="backToPlans">Change</x-filament::button>
                        </div>
                    </x-filament::section>

                    <x-filament::section heading="Payment Details">
                        {{ $this->form }}
                    </x-filament::section>

                    <div class="flex items-center justify-end gap-2">
                        <x-filament::button type="button" wire:click="processPayment">
                            Pay & Activate
                        </x-filament::button>
                    </div>
                </div>
            @endif
        </x-filament::modal>
    @endif
</div>

@once
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        (() => {
            const componentId = @json($this->getId());
            const getComponent = () => window.Livewire?.find ? window.Livewire.find(componentId) : null;

            const openRazorpay = (detail) => {
                const payload = detail?.payload || detail || {};
                if (!payload?.key) {
                    return;
                }

                if (!window.Razorpay) {
                    console.error('Razorpay checkout script not loaded.');
                    return;
                }

                const component = getComponent();
                const options = {
                    ...payload,
                    handler: (response) => {
                        component?.call(
                            'completeRazorpayPayment',
                            response?.razorpay_payment_id || '',
                            response?.razorpay_order_id || '',
                            response?.razorpay_signature || ''
                        );
                    },
                    modal: {
                        ondismiss: () => component?.call('handleRazorpayCancelled'),
                    },
                };

                const instance = new window.Razorpay(options);
                instance.on('payment.failed', () => component?.call('handleRazorpayCancelled'));
                instance.open();
            };

            const bind = () => {
                window.addEventListener('razorpay-checkout', (event) => openRazorpay(event.detail));
                document.addEventListener('razorpay-checkout', (event) => openRazorpay(event.detail));
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bind);
            } else {
                bind();
            }
        })();
    </script>
@endonce
