<div>
    <x-page-shell title="Plans" description="Pricing tiers, limits, and feature entitlements.">
        <x-slot:actions>
            <x-button wire:click="openCreate">Add plan</x-button>
        </x-slot:actions>

        <x-stat-grid>
            <x-stat-card compact label="Total plans" :value="$planStats['total']" icon="plan" />
            <x-stat-card compact label="Active" :value="$planStats['active']" icon="check" tone="success" />
            <x-stat-card compact label="In use" :value="$planStats['assigned']" icon="users" tone="info" />
            <x-stat-card compact label="Showing" :value="$plans->count()" icon="billing" />
        </x-stat-grid>

        <x-page-toolbar search="search" searchPlaceholder="Search plans…">
            <x-slot:tabs>
                <x-segment-control model="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']" />
            </x-slot:tabs>
        </x-page-toolbar>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($plans as $plan)
                <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-4 shadow-sm" wire:key="plan-{{ $plan->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="font-semibold text-zinc-900">{{ $plan->name }}</h3>
                            <p class="font-mono text-[11px] text-zinc-500">{{ $plan->slug }}</p>
                        </div>
                        <x-badge :status="$plan->status" />
                    </div>
                    <div class="mt-3">
                        <span class="text-2xl font-bold">${{ number_format($plan->monthly_price, 0) }}</span>
                        <span class="text-sm text-zinc-500">/mo</span>
                    </div>
                    <p class="mt-2 text-xs text-zinc-600">
                        {{ $plan->max_guards ? number_format($plan->max_guards).' guards' : '∞ guards' }}
                        · {{ $plan->max_sites ? number_format($plan->max_sites).' sites' : '∞ sites' }}
                    </p>
                    @if ($plan->features)
                        <div class="mt-3 flex flex-wrap gap-1">
                            @foreach ($plan->features as $feature)
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium text-zinc-600">{{ $featureLabels[$feature]['label'] ?? $feature }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="mt-4 flex items-center justify-between border-t border-zinc-100 pt-3">
                        <span class="text-xs text-zinc-500">{{ $plan->subscriptions_count }} tenants</span>
                        <div class="flex gap-1">
                            <x-button wire:click="openEdit({{ $plan->id }})" variant="secondary" size="sm">Edit</x-button>
                            @if ($plan->subscriptions_count === 0)
                                <x-button wire:click="delete({{ $plan->id }})" wire:confirm="Delete {{ $plan->name }}?" variant="danger" size="sm">Delete</x-button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8"><x-empty-state title="No plans yet" /></div>
            @endforelse
        </div>
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingPlanId ? 'Edit plan' : 'Add plan'" width="lg" closeMethod="closeDrawer">
            <form wire:submit="save" class="space-y-4">
                <x-input wire:model.live="form.name" label="Plan name" />
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-input wire:model="form.slug" label="Slug" />
                    <x-input wire:model="form.paystack_plan_code" label="Paystack plan code" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-input wire:model="form.monthly_price" label="Monthly price" type="number" step="0.01" />
                    <x-input wire:model="form.annual_price" label="Annual price" type="number" step="0.01" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-input wire:model="form.max_guards" label="Max guards" type="number" placeholder="Unlimited" />
                    <x-input wire:model="form.max_sites" label="Max sites" type="number" placeholder="Unlimited" />
                </div>
                <div>
                    <p class="mb-2 text-sm font-medium text-zinc-700">Feature entitlements</p>
                    <div class="max-h-64 space-y-4 overflow-y-auto rounded-lg border border-zinc-200 p-3">
                        @foreach ($featureGroups as $group => $features)
                            <div wire:key="group-{{ $group }}">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $group }}</p>
                                <div class="space-y-2">
                                    @foreach ($features as $feature)
                                        <label class="flex items-start gap-2 text-sm">
                                            <input type="checkbox" wire:model="form.selectedFeatures" value="{{ $feature['key'] }}" class="mt-0.5 rounded border-zinc-300" />
                                            <span>{{ $feature['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <select wire:model="form.status" class="form-input w-full">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <div class="flex gap-2 border-t border-zinc-100 pt-4">
                    <x-button type="submit">{{ $editingPlanId ? 'Save changes' : 'Create plan' }}</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeDrawer">Cancel</x-button>
                </div>
            </form>
        </x-drawer>
    @endif
</div>
