<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 24px; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; margin: 0; }
        .title { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
        .subtle { color: #6b7280; font-size: 12px; }
        .card { border: 1px solid #e5e7eb; padding: 16px; margin-bottom: 16px; page-break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 8px 6px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; }
        .totals td { font-weight: 600; }
        .badge { display: inline-block; padding: 4px 10px; background: #ecfdf3; color: #047857; font-size: 12px; }
    </style>
</head>
<body>
    @php($displayCurrency = in_array($invoice->payment_method, ['razorpay', 'phonepe'], true) ? 'INR' : $invoice->currency)

    <table style="margin-bottom: 24px;">
        <tr>
            <td>
                <div class="title">Subscription Invoice</div>
                <div class="subtle">Invoice #: {{ $invoice->invoice_number }}</div>
                <div class="subtle">Issued: {{ optional($invoice->issued_at)->format('Y-m-d') }}</div>
            </td>
            <td style="text-align: right;">
                <span class="badge">{{ strtoupper((string) $invoice->status) }}</span>
            </td>
        </tr>
    </table>

    <div class="card">
        <div class="subtle">Organization</div>
        <div style="font-size: 16px; font-weight: 600; margin-top: 4px;">{{ $invoice->organization?->name }}</div>
        <div class="subtle">{{ $invoice->organization?->email }}</div>
    </div>

    <div class="card">
        <div class="subtle">Plan</div>
        <div style="font-size: 16px; font-weight: 600; margin-top: 4px;">{{ $invoice->subscription?->plan_name ?: $invoice->subscription?->plan_key }}</div>
        <div class="subtle">Billing cycle: {{ strtoupper((string) ($invoice->subscription?->billing_cycle ?: 'month')) }}</div>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Plan price</td>
                    <td style="text-align: right;">{{ number_format((float) $invoice->plan_price, 2) }} {{ $displayCurrency }}</td>
                </tr>
                <tr>
                    <td>GST / Tax</td>
                    <td style="text-align: right;">{{ number_format((float) $invoice->tax_amount, 2) }} {{ $displayCurrency }}</td>
                </tr>
                <tr>
                    <td>Platform fee</td>
                    <td style="text-align: right;">{{ number_format((float) $invoice->platform_fee, 2) }} {{ $displayCurrency }}</td>
                </tr>
                <tr class="totals">
                    <td>Total</td>
                    <td style="text-align: right;">{{ number_format((float) $invoice->total_amount, 2) }} {{ $displayCurrency }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="subtle">Payment</div>
        <div style="margin-top: 6px; font-size: 13px;">
            Method: {{ strtoupper((string) $invoice->payment_method) }}<br>
            Reference: {{ $invoice->payment_reference ?: '-' }}
        </div>
    </div>

    <div class="subtle">System generated invoice • Multi Tenant CRM</div>
</body>
</html>
