<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { size: A4; margin: 16mm 14mm; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 0;
            width: 182mm;
            max-width: 182mm;
        }

        .sheet {
            width: 182mm;
            max-width: 182mm;
            border: 1.5px solid #111827;
            padding: 12px;
            min-height: 255mm;
            box-sizing: border-box;
        }

        .top-header {
            width: 100%;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 10px;
            margin-bottom: 12px;
            table-layout: fixed;
        }

        .top-header td { vertical-align: top; }
        .logo { max-width: 180px; max-height: 64px; }

        .title {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: .6px;
            text-align: right;
        }

        .muted { color: #6b7280; }
        .section { margin-top: 12px; }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #0f766e;
            margin-bottom: 6px;
        }

        .box {
            width: 100%;
            border: 1px solid #9ca3af;
            border-radius: 4px;
            padding: 8px;
            box-sizing: border-box;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #111827;
            table-layout: fixed;
        }

        .table th,
        .table td {
            border: 1px solid #9ca3af;
            padding: 7px 8px;
            vertical-align: top;
        }

        .table th {
            background: #f1f5f9;
            text-align: left;
            font-weight: 700;
        }

        .table .num { text-align: right; }
        .grid-2 { width: 100%; }
        .grid-2 td { width: 50%; vertical-align: top; }
        .mt-6 { margin-top: 6px; }

        .summary {
            width: 45%;
            margin-left: auto;
            margin-top: 10px;
            border-collapse: collapse;
            border: 1px solid #111827;
            table-layout: fixed;
        }

        .summary th,
        .summary td {
            border: 1px solid #9ca3af;
            padding: 7px 8px;
        }

        .summary th { text-align: left; background: #f8fafc; }
        .summary td { text-align: right; }
        .grand { background: #ecfeff; font-weight: 700; }

        .footer {
            margin-top: 12px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>
<body>
@php
    $consumer = $invoice->order?->consumer;
    $consumerName = $invoice->contact_name ?: ($consumer?->name ?: '-');
    $consumerMobile = data_get($invoice->billing_address, 'mobile')
        ?: data_get($invoice->billing_address, 'phone')
        ?: data_get($invoice->shipping_address, 'mobile')
        ?: data_get($invoice->shipping_address, 'phone')
        ?: '-';
@endphp

<div class="sheet">
    <table class="top-header">
        <tr>
            <td style="width: 58%;">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" class="logo" alt="Organization Logo">
                @endif
                <div style="font-size: 16px; font-weight: 700; margin-top: 4px;">{{ $organizationName ?: '-' }}</div>
                <div class="muted">Vendor: {{ $vendorName ?: '-' }}</div>
            </td>
            <td style="width: 42%; text-align: right;">
                <div class="title">INVOICE</div>
                <div><strong>No:</strong> {{ $invoice->invoice_number }}</div>
                <div><strong>Order #:</strong> {{ $invoice->order?->order_number ?? '-' }}</div>
                <div><strong>Date:</strong> {{ optional($invoice->invoice_date)->format('Y-m-d') }}</div>
                <div><strong>Due:</strong> {{ optional($invoice->due_date)->format('Y-m-d') }}</div>
                <div><strong>Status:</strong> {{ strtoupper((string) $invoice->status) }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Consumer Details</div>
        <div class="box">
            <div><strong>Name:</strong> {{ $consumerName }}</div>
            <div class="mt-6"><strong>Mobile:</strong> {{ $consumerMobile }}</div>
            <div class="mt-6"><strong>Address:</strong>
                {{ data_get($invoice->billing_address, 'street', '-') }},
                {{ data_get($invoice->billing_address, 'city', '') }}
                {{ data_get($invoice->billing_address, 'state', '') }}
                {{ data_get($invoice->billing_address, 'postal_code', '') }}
                {{ data_get($invoice->billing_address, 'country', '') }}
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Ordered Items</div>
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 7%;">#</th>
                    <th style="width: 45%;">Description</th>
                    <th style="width: 16%;" class="num">Quantity</th>
                    <th style="width: 16%;" class="num">Unit Cost</th>
                    <th style="width: 16%;" class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->item_name }}</td>
                        <td class="num">{{ number_format((float) $item->qty, 3) }}</td>
                        <td class="num">${{ number_format((float) $item->selling_price, 2) }}</td>
                        <td class="num">${{ number_format((float) $item->total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="muted">No items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Billing & Shipping Details</div>
        <table class="table grid-2">
            <thead>
                <tr>
                    <th>Billing Details</th>
                    <th>Shipping Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div>{{ data_get($invoice->billing_address, 'street', '-') }}</div>
                        <div class="mt-6">{{ data_get($invoice->billing_address, 'city', '') }} {{ data_get($invoice->billing_address, 'state', '') }}</div>
                        <div class="mt-6">{{ data_get($invoice->billing_address, 'postal_code', '') }} {{ data_get($invoice->billing_address, 'country', '') }}</div>
                        <div class="mt-6"><strong>Mobile:</strong> {{ data_get($invoice->billing_address, 'mobile', data_get($invoice->billing_address, 'phone', '-')) }}</div>
                    </td>
                    <td>
                        <div>{{ data_get($invoice->shipping_address, 'street', '-') }}</div>
                        <div class="mt-6">{{ data_get($invoice->shipping_address, 'city', '') }} {{ data_get($invoice->shipping_address, 'state', '') }}</div>
                        <div class="mt-6">{{ data_get($invoice->shipping_address, 'postal_code', '') }} {{ data_get($invoice->shipping_address, 'country', '') }}</div>
                        <div class="mt-6"><strong>Mobile:</strong> {{ data_get($invoice->shipping_address, 'mobile', data_get($invoice->shipping_address, 'phone', '-')) }}</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <table class="summary">
        <tr>
            <th>Payment Method</th>
            <td>{{ strtoupper(str_replace('_', ' ', (string) $invoice->payment_method)) ?: '-' }}</td>
        </tr>
        <tr>
            <th>Payment Ref</th>
            <td>{{ $invoice->order?->payment_reference_number ?: '-' }}</td>
        </tr>
        <tr>
            <th>Pre-tax Total</th>
            <td>${{ number_format((float) $invoice->pre_tax_total, 2) }}</td>
        </tr>
        <tr>
            <th>Tax</th>
            <td>${{ number_format((float) $invoice->tax_amount, 2) }}</td>
        </tr>
        <tr class="grand">
            <th>Grand Total</th>
            <td>${{ number_format((float) $invoice->grand_total, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        System generated invoice • Multi Tenant CRM
    </div>
</div>
</body>
</html>
