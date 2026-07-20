<div>
    <x-page-shell
        title="Subscriptions"
        description="Tenant billing status and plan assignments."
        :breadcrumbs="[
            ['label' => 'Platform', 'href' => route('saas.tenants')],
            ['label' => 'Subscriptions'],
        ]"
    >
        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$stats['total']" icon="users" />
            <x-stat-card compact label="Active" :value="$stats['active']" icon="check" tone="success" />
            <x-stat-card compact label="Trial" :value="$stats['trial']" icon="plan" tone="info" />
            <x-stat-card compact label="Showing" :value="$subscriptions->count()" icon="billing" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search tenants…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'active' => 'Active', 'trial' => 'Trial', 'cancelled' => 'Cancelled']" />
            </x-slot:tabs>
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Tenant</x-table.th>
                    <x-table.th responsive="md">Plan</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th responsive="lg">Trial ends</x-table.th>
                    <x-table.th align="right" class="w-12"></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse ($subscriptions as $subscription)
                    <tr class="table-row-hover" wire:key="sub-{{ $subscription->id }}">
                        <x-table.td>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $subscription->tenant?->name }}</span>
                            @if ($subscription->tenant?->slug)
                                <div class="font-mono text-[11px] text-zinc-500 dark:text-zinc-400">{{ $subscription->tenant->slug }}</div>
                            @endif
                        </x-table.td>
                        <x-table.td responsive="md" muted>{{ $subscription->plan?->name ?? '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$subscription->status" /></x-table.td>
                        <x-table.td responsive="lg" muted class="tabular-nums">{{ $subscription->trial_ends_at?->format('M j, Y') ?? '—' }}</x-table.td>
                        <x-table.td align="right">
                            <x-row-menu>
                                <x-row-menu-item wire:click="openEdit({{ $subscription->id }})">Edit subscription</x-row-menu-item>
                                @if ($subscription->tenant_id)
                                    <x-row-menu-item :href="route('saas.tenants', ['search' => $subscription->tenant?->slug])">View tenant</x-row-menu-item>
                                @endif
                            </x-row-menu>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="5">
                        <x-empty-state
                            title="No subscriptions"
                            description="Subscriptions appear when tenants are assigned a plan."
                        />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$subscriptions" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="Edit subscription" description="Update plan and billing status." width="md" closeMethod="closeDrawer">
            <x-drawer-form wire:submit="save" submit-label="Save subscription">
                <x-form-section title="Billing">
                    <x-select wire:model="form.subscription_plan_id" label="Plan" class="sm:col-span-2">
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
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
