<div>
    <x-page-shell
        title="Invoices"
        description="Generate client invoices, record payments, and export accounting data."
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Invoices'],
        ]"
    >
        <x-slot:actions>
            @if ($canExport)
                <x-button variant="secondary" wire:click="exportInvoicesCsv">Export CSV</x-button>
            @endif
            <x-button variant="secondary" wire:click="openGenerate">Generate monthly</x-button>
            <x-button :href="route('billing.invoices.create')">New invoice</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            <div class="stat-grid">
                <x-stat-card compact label="Amount due" :value="'₦'.number_format($stats['amount_due'], 0)" icon="billing" tone="warning" />
                <x-stat-card compact label="Draft" :value="$stats['draft']" icon="plan" />
                <x-stat-card compact label="Sent" :value="$stats['sent']" icon="check" tone="info" />
                <x-stat-card compact label="Paid" :value="$stats['paid'] + $stats['partial']" icon="check" tone="success" />
            </div>

            <x-page-toolbar search="search" searchPlaceholder="Search invoices…">
                <x-slot:tabs>
                    <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'draft' => 'Draft', 'sent' => 'Sent', 'partial' => 'Partial', 'paid' => 'Paid', 'void' => 'Void', 'overdue' => 'Overdue']" />
                </x-slot:tabs>
                <x-slot:controls>
                    <button type="button" wire:click="toggleFilters" class="table-action">
                        {{ $showFilters ? 'Hide filters' : 'Filters' }}
                    </button>
                    @if ($search !== '' || $statusFilter !== 'all' || $hasAdvancedFilters)
                        <button type="button" wire:click="clearFilters" class="table-action">Clear</button>
                    @endif
                    @if ($canExport && $exports->isNotEmpty())
                        <span class="text-xs text-zinc-500">{{ $exports->count() }} recent export{{ $exports->count() === 1 ? '' : 's' }}</span>
                    @endif
                </x-slot:controls>
            </x-page-toolbar>

            @if ($showFilters)
                <div class="billing-filter-panel">
                    <x-select wire:model.live="filterClientId" label="Client">
                        <option value="">All clients</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model.live="dateFrom" type="date" label="From date" />
                    <x-input wire:model.live="dateTo" type="date" label="To date" />
                </div>
            @endif

            @if ($canExport && $exports->isNotEmpty())
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($exports->take(3) as $export)
                        <button type="button" wire:click="downloadExport({{ $export->id }})" class="status-chip status-chip-neutral" wire:key="export-chip-{{ $export->id }}">
                            <span class="tabular-nums">{{ $export->created_at?->format('M j, H:i') }}</span> · Download
                        </button>
                    @endforeach
                </div>
            @endif

            <x-data-table>
                <x-table.head>
                    <tr>
                        <x-table.th>Invoice #</x-table.th>
                        <x-table.th>Client</x-table.th>
                        <x-table.th responsive="md">Date</x-table.th>
                        <x-table.th>Total</x-table.th>
                        <x-table.th>Balance</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th align="right">Actions</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($invoices as $invoice)
                        @php
                            $balance = max(0, (float) $invoice->grand_total - (float) ($invoice->amount_paid ?? 0));
                        @endphp
                        <tr class="table-row-hover" wire:key="inv-{{ $invoice->id }}">
                            <x-table.td mono>
                                <a href="{{ route('billing.invoices.show', $invoice) }}" wire:navigate class="font-medium text-zinc-900 transition hover:text-accent-700 dark:text-zinc-100 dark:hover:text-accent-300">{{ $invoice->invoice_number }}</a>
                            </x-table.td>
                            <x-table.td>{{ $invoice->clientAccount?->name }}</x-table.td>
                            <x-table.td responsive="md" muted class="tabular-nums">{{ $invoice->invoice_date?->format('M j, Y') }}</x-table.td>
                            <x-table.td class="font-semibold tabular-nums">₦{{ number_format($invoice->grand_total, 2) }}</x-table.td>
                            <x-table.td muted class="tabular-nums">₦{{ number_format($balance, 2) }}</x-table.td>
                            <x-table.td><x-badge :status="$invoice->status" /></x-table.td>
                            <x-table.td align="right">
                                <x-row-menu>
                                    <x-row-menu-item :href="route('billing.invoices.show', $invoice)">Open</x-row-menu-item>
                                    @if ($invoice->status === 'draft')
                                        <x-row-menu-item :href="route('billing.invoices.edit', $invoice)">Edit</x-row-menu-item>
                                    @endif
                                    @if ($invoice->status !== 'paid' && $invoice->status !== 'void')
                                        <x-row-menu-item wire:click="openPayment({{ $invoice->id }})">Record payment</x-row-menu-item>
                                    @endif
                                    <x-row-menu-item wire:click="exportPdf({{ $invoice->id }})">Download PDF</x-row-menu-item>
                                    @if (in_array($invoice->status, ['draft', 'sent', 'partial', 'overdue'], true))
                                        <x-row-menu-item wire:click="openSend({{ $invoice->id }})">Email</x-row-menu-item>
                                    @endif
                                    @if ($invoice->status === 'draft')
                                        <x-row-menu-item wire:click="markSent({{ $invoice->id }})">Mark sent</x-row-menu-item>
                                    @endif
                                </x-row-menu>
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="7">
                            <x-empty-state
                                compact
                                :title="($search !== '' || $statusFilter !== 'all' || $hasAdvancedFilters) ? 'No matching invoices' : 'No invoices'"
                                :description="($search !== '' || $statusFilter !== 'all' || $hasAdvancedFilters) ? 'Try clearing filters or widening the date range.' : 'Create a draft invoice or generate one from client coverage.'"
                            >
                                <x-slot:actions>
                                    @if ($search !== '' || $statusFilter !== 'all' || $hasAdvancedFilters)
                                        <x-button size="sm" variant="secondary" wire:click="clearFilters">Clear filters</x-button>
                                    @else
                                        <x-button size="sm" :href="route('billing.invoices.create')">New invoice</x-button>
                                        <x-button size="sm" variant="secondary" wire:click="openGenerate">Generate monthly</x-button>
                                    @endif
                                </x-slot:actions>
                            </x-empty-state>
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-pagination :paginator="$invoices" />
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showGenerateForm)
        <x-drawer title="Generate invoice" description="Create a monthly invoice from client coverage." width="md" close-method="closeGenerate">
            <x-drawer-form wire:submit="generate" submit-label="Generate invoice" close-method="closeGenerate" target="generate">
                <x-form-section title="Invoice">
                    <x-select wire:model="clientId" label="Client *" class="sm:col-span-2">
                        <option value="">Select client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="month" label="Month *" type="month" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($sendingInvoice)
        <x-drawer
            title="Email invoice"
            :description="'Send '.$sendingInvoice->invoice_number.' with PDF attachment to '.$sendingInvoice->clientAccount?->name"
            width="md"
            close-method="closeSend"
        >
            <x-drawer-form wire:submit="sendInvoice" submit-label="Send email" close-method="closeSend" target="sendInvoice">
                <x-form-section title="Recipients" description="Comma-separated emails. Defaults to client and contact emails.">
                    <x-textarea wire:model="sendEmails" label="To *" rows="3" class="sm:col-span-2" />
                    <x-textarea wire:model="sendMessage" label="Optional message" rows="3" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($payingInvoice)
        <x-drawer
            title="Record payment"
            :description="'Invoice '.$payingInvoice->invoice_number.' · Remaining ₦'.number_format(max(0, $payingInvoice->grand_total - ($payingInvoice->amount_paid ?? 0)), 2)"
            width="md"
            close-method="closePayment"
        >
            <x-drawer-form wire:submit="recordPayment" submit-label="Save payment" close-method="closePayment" target="recordPayment">
                <x-form-section title="Payment">
                    <x-input wire:model="paymentForm.amount" label="Amount *" type="number" step="0.01" min="0.01" />
                    <x-select wire:model="paymentForm.payment_method" label="Method *">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="mobile_money">Mobile money</option>
                        <option value="card">Card</option>
                        <option value="cheque">Cheque</option>
                    </x-select>
                    <x-textarea wire:model="paymentForm.notes" label="Notes" rows="2" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
