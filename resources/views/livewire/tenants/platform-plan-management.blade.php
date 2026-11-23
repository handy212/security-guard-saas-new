<div>
    <x-page-shell
        title="Plans"
        description="Pricing tiers, limits, and feature entitlements."
        :breadcrumbs="[
            ['label' => 'SaaS', 'href' => route('saas')],
            ['label' => 'Plans'],
        ]"
    >
        <x-slot:actions>
            <x-button wire:click="openCreate">Add plan</x-button>
        </x-slot:actions>

        <x-flash-status type="success" />
        @error('plan')<p role="alert" class="form-error">{{ $message }}</p>@enderror

        <div class="stat-grid">
            <x-stat-card compact label="Total plans" :value="$planStats['total']" icon="plan" />
            <x-stat-card compact label="Active" :value="$planStats['active']" icon="check" tone="success" />
            <x-stat-card compact label="In use" :value="$planStats['assigned']" icon="users" tone="info" />
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
                            <span class="text-2xl font-semibold tracking-tight tabular-nums text-zinc-900 dark:text-zinc-50">{{ money($plan->monthly_price) }}</span>
                            <span class="text-sm text-zinc-500">/mo</span>
                            @if ($plan->annual_price > 0)
                                <span class="ml-2 text-sm text-zinc-500 tabular-nums">{{ money($plan->annual_price) }}/yr</span>
                            @endif
                        </div>
                        <p class="text-xs tabular-nums text-zinc-600 dark:text-zinc-400">
                            {{ number_format($plan->max_guards ?? \App\Services\PlanLimitService::DEFAULT_MAX_GUARDS) }} guards
                            · {{ number_format($plan->max_sites ?? \App\Services\PlanLimitService::DEFAULT_MAX_SITES) }} sites
                        </p>
                        @php $includedFeatures = $plan->features ?? $defaultFeatures; @endphp
                        @if ($includedFeatures)
                            <div class="flex flex-wrap gap-1">
                                @foreach (collect($includedFeatures)->take(4) as $feature)
                                    <span class="status-chip status-chip-neutral !px-2 !py-0.5 text-[10px]">{{ $featureLabels[$feature]['label'] ?? $feature }}</span>
                                @endforeach
                                @if (count($includedFeatures) > 4)
                                    <span class="status-chip status-chip-neutral !px-2 !py-0.5 text-[10px]">+{{ count($includedFeatures) - 4 }}</span>
                                @endif
                            </div>
                        @endif
                        <div class="mt-auto flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                            <span class="text-xs tabular-nums text-zinc-500">{{ $plan->subscriptions_count }} subscriptions</span>
                            <div class="flex gap-1.5">
                                <x-button wire:click="openEdit({{ $plan->id }})" variant="secondary" size="sm">Edit</x-button>
                                @if ($plan->subscriptions_count === 0 && $plan->tenants_count === 0)
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
            width="xl"
            closeMethod="closeDrawer"
        >
            <x-drawer-form wire:submit="save" :submit-label="$editingPlanId ? 'Save changes' : 'Create plan'">
                <x-form-section title="Plan">
                    <x-input wire:model="form.name" label="Plan name" class="sm:col-span-2" />
                    <x-input wire:model="form.slug" label="Slug" hint="Leave blank to generate from the plan name." />
                    <x-input wire:model="form.paystack_plan_code" label="Paystack plan code" />
                    <x-input wire:model="form.monthly_price" label="Monthly price" type="number" step="0.01" />
                    <x-input wire:model="form.annual_price" label="Annual price" type="number" step="0.01" />
                    <x-input wire:model="form.max_guards" label="Max guards" type="number" placeholder="Default limit" />
                    <x-input wire:model="form.max_sites" label="Max sites" type="number" placeholder="Default limit" />
                    <x-select wire:model="form.status" label="Status" class="sm:col-span-2">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </x-select>
                </x-form-section>

                <x-form-section title="Feature entitlements" description="Select the modules this plan includes. Clearing every selection disables all plan-controlled modules.">
                    <div class="sm:col-span-2 grid items-start gap-3 sm:grid-cols-2">
                        @foreach ($featureGroups as $group => $features)
                            <fieldset wire:key="group-{{ $group }}" class="min-w-0 rounded-lg border border-zinc-200 px-3 pb-2 dark:border-zinc-700">
                                <legend class="px-1 text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ $group }}</legend>
                                <div class="space-y-0.5">
                                    @foreach ($features as $feature)
                                        <label class="flex cursor-pointer items-center gap-2 py-1 text-sm text-zinc-700 dark:text-zinc-300">
                                            <input type="checkbox" wire:model="form.selectedFeatures" value="{{ $feature['key'] }}" class="size-4 shrink-0 accent-accent-600" />
                                            <span>{{ $feature['label'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
