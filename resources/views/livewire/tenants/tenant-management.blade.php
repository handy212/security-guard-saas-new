<div>
    <x-page-shell title="Tenants" description="Onboard and manage security companies.">
        <x-slot:actions>
            <x-button wire:click="exportTenants" variant="secondary">Export CSV</x-button>
            <x-button wire:click="openCreateTenant">Add tenant</x-button>
        </x-slot:actions>

        <div class="grid grid-cols-4 gap-2">
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
                    <button type="button" wire:click="clearFilters" class="text-xs font-medium text-zinc-500 hover:text-zinc-800">
                        Clear filters
                    </button>
                @endif
                <select wire:model.live="planFilter" class="form-input w-auto min-w-[8.5rem] text-sm">
                    <option value="all">All plans</option>
                    <option value="none">No plan</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="sortBy" class="form-input w-auto min-w-[8.5rem] text-sm">
                    <option value="name">Sort: Name</option>
                    <option value="created">Sort: Newest</option>
                    <option value="users">Sort: Users</option>
                </select>
            </x-slot:controls>
        </x-page-toolbar>

        <x-data-table>
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Company</th>
                    <th class="hidden px-3 py-2 md:table-cell">Subdomain</th>
                    <th class="hidden px-3 py-2 lg:table-cell">Users</th>
                    <th class="hidden px-3 py-2 lg:table-cell">Guards</th>
                    <th class="hidden px-3 py-2 xl:table-cell">Plan</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right w-12"></th>
                </tr>
            </thead>
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
                        <td class="px-3 py-2">
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
                        </td>
                        <td class="hidden px-3 py-2 text-zinc-600 md:table-cell">{{ $tenant->subdomain ?: '—' }}</td>
                        <td class="hidden px-3 py-2 text-zinc-600 lg:table-cell">{{ number_format($tenant->users_count) }}</td>
                        <td class="hidden px-3 py-2 text-zinc-600 lg:table-cell">{{ number_format($tenant->guards_count) }}</td>
                        <td class="hidden px-3 py-2 text-zinc-600 xl:table-cell">{{ $planName ?? '—' }}</td>
                        <td class="px-3 py-2"><x-badge :status="$tenant->status ?? 'active'" /></td>
                        <td class="px-3 py-2 text-right" wire:click.stop>
                            <div x-data="{ open: false }" class="relative inline-block text-left">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="rounded-md p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800"
                                    aria-label="Tenant actions"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4z"/></svg>
                                </button>
                                <div
                                    x-show="open"
                                    x-cloak
                                    @click.outside="open = false"
                                    class="absolute right-0 z-10 mt-1 w-40 origin-top-right rounded-lg border border-zinc-200 bg-white py-1 shadow-lg"
                                >
                                    <button type="button" wire:click="openViewTenant({{ $tenant->id }})" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-50">View details</button>
                                    @if (($tenant->status ?? 'active') === 'active')
                                        <button type="button" wire:click="enterTenant({{ $tenant->id }})" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-50">Open tenant app</button>
                                    @endif
                                    <button type="button" wire:click="openEditTenant({{ $tenant->id }})" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-50">Edit</button>
                                    @if (($tenant->status ?? 'active') === 'active')
                                        <button type="button" wire:click="updateTenantStatus({{ $tenant->id }}, 'suspended')" wire:confirm="Suspend this tenant?" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-red-600 hover:bg-red-50">Suspend</button>
                                    @else
                                        <button type="button" wire:click="updateTenantStatus({{ $tenant->id }}, 'active')" wire:confirm="Activate this tenant?" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-50">Activate</button>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-8">
                            <x-empty-state
                                :title="$hasActiveFilters ? 'No matching tenants' : 'No tenants yet'"
                                :description="$hasActiveFilters ? 'Try adjusting your filters.' : 'Add your first security company.'"
                            />
                            @if (! $hasActiveFilters)
                                <div class="mt-3 text-center">
                                    <x-button wire:click="openCreateTenant" size="sm">Add tenant</x-button>
                                </div>
                            @endif
                        </td>
                    </tr>
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
                    <button
                        type="button"
                        x-data="{ copied: false }"
                        @click="navigator.clipboard.writeText(@js($viewingTenant->slug)); copied = true; setTimeout(() => copied = false, 1500)"
                        class="btn-secondary text-xs"
                        x-text="copied ? 'Copied!' : 'Copy slug'"
                    ></button>
                    @if ($viewingTenant->subdomain)
                        <button
                            type="button"
                            x-data="{ copied: false }"
                            @click="navigator.clipboard.writeText(@js($viewingTenant->subdomain)); copied = true; setTimeout(() => copied = false, 1500)"
                            class="btn-secondary text-xs"
                            x-text="copied ? 'Copied!' : 'Copy subdomain'"
                        ></button>
                    @endif
                    @if ($viewingTenant->subscription)
                        <a href="{{ route('saas.subscriptions', ['search' => $viewingTenant->slug]) }}" class="btn-secondary text-xs">View subscription</a>
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
            <form wire:submit="saveTenant" class="space-y-4">
                <x-input wire:model.live="tenantForm.name" label="Company name" placeholder="Acme Security Ltd" />
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-input wire:model="tenantForm.slug" label="Slug" placeholder="acme-security" />
                    <x-input wire:model="tenantForm.subdomain" label="Subdomain" placeholder="acme-security" />
                </div>
                <x-input wire:model="tenantForm.domain" label="Custom domain" placeholder="security.acme.com" />
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700">Status</label>
                        <select wire:model="tenantForm.status" class="form-input w-full">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <x-input wire:model="tenantForm.trial_ends_at" label="Trial ends" type="date" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700">Subscription plan</label>
                    <select wire:model="tenantForm.plan_id" class="form-input w-full">
                        <option value="">No plan yet</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if (! $editingTenantId)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 space-y-3">
                        <p class="text-sm font-medium text-zinc-900">Company admin (optional)</p>
                        <x-input wire:model="tenantForm.admin_name" label="Name" placeholder="Jane Admin" />
                        <x-input wire:model="tenantForm.admin_email" label="Email" type="email" placeholder="admin@acme.test" />
                        <x-input wire:model="tenantForm.admin_password" label="Password" type="password" hint="Min. 12 characters when inviting an admin." />
                    </div>
                @endif
                <div class="flex gap-2 border-t border-zinc-100 pt-4">
                    <x-button type="submit" wire:loading.attr="disabled" wire:target="saveTenant">
                        <span wire:loading.remove wire:target="saveTenant">{{ $editingTenantId ? 'Save' : 'Create' }}</span>
                        <span wire:loading wire:target="saveTenant">Saving…</span>
                    </x-button>
                    <x-button type="button" variant="secondary" wire:click="closeDrawer">Cancel</x-button>
                </div>
            </form>
        </x-drawer>
    @endif
</div>
