<div>
    <x-page-shell
        title="Expenses"
        description="Track vendor bills, reimbursements, and operational spend."
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Expenses'],
        ]"
    >
        <x-slot:actions>
            <x-button wire:click="openForm">Add expense</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            <div class="stat-grid">
                <x-stat-card compact label="Total" :value="$stats['total']" icon="billing" />
                <x-stat-card compact label="Draft" :value="$stats['draft']" icon="plan" />
                <x-stat-card compact label="Approved" :value="$stats['approved']" icon="check" tone="info" />
                <x-stat-card compact label="MTD spend" :value="'₦'.number_format($stats['amount_mtd'], 0)" icon="pause" tone="warning" />
            </div>

            <x-page-toolbar search="search" searchPlaceholder="Search expenses…">
                <x-slot:tabs>
                    <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'draft' => 'Draft', 'approved' => 'Approved', 'paid' => 'Paid']" />
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
                    <x-input wire:model.live="dateFrom" type="date" label="From date" />
                    <x-input wire:model.live="dateTo" type="date" label="To date" />
                </div>
            @endif

            <x-data-table>
                <x-table.head>
                    <tr>
                        <x-table.th>Expense #</x-table.th>
                        <x-table.th>Title</x-table.th>
                        <x-table.th responsive="md">Category</x-table.th>
                        <x-table.th>Date</x-table.th>
                        <x-table.th>Amount</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th responsive="lg">Receipt</x-table.th>
                        <x-table.th align="right">Actions</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr class="table-row-hover" wire:key="exp-{{ $expense->id }}">
                            <x-table.td mono>{{ $expense->expense_number }}</x-table.td>
                            <x-table.td>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $expense->title }}</div>
                                @if ($expense->vendor_name)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $expense->vendor_name }}</div>
                                @endif
                            </x-table.td>
                            <x-table.td responsive="md" muted>{{ $expense->category?->name ?? '—' }}</x-table.td>
                            <x-table.td muted class="tabular-nums">{{ $expense->expense_date?->format('M j, Y') }}</x-table.td>
                            <x-table.td class="font-semibold tabular-nums">₦{{ number_format($expense->amount, 2) }}</x-table.td>
                            <x-table.td><x-badge :status="$expense->status" /></x-table.td>
                            <x-table.td responsive="lg">
                                @if ($expense->receipt_path)
                                    <a href="{{ route('files.expense-receipt', $expense) }}" class="page-link" target="_blank" rel="noopener">View</a>
                                @else
                                    <span class="text-xs text-zinc-400">—</span>
                                @endif
                            </x-table.td>
                            <x-table.td align="right">
                                @if (in_array($expense->status, ['draft', 'submitted', 'approved']) || $expense->receipt_path)
                                    <x-row-menu>
                                        @if (in_array($expense->status, ['draft', 'submitted']))
                                            <x-row-menu-item wire:click="edit({{ $expense->id }})">Edit</x-row-menu-item>
                                            <x-row-menu-item wire:click="approve({{ $expense->id }})">Approve</x-row-menu-item>
                                            <x-row-menu-item wire:click="delete({{ $expense->id }})" wire:confirm="Delete this expense?" danger>Delete</x-row-menu-item>
                                        @endif
                                        @if ($expense->status === 'approved')
                                            <x-row-menu-item wire:click="markPaid({{ $expense->id }})">Mark paid</x-row-menu-item>
                                        @endif
                                        @if ($expense->receipt_path)
                                            <x-row-menu-item :href="route('files.expense-receipt', $expense)">View receipt</x-row-menu-item>
                                        @endif
                                    </x-row-menu>
                                @else
                                    <span class="text-xs text-zinc-400">—</span>
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="8">
                            <x-empty-state
                                compact
                                :title="$hasActiveFilters ? 'No matching expenses' : 'No expenses'"
                                :description="$hasActiveFilters ? 'Try clearing filters or widening the date range.' : 'Record vendor bills and operational costs.'"
                            >
                                <x-slot:actions>
                                    @if ($hasActiveFilters)
                                        <x-button size="sm" variant="secondary" wire:click="clearFilters">Clear filters</x-button>
                                    @else
                                        <x-button size="sm" wire:click="openForm">Add expense</x-button>
                                    @endif
                                </x-slot:actions>
                            </x-empty-state>
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-pagination :paginator="$expenses" />
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingId ? 'Edit expense' : 'Add expense'" description="Record a vendor bill or reimbursement." width="lg" close-method="closeForm">
            <x-drawer-form wire:submit="save" :submit-label="$editingId ? 'Save changes' : 'Save expense'" close-method="closeForm">
                <x-form-section title="Expense">
                    <x-input wire:model="form.title" label="Title *" class="sm:col-span-2" />
                    <x-select wire:model="form.expense_category_id" label="Category">
                        <option value="">Uncategorized</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="form.expense_date" type="date" label="Date *" />
                    <x-input wire:model="form.amount" type="number" step="0.01" min="0.01" label="Amount (₦) *" />
                    <x-input wire:model="form.vendor_name" label="Vendor" />
                    <x-select wire:model="form.payment_method" label="Payment method">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="mobile_money">Mobile money</option>
                        <option value="card">Card</option>
                        <option value="cheque">Cheque</option>
                    </x-select>
                    <x-file-input wire:model="receiptFile" label="Receipt (optional)" hint="JPG, PNG, or PDF up to 10MB" class="sm:col-span-2" />
                </x-form-section>
                <x-form-section title="Allocation" description="Optional — link spend to a client or site.">
                    <x-select wire:model="form.client_account_id" label="Client" class="sm:col-span-2">
                        <option value="">None</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="form.site_id" label="Site" class="sm:col-span-2">
                        <option value="">None</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <x-textarea wire:model="form.description" label="Notes" rows="3" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
