<div>
    <x-page-shell
        title="Estimates"
        description="Create estimates and convert to invoices on acceptance."
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Estimates'],
        ]"
    >
        <x-slot:actions>
            <x-button :href="route('billing.estimates.create')">New estimate</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            <div class="stat-grid">
                <x-stat-card compact label="Pipeline" :value="'₦'.number_format($stats['pipeline'], 0)" icon="billing" tone="info" />
                <x-stat-card compact label="Open" :value="$stats['open']" icon="pause" />
                <x-stat-card compact label="Accepted" :value="$stats['accepted']" icon="check" tone="success" />
                <x-stat-card compact label="Converted" :value="$stats['converted']" icon="plan" />
            </div>

            <x-page-toolbar search="search" searchPlaceholder="Search estimates…">
                <x-slot:tabs>
                    <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted']" />
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
                        <x-table.th>Estimate #</x-table.th>
                        <x-table.th>Client</x-table.th>
                        <x-table.th responsive="md">Valid until</x-table.th>
                        <x-table.th>Total</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th align="right">Actions</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($estimates as $estimate)
                        <tr class="table-row-hover" wire:key="estimate-{{ $estimate->id }}">
                            <x-table.td mono>
                                <a href="{{ route('billing.estimates.show', $estimate) }}" wire:navigate class="font-medium text-zinc-900 transition hover:text-accent-700 dark:text-zinc-100 dark:hover:text-accent-300">{{ $estimate->estimate_number }}</a>
                            </x-table.td>
                            <x-table.td>{{ $estimate->clientAccount?->name }}</x-table.td>
                            <x-table.td responsive="md" muted class="tabular-nums">{{ $estimate->valid_until?->format('M j, Y') ?? '—' }}</x-table.td>
                            <x-table.td class="font-semibold tabular-nums">₦{{ number_format($estimate->grand_total, 2) }}</x-table.td>
                            <x-table.td><x-badge :status="$estimate->status" /></x-table.td>
                            <x-table.td align="right">
                                <x-row-menu>
                                    <x-row-menu-item :href="route('billing.estimates.show', $estimate)">Open</x-row-menu-item>
                                    <x-row-menu-item wire:click="exportPdf({{ $estimate->id }})">Download PDF</x-row-menu-item>
                                    @if ($estimate->status === 'draft' || $estimate->status === 'sent')
                                        <x-row-menu-item :href="route('billing.estimates.edit', $estimate)">Edit</x-row-menu-item>
                                        <x-row-menu-item wire:click="openSend({{ $estimate->id }})">Email</x-row-menu-item>
                                        @if ($estimate->status === 'draft')
                                            <x-row-menu-item wire:click="send({{ $estimate->id }})">Mark sent</x-row-menu-item>
                                        @endif
                                        <x-row-menu-item wire:click="accept({{ $estimate->id }})">Accept</x-row-menu-item>
                                        <x-row-menu-item wire:click="delete({{ $estimate->id }})" wire:confirm="Delete this estimate?" danger>Delete</x-row-menu-item>
                                    @endif
                                    @if ($estimate->status === 'accepted' && ! $estimate->converted_invoice_id)
                                        <x-row-menu-item wire:click="convert({{ $estimate->id }})">Convert to invoice</x-row-menu-item>
                                    @endif
                                    @if ($estimate->converted_invoice_id)
                                        <x-row-menu-item :href="route('billing.invoices.show', $estimate->converted_invoice_id)">Open invoice</x-row-menu-item>
                                    @endif
                                </x-row-menu>
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="6">
                            <x-empty-state
                                compact
                                :title="$hasActiveFilters ? 'No matching estimates' : 'No estimates'"
                                :description="$hasActiveFilters ? 'Try clearing filters or widening the date range.' : 'Create an estimate to send to clients.'"
                            >
                                <x-slot:actions>
                                    @if ($hasActiveFilters)
                                        <x-button size="sm" variant="secondary" wire:click="clearFilters">Clear filters</x-button>
                                    @else
                                        <x-button size="sm" :href="route('billing.estimates.create')">New estimate</x-button>
                                        <x-button size="sm" variant="secondary" :href="route('billing.invoices')">Invoices</x-button>
                                    @endif
                                </x-slot:actions>
                            </x-empty-state>
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-pagination :paginator="$estimates" />
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($sendingEstimate)
        <x-drawer
            title="Email estimate"
            :description="'Send '.$sendingEstimate->estimate_number.' with PDF attachment to '.$sendingEstimate->clientAccount?->name"
            width="md"
            close-method="closeSend"
        >
            <x-drawer-form wire:submit="sendEstimate" submit-label="Send email" close-method="closeSend" target="sendEstimate">
                <x-form-section title="Recipients" description="Comma-separated emails.">
                    <x-textarea wire:model="sendEmails" label="To *" rows="3" class="sm:col-span-2" />
                    <x-textarea wire:model="sendMessage" label="Optional message" rows="3" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
