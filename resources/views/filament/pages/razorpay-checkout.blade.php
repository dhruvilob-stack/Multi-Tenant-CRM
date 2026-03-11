<x-filament-panels::layout.simple>
    <x-filament::section heading="Razorpay Checkout">
        <div class="text-sm text-gray-600">
            We are opening the Razorpay payment window. If it does not open, click Retry.
        </div>

        <div class="mt-4">
            <x-filament::button type="button" id="razorpay-retry">
                Retry
            </x-filament::button>
        </div>

        <form id="razorpay-callback-form" method="POST" action="{{ route('tenant.subscription.razorpay.callback', ['tenant' => request()->route('tenant')]) }}">
            @csrf
            <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
            <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
            <input type="hidden" name="razorpay_signature" id="razorpay_signature">
        </form>
    </x-filament::section>
</x-filament-panels::layout.simple>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    (() => {
        const options = {
            key: @json($razorpayKey),
            amount: @json($amount),
            currency: @json($currency),
            name: @json(config('app.name')),
            description: 'Subscription Payment',
            order_id: @json($orderId),
            prefill: @json($prefill ?? []),
            notes: @json($notes ?? []),
            handler: (response) => {
                document.getElementById('razorpay_payment_id').value = response?.razorpay_payment_id || '';
                document.getElementById('razorpay_order_id').value = response?.razorpay_order_id || '';
                document.getElementById('razorpay_signature').value = response?.razorpay_signature || '';
                document.getElementById('razorpay-callback-form').submit();
            },
            modal: {
                ondismiss: () => {
                    window.location.href = @json(route('filament.admin.pages.subscription'));
                },
            },
        };

        const openCheckout = () => {
            if (!window.Razorpay) {
                return;
            }
            const instance = new window.Razorpay(options);
            instance.open();
        };

        document.getElementById('razorpay-retry')?.addEventListener('click', openCheckout);
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', openCheckout);
        } else {
            openCheckout();
        }
    })();
</script>
