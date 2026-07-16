<div>
    <x-page-shell
        title="Payments"
        description="Cash collected against invoices — record payments from any open invoice."
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Payments'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('billing.invoices')">Open invoices</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            <div class="stat-grid">
                <x-stat-card compact label="Collected MTD" :value="'₦'.number_format($stats['mtd'], 0)" icon="check" tone="success" />
                <x-stat-card compact label="Payments MTD" :value="$stats['mtd_count']" icon="billing" tone="info" />
                <x-stat-card compact label="All payments" :value="$stats['total']" icon="plan" />
                <x-stat-card compact label="Open invoices" :value="$stats['open_invoices']" icon="pause" tone="warning" :href="route('billing.invoices')" />
            </div>

            <x-page-toolbar search="search" searchPlaceholder="Search payments…">
                <x-slot:tabs>
                    <x-segment-control field="methodFilter" :active="$methodFilter" :options="['all' => 'All methods', 'cash' => 'Cash', 'bank_transfer' => 'Bank', 'mobile_money' => 'Mobile', 'card' => 'Card', 'cheque' => 'Cheque']" />
                </x-slot:tabs>
                <x-slot:controls>
                    <button type="button" wire:click="toggleFilters" class="table-action">
                        {{ $showFilters ? 'Hide filters' : 'Filters' }}
                    </button>
                    @if ($hasActiveFilters)
                        <button type="button" wire:click="clearFilters" class="table-action">Clear</button>
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

            <x-data-table>
                <x-table.head>
                    <tr>
                        <x-table.th>Date</x-table.th>
                        <x-table.th>Invoice</x-table.th>
                        <x-table.th>Client</x-table.th>
                        <x-table.th responsive="md">Method</x-table.th>
                        <x-table.th>Amount</x-table.th>
                        <x-table.th responsive="lg">Notes</x-table.th>
                        <x-table.th align="right">Actions</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr class="table-row-hover" wire:key="pay-{{ $payment->id }}">
                            <x-table.td muted>{{ $payment->paid_at?->format('M j, Y') ?? '—' }}</x-table.td>
                            <x-table.td class="font-mono font-medium">
                                @if ($payment->invoice)
                                    <a href="{{ route('billing.invoices.show', $payment->invoice) }}" wire:navigate class="text-accent-700 hover:underline">{{ $payment->invoice->invoice_number }}</a>
                                @else
                                    —
                                @endif
                            </x-table.td>
                            <x-table.td>{{ $payment->invoice?->clientAccount?->name ?? '—' }}</x-table.td>
                            <x-table.td responsive="md" muted>{{ str_replace('_', ' ', $payment->payment_method) }}</x-table.td>
                            <x-table.td class="font-semibold">₦{{ number_format($payment->amount, 2) }}</x-table.td>
                            <x-table.td responsive="lg" muted>{{ $payment->notes ?: '—' }}</x-table.td>
                            <x-table.td align="right">
                                @if ($payment->invoice)
                                    <x-row-menu>
                                        <x-row-menu-item :href="route('billing.invoices.show', $payment->invoice)">Open invoice</x-row-menu-item>
                                    </x-row-menu>
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="7">
                            <x-empty-state
                                compact
                                :title="$hasActiveFilters ? 'No matching payments' : 'No payments yet'"
                                :description="$hasActiveFilters ? 'Try clearing filters or widening the date range.' : 'Record a payment from an open invoice to see it here.'"
                            >
                                <x-slot:actions>
                                    @if ($hasActiveFilters)
                                        <x-button size="sm" variant="secondary" wire:click="clearFilters">Clear filters</x-button>
                                    @else
                                        <x-button size="sm" :href="route('billing.invoices')">Open invoices</x-button>
                                    @endif
                                </x-slot:actions>
                            </x-empty-state>
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-pagination :paginator="$payments" />
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
