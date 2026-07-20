<div>
    <x-portal-page-header
        title="Invoices"
        description="View and download invoices issued to your account."
    />

    <x-flash-status type="success" />

    <div class="stat-grid mb-4">
        <x-stat-card compact label="Open" :value="$stats['open']" icon="billing" :tone="$stats['open'] ? 'warning' : 'default'" />
        <x-stat-card compact label="Paid" :value="$stats['paid']" icon="check" tone="success" />
        <x-stat-card compact label="Balance due" :value="'₦'.number_format($stats['balance'], 0)" icon="pause" tone="info" />
    </div>

    <x-page-toolbar>
        <x-slot:tabs>
            <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'sent' => 'Sent', 'partial' => 'Partial', 'paid' => 'Paid', 'overdue' => 'Overdue']" />
        </x-slot:tabs>
    </x-page-toolbar>

    <x-data-table>
        <x-table.head>
            <tr>
                <x-table.th>Invoice</x-table.th>
                <x-table.th>Date</x-table.th>
                <x-table.th responsive="md">Due</x-table.th>
                <x-table.th>Total</x-table.th>
                <x-table.th responsive="lg">Balance</x-table.th>
                <x-table.th>Status</x-table.th>
                <x-table.th align="right">Actions</x-table.th>
            </tr>
        </x-table.head>
        <tbody>
            @forelse ($invoices as $invoice)
                @php
                    $balance = max(0, (float) $invoice->grand_total - (float) ($invoice->amount_paid ?? 0));
                @endphp
                <tr class="table-row-hover" wire:key="portal-inv-{{ $invoice->id }}">
                    <x-table.td>
                        <a
                            href="{{ route('client-portal.invoices.show', $invoice) }}"
                            wire:navigate
                            class="font-mono font-medium text-zinc-900 transition hover:text-accent-700 dark:text-zinc-100 dark:hover:text-accent-300"
                        >{{ $invoice->invoice_number }}</a>
                    </x-table.td>
                    <x-table.td muted class="tabular-nums">{{ $invoice->invoice_date?->format('M j, Y') }}</x-table.td>
                    <x-table.td responsive="md" muted class="tabular-nums">{{ $invoice->due_date?->format('M j, Y') ?? '—' }}</x-table.td>
                    <x-table.td class="font-semibold tabular-nums">₦{{ number_format($invoice->grand_total, 2) }}</x-table.td>
                    <x-table.td responsive="lg" muted class="tabular-nums">₦{{ number_format($balance, 2) }}</x-table.td>
                    <x-table.td><x-badge :status="$invoice->status" /></x-table.td>
                    <x-table.td align="right">
                        <div class="table-inline-actions justify-end">
                            <a href="{{ route('client-portal.invoices.show', $invoice) }}" wire:navigate class="table-action">View</a>
                            <button type="button" wire:click="downloadPdf({{ $invoice->id }})" class="table-action">PDF</button>
                        </div>
                    </x-table.td>
                </tr>
            @empty
                <x-table.empty colspan="7">
                    <x-empty-state compact title="No invoices yet" description="Invoices appear here after your security provider sends them." />
                </x-table.empty>
            @endforelse
        </tbody>
    </x-data-table>

    <x-pagination :paginator="$invoices" />
</div>
