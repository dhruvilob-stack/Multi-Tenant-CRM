<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4; margin: 16mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; }
        .wrap { border: 1px solid #111827; padding: 12px; }
        .title { font-size: 22px; font-weight: 700; margin-bottom: 6px; }
        .muted { color: #64748b; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #94a3b8; padding: 8px; text-align: left; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="title">{{ $organization->name }} - Sales Dashboard Report</div>
    <div class="muted">Generated at {{ $generatedAt }}</div>

    <table>
        <thead><tr><th>Metric</th><th>Value</th></tr></thead>
        <tbody>
            <tr><td>Total Orders</td><td>{{ number_format($stats['orders_total']) }}</td></tr>
            <tr><td>Delivered Orders</td><td>{{ number_format($stats['orders_delivered']) }}</td></tr>
            <tr><td>Total Invoice Amount</td><td>${{ number_format($stats['invoices_total'], 2) }}</td></tr>
            <tr><td>Total Paid Amount</td><td>${{ number_format($stats['invoices_paid'], 2) }}</td></tr>
        </tbody>
    </table>
</div>
</body>
</html>
