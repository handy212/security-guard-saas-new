<div>
    <x-page-shell title="Invoices" description="Generate client invoices, record payments, and export accounting data.">
        <x-flash-status type="success" />

        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$stats['total']" icon="billing" />
            <x-stat-card compact label="Draft" :value="$stats['draft']" icon="plan" />
            <x-stat-card compact label="Sent" :value="$stats['sent']" icon="check" tone="info" />
            <x-stat-card compact label="Paid" :value="$stats['paid'] + $stats['partial']" icon="check" tone="success" />
        </div>

        <div class="page-grid-2">
            <x-form-card title="Generate monthly invoice">
                <form wire:submit="generate" class="space-y-3">
                    <x-select wire:model="clientId" label="Client">
                        <option value="">Select client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="month" label="Month" type="month" />
                    <x-button type="submit" size="sm">Generate invoice</x-button>
                </form>
            </x-form-card>

            @if ($canExport)
                <x-form-card title="Accounting export" description="CSV export for QuickBooks, Xero, or spreadsheets.">
                    <x-button wire:click="exportInvoicesCsv" size="sm">Export invoices CSV</x-button>
                    @if ($exports->isNotEmpty())
                        <div class="mt-4 space-y-2">
                            @foreach ($exports as $export)
                                <div class="flex items-center justify-between gap-2 text-sm" wire:key="export-{{ $export->id }}">
                                    <span class="truncate text-zinc-600">{{ $export->created_at?->format('M j, H:i') }} · {{ strtoupper($export->export_type ?? 'csv') }}</span>
                                    <button type="button" wire:click="downloadExport({{ $export->id }})" class="shrink-0 text-xs font-medium text-accent-600 hover:underline">Download</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-form-card>
            @endif
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search invoices…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'draft' => 'Draft', 'sent' => 'Sent', 'partial' => 'Partial', 'paid' => 'Paid']" />
            </x-slot:tabs>
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Invoice #</x-table.th>
                    <x-table.th>Client</x-table.th>
                    <x-table.th responsive="md">Date</x-table.th>
                    <x-table.th>Total</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right">Actions</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr class="table-row-hover" wire:key="inv-{{ $invoice->id }}">
                        <x-table.td class="font-mono font-medium">{{ $invoice->invoice_number }}</x-table.td>
                        <x-table.td>{{ $invoice->clientAccount?->name }}</x-table.td>
                        <x-table.td responsive="md" muted>{{ $invoice->invoice_date?->format('M j, Y') }}</x-table.td>
                        <x-table.td class="font-semibold">₦{{ number_format($invoice->grand_total, 2) }}</x-table.td>
                        <x-table.td><x-badge :status="$invoice->status" /></x-table.td>
                        <x-table.td align="right">
                            <div class="table-inline-actions">
                                @if($invoice->status !== 'paid')
                                    <button type="button" wire:click="recordPayment({{ $invoice->id }})" class="table-action">Record payment</button>
                                @endif
                                <button type="button" wire:click="exportPdf({{ $invoice->id }})" class="table-action">PDF</button>
                                @if ($invoice->status === 'draft')
                                    <button type="button" wire:click="markSent({{ $invoice->id }})" class="table-action">Mark sent</button>
                                @endif
                            </div>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6">
                        <x-empty-state compact title="No invoices" description="Generate a monthly invoice for a client to get started." />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$invoices" />
    </x-page-shell>
</div>
