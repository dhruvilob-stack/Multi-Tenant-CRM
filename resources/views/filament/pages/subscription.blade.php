<x-filament-panels::page>
    <x-filament::section heading="Current Subscription">
        @if($subscription)
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <div class="text-sm text-gray-500">Plan</div>
                    <div class="text-lg font-semibold">{{ $subscription->plan_name ?: $subscription->plan_key }}</div>
                    <div class="text-sm text-gray-500">Status</div>
                    <x-filament::badge color="success">{{ strtoupper($subscription->status) }}</x-filament::badge>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Billing Cycle</div>
                    <div class="text-lg font-semibold">{{ strtoupper($subscription->billing_cycle ?? 'month') }}</div>
                    <div class="text-sm text-gray-500">Days Remaining</div>
                    <div class="text-lg font-semibold">
                        {{ $subscription->ends_at ? max(0, (int) now()->diffInDays($subscription->ends_at, false)) : '-' }}
                    </div>
                </div>
            </div>
        @else
            <x-filament::callout color="warning" heading="No active subscription">
                Complete your subscription to activate your organization.
            </x-filament::callout>
        @endif
    </x-filament::section>

    <x-filament::section heading="Subscription Invoices" class="mt-6">
        @if(empty($invoices))
            <x-filament::callout color="gray" heading="No invoices yet">
                Your subscription invoices will appear here after payment.
            </x-filament::callout>
        @else
            <div class="space-y-3">
                @foreach($invoices as $invoice)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 p-3">
                        <div>
                            <div class="font-semibold">{{ $invoice->invoice_number }}</div>
                            <div class="text-sm text-gray-500">Issued: {{ optional($invoice->issued_at)->format('Y-m-d') }}</div>
                        </div>
                        <div class="text-sm text-gray-600">
                            @php($displayCurrency = in_array($invoice->payment_method, ['razorpay', 'phonepe'], true) ? 'INR' : $invoice->currency)
                            Total: {{ number_format((float) $invoice->total_amount, 2) }} {{ $displayCurrency }}
                        </div>
                        <div class="flex items-center gap-2">
                            <x-filament::badge color="success">{{ strtoupper($invoice->status) }}</x-filament::badge>
                            @if($invoice->pdf_path)
                                <x-filament::button
                                    tag="a"
                                    href="{{ route('tenant.subscription.invoice', ['tenant' => request()->route('tenant'), 'invoice' => $invoice->id]) }}"
                                    color="gray"
                                    size="sm"
                                >
                                    Download PDF
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
