<div>
    <x-page-shell title="Billing & Invoices" description="Generate client invoices and export PDFs.">
        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$stats['total']" icon="billing" />
            <x-stat-card compact label="Draft" :value="$stats['draft']" icon="plan" />
            <x-stat-card compact label="Sent" :value="$stats['sent']" icon="check" tone="info" />
            <x-stat-card compact label="Paid" :value="$stats['paid']" icon="check" tone="success" />
        </div>

        <x-form-card title="Generate monthly invoice">
            <form wire:submit="generate" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                <x-select wire:model="clientId" label="Client" class="sm:col-span-2 lg:col-span-1">
                    <option value="">Select client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="month" label="Month" type="month" />
                <div class="sm:col-span-2 lg:col-span-1">
                    <x-button type="submit" class="w-full sm:w-auto">Generate invoice</x-button>
                </div>
            </form>
        </x-form-card>

        <x-page-toolbar search="search" searchPlaceholder="Search invoices…">
            <x-slot:tabs>
                <x-segment-control model="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid']" />
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
                        <x-table.td responsive="md" muted>{{ $invoice->invoice_date }}</x-table.td>
                        <x-table.td class="font-semibold">{{ number_format($invoice->grand_total, 2) }}</x-table.td>
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
