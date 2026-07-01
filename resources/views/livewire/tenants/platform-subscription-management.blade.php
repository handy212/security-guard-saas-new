<div>
    <x-page-shell title="Subscriptions" description="Tenant billing status and plan assignments.">
        <div class="grid grid-cols-4 gap-2">
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
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Tenant</th>
                    <th class="px-3 py-2">Plan</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Trial ends</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscriptions as $subscription)
                    <tr class="table-row-hover" wire:key="sub-{{ $subscription->id }}">
                        <td class="px-3 py-2 font-medium">{{ $subscription->tenant?->name }}</td>
                        <td class="px-3 py-2 text-zinc-600">{{ $subscription->plan?->name ?? '—' }}</td>
                        <td class="px-3 py-2"><x-badge :status="$subscription->status" /></td>
                        <td class="px-3 py-2 text-xs text-zinc-500">{{ $subscription->trial_ends_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">
                            <x-button wire:click="openEdit({{ $subscription->id }})" variant="secondary" size="sm">Edit</x-button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-8"><x-empty-state title="No subscriptions" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$subscriptions" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="Edit subscription" width="md" closeMethod="closeDrawer">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700">Plan</label>
                    <select wire:model="form.subscription_plan_id" class="form-input w-full">
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700">Status</label>
                    <select wire:model="form.status" class="form-input w-full">
                        <option value="active">Active</option>
                        <option value="trial">Trial</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="past_due">Past due</option>
                    </select>
                </div>
                <x-input wire:model="form.trial_ends_at" label="Trial ends" type="date" />
                <div class="flex gap-2 border-t border-zinc-100 pt-4">
                    <x-button type="submit">Save</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeDrawer">Cancel</x-button>
                </div>
            </form>
        </x-drawer>
    @endif
</div>
