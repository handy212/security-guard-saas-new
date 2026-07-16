@props(['estimate'])

<article {{ $attributes->merge(['class' => 'billing-document']) }}>
    <header class="billing-document-header">
        <div>
            <p class="billing-document-eyebrow">Estimate</p>
            <h2 class="billing-document-title">{{ $estimate->estimate_number }}</h2>
            <div class="mt-2"><x-badge :status="$estimate->status" /></div>
        </div>
        <div class="billing-document-meta text-right">
            <div class="text-xs uppercase tracking-wide text-zinc-500">Total</div>
            <div class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">₦{{ number_format((float) $estimate->grand_total, 2) }}</div>
        </div>
    </header>

    <div class="billing-document-parties">
        <div>
            <div class="billing-document-label">Prepared for</div>
            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $estimate->clientAccount?->name ?? '—' }}</div>
        </div>
        <div class="sm:text-right">
            <div class="billing-document-label">Estimate date</div>
            <div>{{ $estimate->estimate_date?->format('M j, Y') ?? '—' }}</div>
            <div class="billing-document-label mt-2">Valid until</div>
            <div>{{ $estimate->valid_until?->format('M j, Y') ?? '—' }}</div>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="billing-document-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($estimate->items as $item)
                    <tr wire:key="est-doc-item-{{ $item->id }}">
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="text-right">₦{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="text-right font-medium">₦{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-zinc-500">No line items</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <dl class="billing-document-totals">
        <div class="billing-document-total-row">
            <dt>Subtotal</dt>
            <dd>₦{{ number_format((float) $estimate->subtotal, 2) }}</dd>
        </div>
        <div class="billing-document-total-row">
            <dt>Tax</dt>
            <dd>₦{{ number_format((float) $estimate->tax_total, 2) }}</dd>
        </div>
        <div class="billing-document-total-row billing-document-total-grand">
            <dt>Total</dt>
            <dd>₦{{ number_format((float) $estimate->grand_total, 2) }}</dd>
        </div>
    </dl>
</article>
