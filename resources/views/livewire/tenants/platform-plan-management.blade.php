<div>
    <x-page-shell
        title="Plans"
        description="Pricing tiers, limits, and feature entitlements."
        :breadcrumbs="[
            ['label' => 'Platform', 'href' => route('saas.tenants')],
            ['label' => 'Plans'],
        ]"
    >
        <x-slot:actions>
            <x-button wire:click="openCreate">Add plan</x-button>
        </x-slot:actions>

        <div class="stat-grid">
            <x-stat-card compact label="Total plans" :value="$planStats['total']" icon="plan" />
            <x-stat-card compact label="Active" :value="$planStats['active']" icon="check" tone="success" />
            <x-stat-card compact label="In use" :value="$planStats['assigned']" icon="users" tone="info" />
            <x-stat-card compact label="Showing" :value="$plans->count()" icon="billing" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search plans…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']" />
            </x-slot:tabs>
        </x-page-toolbar>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse($plans as $plan)
                <article class="card-surface flex flex-col overflow-hidden" wire:key="plan-{{ $plan->id }}">
                    <div class="card-header">
                        <div class="min-w-0">
                            <h3 class="card-header-title truncate">{{ $plan->name }}</h3>
                            <p class="card-header-meta font-mono">{{ $plan->slug }}</p>
                        </div>
                        <x-badge :status="$plan->status" />
                    </div>
                    <div class="flex flex-1 flex-col gap-3 p-4">
                        <div>
                            <span class="text-2xl font-semibold tracking-tight tabular-nums text-zinc-900 dark:text-zinc-50">${{ number_format($plan->monthly_price, 0) }}</span>
                            <span class="text-sm text-zinc-500">/mo</span>
                        </div>
                        <p class="text-xs tabular-nums text-zinc-600 dark:text-zinc-400">
                            {{ $plan->max_guards ? number_format($plan->max_guards).' guards' : '∞ guards' }}
                            · {{ $plan->max_sites ? number_format($plan->max_sites).' sites' : '∞ sites' }}
                        </p>
                        @if ($plan->features)
                            <div class="flex max-h-16 flex-wrap gap-1 overflow-hidden">
                                @foreach (collect($plan->features)->take(8) as $feature)
                                    <span class="status-chip status-chip-neutral !px-2 !py-0.5 text-[10px]">{{ $featureLabels[$feature]['label'] ?? $feature }}</span>
                                @endforeach
                                @if (count($plan->features) > 8)
                                    <span class="status-chip status-chip-neutral !px-2 !py-0.5 text-[10px]">+{{ count($plan->features) - 8 }}</span>
                                @endif
                            </div>
                        @endif
                        <div class="mt-auto flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                            <span class="text-xs tabular-nums text-zinc-500">{{ $plan->subscriptions_count }} tenants</span>
                            <div class="flex gap-1.5">
                                <x-button wire:click="openEdit({{ $plan->id }})" variant="secondary" size="sm">Edit</x-button>
                                @if ($plan->subscriptions_count === 0)
                                    <x-button wire:click="delete({{ $plan->id }})" wire:confirm="Delete {{ $plan->name }}?" variant="danger" size="sm">Delete</x-button>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-8">
                    <x-empty-state title="No plans yet" description="Create a pricing tier for tenants.">
                        <x-slot:actions>
                            <x-button size="sm" wire:click="openCreate">Add plan</x-button>
                        </x-slot:actions>
                    </x-empty-state>
                </div>
            @endforelse
        </div>
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingPlanId ? 'Edit plan' : 'Add plan'"
            description="Set pricing, limits, and feature entitlements."
            width="lg"
            closeMethod="closeDrawer"
        >
            <x-drawer-form wire:submit="save" :submit-label="$editingPlanId ? 'Save changes' : 'Create plan'">
                <x-form-section title="Plan">
                    <x-input wire:model.live="form.name" label="Plan name" class="sm:col-span-2" />
                    <x-input wire:model="form.slug" label="Slug" />
                    <x-input wire:model="form.paystack_plan_code" label="Paystack plan code" />
                    <x-input wire:model="form.monthly_price" label="Monthly price" type="number" step="0.01" />
                    <x-input wire:model="form.annual_price" label="Annual price" type="number" step="0.01" />
                    <x-input wire:model="form.max_guards" label="Max guards" type="number" placeholder="Unlimited" />
                    <x-input wire:model="form.max_sites" label="Max sites" type="number" placeholder="Unlimited" />
                    <x-select wire:model="form.status" label="Status" class="sm:col-span-2">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </x-select>
                </x-form-section>

                <x-form-section title="Feature entitlements" description="Modules available on this plan.">
                    <div class="sm:col-span-2 max-h-64 space-y-4 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        @foreach ($featureGroups as $group => $features)
                            <div wire:key="group-{{ $group }}">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $group }}</p>
                                <div class="space-y-2">
                                    @foreach ($features as $feature)
                                        <label class="flex items-start gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                            <input type="checkbox" wire:model="form.selectedFeatures" value="{{ $feature['key'] }}" class="mt-0.5 rounded border-zinc-300 text-accent-600 focus:ring-accent-600/20 dark:border-zinc-600 dark:bg-zinc-900" />
                                            <span>{{ $feature['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
