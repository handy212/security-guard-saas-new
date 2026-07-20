<div>
    <x-page-shell
        title="Back Office"
        description="Invoices, estimates, expenses, payroll, compliance, and analytics."
        :breadcrumbs="[['label' => 'Back Office']]"
    >
        <x-slot:actions>
            <x-button :href="route('billing.invoices.create')">New invoice</x-button>
            <x-button variant="secondary" :href="route('billing.estimates.create')">New estimate</x-button>
            <x-button variant="secondary" :href="route('billing.payments')">Payments</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>

            <div class="stat-grid">
                <x-stat-card compact label="Amount due" :value="'₦'.number_format($stats['amount_due'], 0)" icon="billing" tone="warning" :href="route('billing.invoices', ['status' => 'sent'])" />
                <x-stat-card compact label="Collected MTD" :value="'₦'.number_format($stats['collected_mtd'], 0)" icon="check" tone="success" :href="route('billing.payments')" />
                <x-stat-card compact label="Expenses MTD" :value="'₦'.number_format($stats['expenses_mtd'], 0)" icon="plan" tone="warning" :href="route('billing.expenses')" />
                <x-stat-card compact label="Overdue" :value="'₦'.number_format($stats['overdue_due'], 0)" icon="pause" :tone="$stats['overdue_due'] > 0 ? 'danger' : 'default'" :href="route('billing.invoices', ['status' => 'overdue'])" />
            </div>

            <div class="stat-grid">
                <x-stat-card compact label="Revenue billed MTD" :value="'₦'.number_format($stats['revenue_mtd'], 0)" icon="billing" />
                <x-stat-card compact label="Open invoices" :value="$stats['invoices_open']" icon="plan" :href="route('billing.invoices')" />
                <x-stat-card compact label="Estimate pipeline" :value="'₦'.number_format($stats['estimates_pipeline'], 0)" icon="pause" tone="info" :href="route('billing.estimates')" />
                <x-stat-card compact label="Open estimates" :value="$stats['estimates_open']" icon="check" :href="route('billing.estimates')" />
            </div>

            <div class="flex flex-wrap gap-1.5">
                <a href="{{ route('billing.expenses') }}" class="quick-action" wire:navigate>Expenses</a>
                <a href="{{ route('billing.payroll') }}" class="quick-action" wire:navigate>Payroll</a>
                @can('compliance.manage')
                    <a href="{{ route('compliance.dashboard') }}" class="quick-action" wire:navigate>Compliance</a>
                @endcan
                @can('analytics.view')
                    <a href="{{ route('analytics.dashboard') }}" class="quick-action" wire:navigate>Analytics</a>
                @endcan
            </div>

            <div class="page-grid-2">
                <x-section-card title="Recent invoices" flush>
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
                                <div class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">₦{{ number_format($invoice->grand_total, 2) }}</div>
                                <x-badge :status="$invoice->status" />
                            </div>
                        </a>
                    @empty
                        <div class="p-3">
                            <x-empty-state compact title="No invoices yet" description="Generate invoices from client coverage.">
                                <x-slot:actions>
                                    <x-button size="sm" :href="route('billing.invoices')">Invoices</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </x-section-card>

                <x-section-card title="Recent expenses" flush>
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
                                <div class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">₦{{ number_format($expense->amount, 2) }}</div>
                                <x-badge :status="$expense->status" />
                            </div>
                        </div>
                    @empty
                        <div class="p-3">
                            <x-empty-state compact title="No expenses yet" description="Track vendor bills and operational spend.">
                                <x-slot:actions>
                                    <x-button size="sm" :href="route('billing.expenses')">Add expense</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </x-section-card>
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
