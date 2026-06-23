<div>
    <x-page-header title="SaaS / Tenant Management" description="Branches, subscription plans, billing limits, and tenant settings." />

    <div class="grid gap-4 px-6 pb-4 md:grid-cols-3">
        <x-stat-card label="Branches" :value="$branches->count()" />
        <x-stat-card label="Plans" :value="$plans->count()" tone="info" />
        <x-stat-card label="Billing limits" :value="$limits->count()" />
    </div>

    <div class="space-y-6 p-6 pt-0">
        <div class="grid gap-4 lg:grid-cols-2">
            <x-form-card title="Add branch" description="Register a regional or local office.">
                <form wire:submit="saveBranch" class="grid gap-3 md:grid-cols-2">
                    <x-input wire:model="branchForm.name" label="Branch name" placeholder="Lagos HQ" class="md:col-span-2" />
                    <x-input wire:model="branchForm.code" label="Code" placeholder="LAG-01" />
                    <x-input wire:model="branchForm.phone" label="Phone" placeholder="+234…" />
                    <x-input wire:model="branchForm.email" label="Email" type="email" placeholder="lagos@company.com" class="md:col-span-2" />
                    <x-textarea wire:model="branchForm.address" label="Address" placeholder="Street, city…" class="md:col-span-2" rows="2" />
                    <div class="md:col-span-2">
                        <x-button type="submit">Create branch</x-button>
                    </div>
                </form>
            </x-form-card>

            <x-form-card title="Set billing limits" description="Cap guards, sites, clients, and storage per tenant.">
                <form wire:submit="saveLimit" class="grid gap-3 md:grid-cols-2">
                    <x-input wire:model="limitForm.max_guards" label="Max guards" type="number" />
                    <x-input wire:model="limitForm.max_sites" label="Max sites" type="number" />
                    <x-input wire:model="limitForm.max_clients" label="Max clients" type="number" />
                    <x-input wire:model="limitForm.storage_mb" label="Storage (MB)" type="number" />
                    <div class="md:col-span-2">
                        <x-button type="submit">Save limits</x-button>
                    </div>
                </form>
            </x-form-card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-data-table title="Branches">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Contact</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                        <tr class="table-row-hover">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $branch->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $branch->code ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $branch->email ?: $branch->phone ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-10"><x-empty-state title="No branches" description="Create your first branch above." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-data-table title="Billing limits">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Guards</th>
                        <th class="px-4 py-3">Sites</th>
                        <th class="px-4 py-3">Clients</th>
                        <th class="px-4 py-3">Storage</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($limits as $limit)
                        <tr class="table-row-hover">
                            <td class="px-4 py-3 text-slate-600">{{ $limit->max_guards }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $limit->max_sites }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $limit->max_clients }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ number_format($limit->storage_mb) }} MB</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10"><x-empty-state title="No limits set" description="Configure billing limits above." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </div>

        @if($tenants->isNotEmpty())
            <x-data-table title="All tenants">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Tenant</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tenants as $tenant)
                        <tr class="table-row-hover">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $tenant->name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $tenant->slug }}</td>
                            <td class="px-4 py-3"><x-badge :status="$tenant->status ?? 'active'" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-data-table>
        @endif
    </div>
</div>
