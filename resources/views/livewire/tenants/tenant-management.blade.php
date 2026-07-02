<div>
    <x-page-shell title="Tenants" description="Onboard and manage security companies.">
        <x-slot:actions>
            <x-button wire:click="exportTenants" variant="secondary">Export CSV</x-button>
            <x-button wire:click="openCreateTenant">Add tenant</x-button>
        </x-slot:actions>

        <div class="stat-grid">
            <x-stat-card
                compact
                label="Total"
                :value="$tenantStats['total']"
                icon="users"
                wire:click="applyStatFilter('total')"
                class="cursor-pointer text-left transition hover:border-zinc-300"
                :active="$statusFilter === 'all' && $planFilter === 'all' && $search === ''"
            />
            <x-stat-card
                compact
                label="Active"
                :value="$tenantStats['active']"
                icon="check"
                tone="success"
                wire:click="applyStatFilter('active')"
                class="cursor-pointer text-left transition hover:border-zinc-300"
                :active="$statusFilter === 'active' && $planFilter === 'all'"
            />
            <x-stat-card
                compact
                label="Suspended"
                :value="$tenantStats['suspended']"
                icon="pause"
                :tone="$tenantStats['suspended'] > 0 ? 'warning' : 'default'"
                wire:click="applyStatFilter('suspended')"
                class="cursor-pointer text-left transition hover:border-zinc-300"
                :active="$statusFilter === 'suspended'"
            />
            <x-stat-card
                compact
                label="No plan"
                :value="$tenantStats['without_plan']"
                icon="plan"
                :tone="$tenantStats['without_plan'] > 0 ? 'info' : 'default'"
                wire:click="applyStatFilter('without_plan')"
                class="cursor-pointer text-left transition hover:border-zinc-300"
                :active="$planFilter === 'none'"
            />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search…">
            <x-slot:tabs>
                <x-segment-control
                    model="statusFilter"
                    :active="$statusFilter"
                    :options="['all' => 'All', 'active' => 'Active', 'suspended' => 'Suspended']"
                />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="table-action">
                        Clear filters
                    </button>
                @endif
                <x-filter-select wire:model.live="planFilter">
                    <option value="all">All plans</option>
                    <option value="none">No plan</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                    @endforeach
                </x-filter-select>
                <x-filter-select wire:model.live="sortBy">
                    <option value="name">Sort: Name</option>
                    <option value="created">Sort: Newest</option>
                    <option value="users">Sort: Users</option>
                </x-filter-select>
            </x-slot:controls>
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Company</x-table.th>
                    <x-table.th responsive="md">Subdomain</x-table.th>
                    <x-table.th responsive="lg">Users</x-table.th>
                    <x-table.th responsive="lg">Guards</x-table.th>
                    <x-table.th responsive="xl">Plan</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right" class="w-12"></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($tenants as $tenant)
                    @php
                        $planName = $plans->firstWhere('id', $tenant->plan_id ?? $tenant->subscription?->subscription_plan_id)?->name;
                        $trialEndingSoon = $tenant->trial_ends_at
                            && $tenant->trial_ends_at->isFuture()
                            && $tenant->trial_ends_at->lte(now()->addDays(14));
                    @endphp
                    <tr
                        class="table-row-hover cursor-pointer"
                        wire:key="tenant-{{ $tenant->id }}"
                        wire:click="openViewTenant({{ $tenant->id }})"
                    >
                        <x-table.td>
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-xs font-semibold text-zinc-600">
                                    {{ strtoupper(substr($tenant->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate font-medium text-zinc-900">{{ $tenant->name }}</div>
                                    <div class="truncate font-mono text-[11px] text-zinc-500">{{ $tenant->slug }}</div>
                                    @if ($trialEndingSoon)
                                        <div class="mt-0.5 text-[10px] font-medium text-amber-700">
                                            Trial ends {{ $tenant->trial_ends_at->format('M j') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </x-table.td>
                        <x-table.td responsive="md" muted>{{ $tenant->subdomain ?: '—' }}</x-table.td>
                        <x-table.td responsive="lg" muted>{{ number_format($tenant->users_count) }}</x-table.td>
                        <x-table.td responsive="lg" muted>{{ number_format($tenant->guards_count) }}</x-table.td>
                        <x-table.td responsive="xl" muted>{{ $planName ?? '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$tenant->status ?? 'active'" /></x-table.td>
                        <x-table.td align="right" wire:click.stop>
                            <x-row-menu>
                                <x-row-menu-item wire:click="openViewTenant({{ $tenant->id }})">View details</x-row-menu-item>
                                @if (($tenant->status ?? 'active') === 'active')
                                    <x-row-menu-item wire:click="enterTenant({{ $tenant->id }})">Open tenant app</x-row-menu-item>
                                @endif
                                <x-row-menu-item wire:click="openEditTenant({{ $tenant->id }})">Edit</x-row-menu-item>
                                @if (($tenant->status ?? 'active') === 'active')
                                    <x-row-menu-item wire:click="updateTenantStatus({{ $tenant->id }}, 'suspended')" wire:confirm="Suspend this tenant?" danger>Suspend</x-row-menu-item>
                                @else
                                    <x-row-menu-item wire:click="updateTenantStatus({{ $tenant->id }}, 'active')" wire:confirm="Activate this tenant?">Activate</x-row-menu-item>
                                @endif
                            </x-row-menu>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="7">
                        <x-empty-state
                            :title="$hasActiveFilters ? 'No matching tenants' : 'No tenants yet'"
                            :description="$hasActiveFilters ? 'Try adjusting your filters.' : 'Add your first security company.'"
                        >
                            <x-slot:actions>
                                @if (! $hasActiveFilters)
                                    <x-button wire:click="openCreateTenant" size="sm">Add tenant</x-button>
                                @endif
                            </x-slot:actions>
                        </x-empty-state>
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$tenants" />
    </x-page-shell>

    @if ($showDetail && $viewingTenant)
        <x-drawer title="{{ $viewingTenant->name }}" description="Manage tenant settings and access." width="lg" closeMethod="closeDetail">
            <div class="space-y-4">
                @php
                    $billingStatus = $viewingTenant->subscription?->status;
                    $billingValue = $billingStatus ? ucfirst(str_replace('_', ' ', $billingStatus)) : 'None';
                    $billingTone = match ($billingStatus) {
                        'active' => 'success',
                        'trial' => 'info',
                        'past_due', 'cancelled' => 'warning',
                        default => 'default',
                    };
                    $trialEndingSoon = $viewingTenant->trial_ends_at
                        && $viewingTenant->trial_ends_at->isFuture()
                        && $viewingTenant->trial_ends_at->lte(now()->addDays(14));
                @endphp

                @if ($trialEndingSoon)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        Trial ends {{ $viewingTenant->trial_ends_at->format('M j, Y') }} ({{ $viewingTenant->trial_ends_at->diffForHumans() }})
                    </div>
                @endif

                <div class="grid grid-cols-3 gap-2">
                    <x-stat-card stacked label="Users" :value="$viewingTenant->users_count" icon="users" class="h-full" />
                    <x-stat-card stacked label="Guards" :value="$viewingTenant->guards_count" icon="guards" tone="info" class="h-full" />
                    <x-stat-card stacked label="Billing" :value="$billingValue" icon="billing" :tone="$billingTone" class="h-full" />
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-button
                        type="button"
                        size="sm"
                        variant="secondary"
                        x-data="{ copied: false }"
                        @click="navigator.clipboard.writeText(@js($viewingTenant->slug)); copied = true; setTimeout(() => copied = false, 1500)"
                        x-text="copied ? 'Copied!' : 'Copy slug'"
                    />
                    @if ($viewingTenant->subdomain)
                        <x-button
                            type="button"
                            size="sm"
                            variant="secondary"
                            x-data="{ copied: false }"
                            @click="navigator.clipboard.writeText(@js($viewingTenant->subdomain)); copied = true; setTimeout(() => copied = false, 1500)"
                            x-text="copied ? 'Copied!' : 'Copy subdomain'"
                        />
                    @endif
                    @if ($viewingTenant->subscription)
                        <x-button size="sm" variant="secondary" :href="route('saas.subscriptions', ['search' => $viewingTenant->slug])">View subscription</x-button>
                    @endif
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700">Subscription plan</label>
                    <select
                        class="form-input w-full"
                        wire:change="assignTenantPlan({{ $viewingTenant->id }}, $event.target.value)"
                    >
                        @php $currentPlanId = $viewingTenant->plan_id ?? $viewingTenant->subscription?->subscription_plan_id; @endphp
                        <option value="0" @selected(! $currentPlanId)>No plan</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected($currentPlanId === $plan->id)>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>

                <dl class="grid gap-2 text-sm">
                    <div class="flex justify-between gap-4 border-b border-zinc-100 py-2"><dt class="text-zinc-500">Slug</dt><dd class="font-mono text-zinc-900">{{ $viewingTenant->slug }}</dd></div>
                    <div class="flex justify-between gap-4 border-b border-zinc-100 py-2"><dt class="text-zinc-500">Subdomain</dt><dd class="text-zinc-900">{{ $viewingTenant->subdomain ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4 border-b border-zinc-100 py-2"><dt class="text-zinc-500">Domain</dt><dd class="text-zinc-900">{{ $viewingTenant->domain ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-4 border-b border-zinc-100 py-2"><dt class="text-zinc-500">Trial ends</dt><dd class="text-zinc-900">{{ $viewingTenant->trial_ends_at?->format('M j, Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4 py-2"><dt class="text-zinc-500">Created</dt><dd class="text-zinc-900">{{ $viewingTenant->created_at?->format('M j, Y') }}</dd></div>
                </dl>

                @if ($viewingTenant->users->isNotEmpty())
                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500">Users</p>
                        <ul class="divide-y divide-zinc-100 rounded-lg border border-zinc-200">
                            @foreach ($viewingTenant->users as $user)
                                <li class="px-3 py-2 text-sm" wire:key="tenant-user-{{ $user->id }}">
                                    @if ($resettingUserId === $user->id)
                                        <form wire:submit="resetAdminPassword({{ $viewingTenant->id }})" class="space-y-2">
                                            <p class="font-medium text-zinc-900">Reset password · {{ $user->name }}</p>
                                            <p class="text-xs text-zinc-500">{{ $user->email }}</p>
                                            <x-input wire:model="resetPassword" label="New password" type="password" hint="Min. 12 characters." />
                                            <div class="flex gap-2">
                                                <x-button type="submit" size="sm" wire:loading.attr="disabled" wire:target="resetAdminPassword">Save</x-button>
                                                <x-button type="button" size="sm" variant="secondary" wire:click="cancelResetPassword">Cancel</x-button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="flex items-center justify-between gap-2">
                                            <div>
                                                <span class="font-medium text-zinc-900">{{ $user->name }}</span>
                                                <span class="text-zinc-500"> · {{ $user->email }}</span>
                                            </div>
                                            <button type="button" wire:click="startResetPassword({{ $user->id }})" class="text-xs font-medium text-zinc-600 hover:text-zinc-900">Reset password</button>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-zinc-200 p-3">
                        <p class="text-sm text-zinc-600">No company admin yet.</p>
                        @if (! $showInviteForm)
                            <button type="button" wire:click="$set('showInviteForm', true)" class="btn-link mt-1 text-sm">Invite admin</button>
                        @endif
                    </div>
                @endif

                @if ($showInviteForm)
                    <form wire:submit="inviteAdmin({{ $viewingTenant->id }})" class="space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3">
                        <p class="text-sm font-medium text-zinc-900">Invite company admin</p>
                        <x-input wire:model="inviteForm.name" label="Name" placeholder="Jane Admin" />
                        <x-input wire:model="inviteForm.email" label="Email" type="email" placeholder="admin@acme.test" />
                        <x-input wire:model="inviteForm.password" label="Password" type="password" hint="Min. 12 characters." />
                        <div class="flex gap-2">
                            <x-button type="submit" size="sm" wire:loading.attr="disabled" wire:target="inviteAdmin">Send invite</x-button>
                            <x-button type="button" size="sm" variant="secondary" wire:click="$set('showInviteForm', false)">Cancel</x-button>
                        </div>
                    </form>
                @endif

                <div class="flex flex-wrap gap-2 border-t border-zinc-100 pt-4">
                    @if ($viewingTenant->status === 'active')
                        <x-button wire:click="enterTenant({{ $viewingTenant->id }})">Open tenant app</x-button>
                    @endif
                    <x-button wire:click="openEditTenant({{ $viewingTenant->id }})" variant="secondary">Edit</x-button>
                    @if ($viewingTenant->status === 'active')
                        <x-button wire:click="updateTenantStatus({{ $viewingTenant->id }}, 'suspended')" wire:confirm="Suspend this tenant?" variant="secondary">Suspend</x-button>
                    @else
                        <x-button wire:click="updateTenantStatus({{ $viewingTenant->id }}, 'active')" wire:confirm="Activate this tenant?">Activate</x-button>
                    @endif
                    @if ($viewingTenant->users_count === 0 && $viewingTenant->guards_count === 0)
                        <x-button wire:click="deleteTenant({{ $viewingTenant->id }})" wire:confirm="Permanently delete this tenant?" variant="danger">Delete</x-button>
                    @endif
                </div>
            </div>
        </x-drawer>
    @endif

    @if ($showForm)
        <x-drawer
            :title="$editingTenantId ? 'Edit tenant' : 'Add tenant'"
            :description="$editingTenantId ? 'Update company details.' : 'Create a company and optionally invite an admin.'"
            width="lg"
            closeMethod="closeDrawer"
        >
            <x-drawer-form wire:submit="saveTenant" :submit-label="$editingTenantId ? 'Save tenant' : 'Create tenant'">
                <x-input wire:model.live="tenantForm.name" label="Company name" placeholder="Acme Security Ltd" />
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-input wire:model="tenantForm.slug" label="Slug" placeholder="acme-security" />
                    <x-input wire:model="tenantForm.subdomain" label="Subdomain" placeholder="acme-security" />
                </div>
                <x-input wire:model="tenantForm.domain" label="Custom domain" placeholder="security.acme.com" />
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-select wire:model="tenantForm.status" label="Status">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </x-select>
                    <x-input wire:model="tenantForm.trial_ends_at" label="Trial ends" type="date" />
                </div>
                <x-select wire:model="tenantForm.plan_id" label="Subscription plan">
                    <option value="">No plan yet</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                    @endforeach
                </x-select>
                @if (! $editingTenantId)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 space-y-3 sm:col-span-2">
                        <p class="text-sm font-medium text-zinc-900">Company admin (optional)</p>
                        <x-input wire:model="tenantForm.admin_name" label="Name" placeholder="Jane Admin" />
                        <x-input wire:model="tenantForm.admin_email" label="Email" type="email" placeholder="admin@acme.test" />
                        <x-input wire:model="tenantForm.admin_password" label="Password" type="password" hint="Min. 12 characters when inviting an admin." />
                    </div>
                @endif
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
