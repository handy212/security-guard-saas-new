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
                    field="statusFilter"
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
        @php
            $currentPlanId = $viewingTenant->plan_id ?? $viewingTenant->subscription?->subscription_plan_id;
            $planName = $plans->firstWhere('id', $currentPlanId)?->name ?? 'No plan';
            $billingStatus = $viewingTenant->subscription?->status;
            $trialEndingSoon = $viewingTenant->trial_ends_at
                && $viewingTenant->trial_ends_at->isFuture()
                && $viewingTenant->trial_ends_at->lte(now()->addDays(14));
            $tenantHost = $viewingTenant->subdomain
                ? $viewingTenant->subdomain.'.'.config('tenancy.base_domain')
                : null;
        @endphp

        <x-drawer
            :title="$viewingTenant->name"
            :description="'Tenant · '.($viewingTenant->slug)"
            width="xl"
            closeMethod="closeDetail"
        >
            <div class="flex h-full min-h-0 flex-col">
                <div class="drawer-form-body space-y-5">
                    <x-flash-status type="success" />

                    <div class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50/80 p-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-sm font-bold text-zinc-700 shadow-sm ring-1 ring-zinc-200">
                                {{ strtoupper(substr($viewingTenant->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge :status="$viewingTenant->status ?? 'active'" />
                                    @if ($billingStatus)
                                        <x-badge :status="$billingStatus" />
                                    @endif
                                </div>
                                <p class="mt-1 font-mono text-xs text-zinc-500">{{ $viewingTenant->slug }}</p>
                                @if ($tenantHost)
                                    <p class="mt-0.5 truncate text-xs text-zinc-500">{{ $tenantHost }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($viewingTenant->status === 'active')
                                <x-button size="sm" wire:click="enterTenant({{ $viewingTenant->id }})">Open app</x-button>
                            @endif
                            <x-button size="sm" variant="secondary" wire:click="openEditTenant({{ $viewingTenant->id }})">Edit</x-button>
                        </div>
                    </div>

                    @if ($trialEndingSoon)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            Trial ends {{ $viewingTenant->trial_ends_at->format('M j, Y') }} ({{ $viewingTenant->trial_ends_at->diffForHumans() }})
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <x-stat-card stacked label="Users" :value="number_format($viewingTenant->users_count)" icon="users" class="h-full" />
                        <x-stat-card stacked label="Guards" :value="number_format($viewingTenant->guards_count)" icon="guards" tone="info" class="h-full" />
                        <x-stat-card stacked label="Sites" :value="number_format($viewingTenant->sites_count)" icon="sites" class="h-full" />
                        <x-stat-card stacked label="Plan" :value="$planName" icon="plan" class="h-full" />
                    </div>

                    @if ($detailUsage && $currentPlanId)
                        <x-section-card title="Plan usage" description="Guard and site limits for the assigned plan.">
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach (['guards' => 'Guards', 'sites' => 'Sites'] as $key => $label)
                                    @php
                                        $row = $detailUsage[$key];
                                        $pct = min(100, (float) ($row['pct'] ?? 0));
                                        $barTone = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-accent-600');
                                    @endphp
                                    <div>
                                        <div class="mb-1 flex items-center justify-between text-sm">
                                            <span class="font-medium text-zinc-700">{{ $label }}</span>
                                            <span class="tabular-nums text-zinc-600">{{ $row['used'] }} / {{ $row['max'] }}</span>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-zinc-100">
                                            <div class="{{ $barTone }} h-full rounded-full" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </x-section-card>
                    @endif

                    <div class="grid gap-4 lg:grid-cols-2">
                        <x-section-card title="Subscription" description="Assign or change the tenant plan.">
                            <x-select
                                wire:change="assignTenantPlan({{ $viewingTenant->id }}, $event.target.value)"
                                label="Plan"
                            >
                                <option value="0" @selected(! $currentPlanId)>No plan</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected($currentPlanId === $plan->id)>{{ $plan->name }}</option>
                                @endforeach
                            </x-select>
                            @if ($viewingTenant->subscription)
                                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-zinc-600">
                                    <span>Billing: <strong class="text-zinc-900">{{ ucfirst(str_replace('_', ' ', $billingStatus ?? 'unknown')) }}</strong></span>
                                    <a href="{{ route('saas.subscriptions', ['search' => $viewingTenant->slug]) }}" class="text-xs font-medium text-accent-600 hover:underline">View in subscriptions</a>
                                </div>
                            @endif
                        </x-section-card>

                        <x-section-card title="Access & domains" description="Identifiers used for login and tenant routing.">
                            <dl class="space-y-3 text-sm">
                                @foreach ([
                                    ['label' => 'Slug', 'value' => $viewingTenant->slug, 'mono' => true, 'copy' => $viewingTenant->slug],
                                    ['label' => 'Subdomain', 'value' => $viewingTenant->subdomain ?: '—', 'mono' => true, 'copy' => $viewingTenant->subdomain],
                                    ['label' => 'Custom domain', 'value' => $viewingTenant->domain ?: '—'],
                                    ['label' => 'Tenant URL', 'value' => $tenantHost ?: '—', 'mono' => true, 'copy' => $tenantHost],
                                ] as $row)
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="shrink-0 text-zinc-500">{{ $row['label'] }}</dt>
                                        <dd class="min-w-0 text-right">
                                            <span @class(['text-zinc-900', 'font-mono text-xs' => $row['mono'] ?? false])>{{ $row['value'] }}</span>
                                            @if (! empty($row['copy']))
                                                <button
                                                    type="button"
                                                    class="ml-2 text-[11px] font-medium text-accent-600 hover:underline"
                                                    x-data="{ copied: false }"
                                                    @click="navigator.clipboard.writeText(@js($row['copy'])); copied = true; setTimeout(() => copied = false, 1500)"
                                                    x-text="copied ? 'Copied' : 'Copy'"
                                                ></button>
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        </x-section-card>
                    </div>

                    <x-section-card title="Timeline">
                        <dl class="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-zinc-500">Created</dt>
                                <dd class="font-medium text-zinc-900">{{ $viewingTenant->created_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500">Trial ends</dt>
                                <dd class="font-medium text-zinc-900">{{ $viewingTenant->trial_ends_at?->format('M j, Y') ?? '—' }}</dd>
                            </div>
                            @if ($viewingTenant->subscription?->starts_at)
                                <div>
                                    <dt class="text-zinc-500">Subscription started</dt>
                                    <dd class="font-medium text-zinc-900">{{ $viewingTenant->subscription->starts_at->format('M j, Y') }}</dd>
                                </div>
                            @endif
                            @if ($viewingTenant->subscription?->ends_at)
                                <div>
                                    <dt class="text-zinc-500">Renews / ends</dt>
                                    <dd class="font-medium text-zinc-900">{{ $viewingTenant->subscription->ends_at->format('M j, Y') }}</dd>
                                </div>
                            @endif
                        </dl>
                    </x-section-card>

                    <x-section-card title="Company admins" description="Users with access to this tenant account.">
                        @if ($viewingTenant->users->isNotEmpty())
                            <ul class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 bg-white">
                                @foreach ($viewingTenant->users as $user)
                                    <li class="px-3 py-3" wire:key="tenant-user-{{ $user->id }}">
                                        @if ($resettingUserId === $user->id)
                                            <form wire:submit="resetAdminPassword({{ $viewingTenant->id }})" class="space-y-3">
                                                <div>
                                                    <p class="text-sm font-medium text-zinc-900">Reset password</p>
                                                    <p class="text-xs text-zinc-500">{{ $user->name }} · {{ $user->email }}</p>
                                                </div>
                                                <x-input wire:model="resetPassword" label="New password" type="password" hint="Min. 12 characters." />
                                                <div class="flex gap-2">
                                                    <x-button type="submit" size="sm" wire:loading.attr="disabled" wire:target="resetAdminPassword">Save</x-button>
                                                    <x-button type="button" size="sm" variant="secondary" wire:click="cancelResetPassword">Cancel</x-button>
                                                </div>
                                            </form>
                                        @else
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="font-medium text-zinc-900">{{ $user->name }}</span>
                                                        <x-badge :status="$user->status ?? 'active'" />
                                                    </div>
                                                    <p class="truncate text-xs text-zinc-500">{{ $user->email }}</p>
                                                </div>
                                                <button type="button" wire:click="startResetPassword({{ $user->id }})" class="shrink-0 text-xs font-medium text-zinc-600 hover:text-zinc-900">Reset password</button>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <x-empty-state compact title="No admins yet" description="Invite a company admin to manage this tenant." />
                        @endif

                        @if (! $showInviteForm && $viewingTenant->users->isEmpty())
                            <x-button type="button" size="sm" variant="secondary" class="mt-3" wire:click="$set('showInviteForm', true)">Invite admin</x-button>
                        @endif

                        @if ($showInviteForm)
                            <form wire:submit="inviteAdmin({{ $viewingTenant->id }})" class="mt-4 space-y-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                                <p class="text-sm font-semibold text-zinc-900">Invite company admin</p>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <x-input wire:model="inviteForm.name" label="Name" placeholder="Jane Admin" />
                                    <x-input wire:model="inviteForm.email" label="Email" type="email" placeholder="admin@acme.test" />
                                </div>
                                <x-input wire:model="inviteForm.password" label="Password" type="password" hint="Min. 12 characters." />
                                <div class="flex gap-2">
                                    <x-button type="submit" size="sm" wire:loading.attr="disabled" wire:target="inviteAdmin">Send invite</x-button>
                                    <x-button type="button" size="sm" variant="secondary" wire:click="$set('showInviteForm', false)">Cancel</x-button>
                                </div>
                            </form>
                        @elseif ($viewingTenant->users->isNotEmpty())
                            <x-button type="button" size="sm" variant="secondary" class="mt-3" wire:click="$set('showInviteForm', true)">Invite another admin</x-button>
                        @endif
                    </x-section-card>
                </div>

                <div class="drawer-form-footer">
                    @if ($viewingTenant->status === 'active')
                        <x-button wire:click="updateTenantStatus({{ $viewingTenant->id }}, 'suspended')" wire:confirm="Suspend this tenant?" variant="secondary">Suspend</x-button>
                    @else
                        <x-button wire:click="updateTenantStatus({{ $viewingTenant->id }}, 'active')" wire:confirm="Activate this tenant?">Activate</x-button>
                    @endif
                    @if ($viewingTenant->users_count === 0 && $viewingTenant->guards_count === 0)
                        <x-button wire:click="deleteTenant({{ $viewingTenant->id }})" wire:confirm="Permanently delete this tenant?" variant="danger">Delete tenant</x-button>
                    @endif
                    <x-button type="button" variant="secondary" class="ml-auto" wire:click="closeDetail">Close</x-button>
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
