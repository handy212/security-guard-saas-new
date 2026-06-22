<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <h1>Invoice {{ $invoice->invoice_number }}</h1>
    <p>Client: {{ $invoice->clientAccount?->name }}</p>
    <p>Date: {{ optional($invoice->invoice_date)->format('Y-m-d') }} | Due: {{ optional($invoice->due_date)->format('Y-m-d') }}</p>
    <table>
        <thead><tr><th>Description</th><th>Qty</th><th>Rate</th><th>Total</th></tr></thead>
        <tbody>
        @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p><strong>Grand Total: {{ number_format($invoice->grand_total, 2) }}</strong></p>
</body>
</html>
