<div>
    <x-page-shell title="Back Office" compact stable-header>
        <x-slot:actions>
            @if($canBilling)
                <x-button :href="route('billing.invoices.create')">New invoice</x-button>
                <x-button variant="secondary" :href="route('billing.payments')">Payments</x-button>
            @endif
            @if($canEstimates)
                <x-button variant="secondary" :href="route('billing.estimates.create')">New estimate</x-button>
            @endif
        </x-slot:actions>

        <x-sub-sidebar-layout class="billing-workspace">
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>

            @if ($lowStockCount !== null || $openComplaints !== null || $canBilling)
                <section aria-label="Items needing attention" class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                    @if ($lowStockCount !== null)
                        <x-stat-card compact label="Categories low on stock" :value="$lowStockCount" icon="equipment" :tone="$lowStockCount ? 'warning' : 'default'" :href="route('assets.inventory')" />
                    @endif
                    @if ($openComplaints !== null)
                        <x-stat-card compact label="Open client complaints" :value="$openComplaints" icon="clients" :tone="$openComplaints ? 'warning' : 'default'" :href="route('clients.index', ['tab' => 'complaints', 'status' => 'open'])" />
                    @endif
                    @if ($canBilling)
                        <x-stat-card compact label="Overdue invoices" :value="$stats['invoices_overdue']" icon="billing" :tone="$stats['invoices_overdue'] ? 'danger' : 'default'" :href="route('billing.invoices', ['status' => 'overdue'])" />
                    @endif
                </section>
            @endif

            @if($canBilling || $canExpenses || $canEstimates)
                <section aria-labelledby="back-office-cash-flow">
                    <div class="billing-section-heading">
                        <div>
                            <h2 id="back-office-cash-flow">Cash flow</h2>
                            <p>Receivables, collections, and operating spend for this month.</p>
                        </div>
                    </div>
                    <div class="billing-primary-stats">
                        @if($canBilling)
                            <x-stat-card compact label="Amount due" :value="money($stats['amount_due'], 0)" icon="billing" tone="warning" :href="route('billing.invoices', ['status' => 'outstanding'])" />
                            <x-stat-card compact label="Collected MTD" :value="money($stats['collected_mtd'], 0)" icon="check" tone="success" :href="route('billing.payments')" />
                        @endif
                        @if($canExpenses)
                            <x-stat-card compact label="Expenses MTD" :value="money($stats['expenses_mtd'], 0)" icon="plan" tone="warning" :href="route('billing.expenses')" />
                        @endif
                        @if($canBilling)
                            <x-stat-card compact label="Overdue" :value="money($stats['overdue_due'], 0)" icon="pause" :tone="$stats['overdue_due'] > 0 ? 'danger' : 'default'" :href="route('billing.invoices', ['status' => 'overdue'])" />
                        @endif
                    </div>
                </section>

                <section class="billing-summary-strip" aria-labelledby="back-office-summary">
                    <div>
                        <p id="back-office-summary" class="billing-summary-title">Workspace summary</p>
                        <p class="billing-summary-copy">Current billing pipeline and approved operating context.</p>
                    </div>
                    @if($canBilling)
                        <a href="{{ route('billing.invoices') }}" wire:navigate><span>Revenue billed MTD</span><strong>{{ money($stats['revenue_mtd'], 0) }}</strong></a>
                        <a href="{{ route('billing.invoices', ['status' => 'outstanding']) }}" wire:navigate><span>Open invoices</span><strong>{{ $stats['invoices_open'] }}</strong></a>
                    @endif
                    @if($canEstimates)
                        <a href="{{ route('billing.estimates') }}" wire:navigate><span>Estimate pipeline</span><strong>{{ money($stats['estimates_pipeline'], 0) }}</strong></a>
                        <a href="{{ route('billing.estimates') }}" wire:navigate><span>Open estimates</span><strong>{{ $stats['estimates_open'] }}</strong></a>
                    @endif
                </section>
            @endif

            @if ($canBilling || $canExpenses)
            <div @class(['billing-overview-grid items-start', 'billing-overview-grid-single' => ! ($canBilling && $canExpenses)])>
                @if ($canBilling)
                <x-section-card title="Recent invoices" compact flush>
                    <x-slot:actions>
                        <a href="{{ route('billing.invoices') }}" class="page-link" wire:navigate>View all</a>
                    </x-slot:actions>
                    @forelse ($recentInvoices as $invoice)
                        <a href="{{ route('billing.invoices.show', $invoice) }}" wire:navigate class="list-row" wire:key="inv-{{ $invoice->id }}">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium tabular-nums text-zinc-900 dark:text-zinc-100">{{ $invoice->invoice_number }}</div>
                                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $invoice->clientAccount?->name }}</div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ money($invoice->grand_total) }}</div>
                                <x-badge :status="$invoice->status" />
                            </div>
                        </a>
                    @empty
                        <div class="p-3">
                            <x-empty-state inline title="No invoices yet" description="Generate invoices from client coverage.">
                                <x-slot:actions>
                                    <x-button size="sm" :href="route('billing.invoices')">Invoices</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </x-section-card>

                @endif
                @if($canExpenses)
                <x-section-card title="Recent expenses" compact flush>
                    <x-slot:actions>
                        <a href="{{ route('billing.expenses') }}" class="page-link" wire:navigate>View all</a>
                    </x-slot:actions>
                    @forelse ($recentExpenses as $expense)
                        <div class="list-row" wire:key="exp-{{ $expense->id }}">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $expense->title }}</div>
                                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $expense->category?->name ?? 'Uncategorized' }} · {{ $expense->expense_date?->format('M j') }}</div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ money($expense->amount) }}</div>
                                <x-badge :status="$expense->status" />
                            </div>
                        </div>
                    @empty
                        <div class="list-row gap-2">
                            <p class="flex-1 text-sm text-zinc-500 dark:text-zinc-400">No expenses yet.</p>
                            <x-button size="sm" variant="secondary" :href="route('billing.expenses')">Add expense</x-button>
                        </div>
                    @endforelse
                </x-section-card>
                @endif
            </div>
            @endif
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
