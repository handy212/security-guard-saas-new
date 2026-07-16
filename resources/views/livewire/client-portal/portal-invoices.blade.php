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

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-950">
                <tr>
                    <th class="px-4 py-3">Invoice</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="hidden px-4 py-3 sm:table-cell">Due</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="hidden px-4 py-3 md:table-cell">Balance</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($invoices as $invoice)
                    @php
                        $balance = max(0, (float) $invoice->grand_total - (float) ($invoice->amount_paid ?? 0));
                    @endphp
                    <tr wire:key="portal-inv-{{ $invoice->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                        <td class="px-4 py-3 font-mono font-medium">
                            <a href="{{ route('client-portal.invoices.show', $invoice) }}" wire:navigate class="text-accent-700 hover:underline">{{ $invoice->invoice_number }}</a>
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $invoice->invoice_date?->format('M j, Y') }}</td>
                        <td class="hidden px-4 py-3 text-zinc-500 sm:table-cell">{{ $invoice->due_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 font-semibold">₦{{ number_format($invoice->grand_total, 2) }}</td>
                        <td class="hidden px-4 py-3 text-zinc-600 md:table-cell">₦{{ number_format($balance, 2) }}</td>
                        <td class="px-4 py-3"><x-badge :status="$invoice->status" /></td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex gap-2">
                                <a href="{{ route('client-portal.invoices.show', $invoice) }}" wire:navigate class="text-xs font-medium text-accent-600 hover:underline">View</a>
                                <button type="button" wire:click="downloadPdf({{ $invoice->id }})" class="text-xs font-medium text-accent-600 hover:underline">PDF</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10">
                            <x-empty-state compact title="No invoices yet" description="Invoices appear here after your security provider sends them." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <x-pagination :paginator="$invoices" />
    </div>
</div>
