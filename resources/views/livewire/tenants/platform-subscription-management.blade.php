<div>
    <x-page-shell title="Subscriptions" description="Tenant billing status and plan assignments.">
        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$stats['total']" icon="users" />
            <x-stat-card compact label="Active" :value="$stats['active']" icon="check" tone="success" />
            <x-stat-card compact label="Trial" :value="$stats['trial']" icon="plan" tone="info" />
            <x-stat-card compact label="Showing" :value="$subscriptions->count()" icon="billing" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search tenants…">
            <x-slot:tabs>
                <x-segment-control model="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'active' => 'Active', 'trial' => 'Trial', 'cancelled' => 'Cancelled']" />
            </x-slot:tabs>
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Tenant</x-table.th>
                    <x-table.th>Plan</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th>Trial ends</x-table.th>
                    <x-table.th align="right">Actions</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse ($subscriptions as $subscription)
                    <tr class="table-row-hover" wire:key="sub-{{ $subscription->id }}">
                        <x-table.td><span class="font-medium text-zinc-900">{{ $subscription->tenant?->name }}</span></x-table.td>
                        <x-table.td muted>{{ $subscription->plan?->name ?? '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$subscription->status" /></x-table.td>
                        <x-table.td mono>{{ $subscription->trial_ends_at?->format('M j, Y') ?? '—' }}</x-table.td>
                        <x-table.td align="right">
                            <x-button wire:click="openEdit({{ $subscription->id }})" variant="secondary" size="sm">Edit</x-button>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="5">
                        <x-empty-state title="No subscriptions" />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$subscriptions" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="Edit subscription" width="md" closeMethod="closeDrawer">
            <x-drawer-form wire:submit="save" submit-label="Save subscription">
                <x-select wire:model="form.subscription_plan_id" label="Plan">
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model="form.status" label="Status">
                    <option value="active">Active</option>
                    <option value="trial">Trial</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="past_due">Past due</option>
                </x-select>
                <x-input wire:model="form.trial_ends_at" label="Trial ends" type="date" />
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
