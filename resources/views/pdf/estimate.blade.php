<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Estimate {{ $estimate->estimate_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #18181b; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: #71717a; }
        .meta { margin-top: 12px; }
        .meta td { padding: 2px 12px 2px 0; border: 0; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.items th, table.items td { border: 1px solid #e4e4e7; padding: 8px; text-align: left; }
        table.items th { background: #f4f4f5; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .right { text-align: right; }
        .totals { margin-top: 16px; width: 240px; margin-left: auto; }
        .totals td { padding: 4px 0; }
        .totals .grand { font-size: 14px; font-weight: bold; border-top: 1px solid #d4d4d8; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Estimate {{ $estimate->estimate_number }}</h1>
    <p class="muted">Status: {{ ucfirst($estimate->status) }}</p>

    <table class="meta">
        <tr>
            <td><strong>Prepared for</strong><br>{{ $estimate->clientAccount?->name }}</td>
            <td>
                <strong>Estimate date</strong><br>{{ optional($estimate->estimate_date)->format('M j, Y') ?: '—' }}<br>
                <strong>Valid until</strong><br>{{ optional($estimate->valid_until)->format('M j, Y') ?: '—' }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Rate</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
        @forelse($estimate->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="right">{{ number_format($item->quantity, 2) }}</td>
                <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No line items</td></tr>
        @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="muted">Subtotal</td>
            <td class="right">{{ number_format($estimate->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="muted">Tax</td>
            <td class="right">{{ number_format($estimate->tax_total, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Grand total</td>
            <td class="right">{{ number_format($estimate->grand_total, 2) }}</td>
        </tr>
    </table>
</body>
</html>
