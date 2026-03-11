<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Subscription Invoice</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #111827;">

    <p>Hello {{ e($organization?->name ?: 'there') }},</p>

    <p>Your subscription payment has been received. The invoice is attached as a PDF.</p>

    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 640px;">
        <tr>
            <td style="border: 1px solid #e5e7eb;">Invoice #</td>
            <td style="border: 1px solid #e5e7eb;">{{ e($invoice->invoice_number) }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #e5e7eb;">Plan</td>
            <td style="border: 1px solid #e5e7eb;">{{ e($subscription?->plan_name ?: $subscription?->plan_key ?: '-') }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #e5e7eb;">Billing Cycle</td>
            <td style="border: 1px solid #e5e7eb;">{{ strtoupper((string) ($subscription?->billing_cycle ?: 'month')) }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #e5e7eb;">Total</td>
            <td style="border: 1px solid #e5e7eb;">{{ number_format((float) $invoice->total_amount, 2) }} {{ $invoice->currency }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #e5e7eb;">Payment Method</td>
            <td style="border: 1px solid #e5e7eb;">{{ strtoupper((string) $invoice->payment_method) }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #e5e7eb;">Issued</td>
            <td style="border: 1px solid #e5e7eb;">{{ optional($invoice->issued_at)->format('Y-m-d') }}</td>
        </tr>
    </table>

    <p style="margin-top: 16px;">If you need a copy later, you can download it from your Subscription page after logging in.</p>

    <p>Thanks,<br>Multi Tenant CRM</p>
</body>
</html>
