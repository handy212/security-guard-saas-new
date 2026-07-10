<div>
    <x-page-shell title="Guards & Officers" description="Roster, profiles, and Know Your Guard verification.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('guards.applications')">Applications</x-button>
            <x-button variant="secondary" :href="route('guards.kyg')">KYG queue</x-button>
            <x-button wire:click="openCreate">Add guard</x-button>
        </x-slot:actions>

        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$guardStats['total']" icon="guards" wire:click="applyStatFilter('total')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'all' && $verificationFilter === 'all' && $search === ''" />
            <x-stat-card compact label="Active" :value="$guardStats['active']" icon="check" tone="success" wire:click="applyStatFilter('active')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'active'" />
            <x-stat-card compact label="Pending KYG" :value="$guardStats['pending']" icon="incidents" :tone="$guardStats['pending'] > 0 ? 'warning' : 'default'" wire:click="applyStatFilter('pending')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$verificationFilter === 'pending'" />
            <x-stat-card compact label="Inactive" :value="$guardStats['inactive']" icon="pause" wire:click="applyStatFilter('inactive')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'inactive'" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search by name, email, or ID…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']" />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="table-action">Clear filters</button>
                @endif
                <x-filter-select wire:model.live="verificationFilter">
                    <option value="all">All KYG</option>
                    <option value="verified">Verified</option>
                    <option value="pending">Pending</option>
                </x-filter-select>
                <x-filter-select wire:model.live="dutyFilter">
                    <option value="all">All duty types</option>
                    @foreach ($dutyTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-filter-select>
                <x-filter-select wire:model.live="branchFilter">
                    <option value="all">All branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </x-filter-select>
            </x-slot:controls>
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Guard</x-table.th>
                    <x-table.th responsive="md">ID</x-table.th>
                    <x-table.th responsive="lg">Type</x-table.th>
                    <x-table.th responsive="lg">Branch</x-table.th>
                    <x-table.th>KYG</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right" class="w-12"></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($guards as $guard)
                    <tr class="table-row-hover" wire:key="guard-{{ $guard->id }}">
                        <x-table.td>
                            <a href="{{ route('guards.show', $guard) }}" class="flex items-center gap-2 hover:underline">
                                @if ($guard->photo_path)
                                    <img src="{{ route('files.guard-photo', $guard) }}" alt="" class="h-8 w-8 rounded-full object-cover">
                                @else
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-100 text-[10px] font-semibold text-zinc-600">
                                        {{ strtoupper(substr($guard->first_name, 0, 1).substr($guard->last_name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="font-medium text-zinc-900">{{ $guard->full_name }}</span>
                            </a>
                        </x-table.td>
                        <x-table.td responsive="md" mono>{{ $guard->employee_number ?: '—' }}</x-table.td>
                        <x-table.td responsive="lg" muted>{{ $guard->dutyTypeLabel() }}</x-table.td>
                        <x-table.td responsive="lg" muted>{{ $guard->branch?->name ?? '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$guard->verification_status" /></x-table.td>
                        <x-table.td><x-badge :status="$guard->status" /></x-table.td>
                        <x-table.td align="right">
                            <x-row-menu>
                                <x-row-menu-item :href="route('guards.show', $guard)">View profile</x-row-menu-item>
                                <x-row-menu-item wire:click="edit({{ $guard->id }})">Edit</x-row-menu-item>
                                <x-row-menu-item wire:click="delete({{ $guard->id }})" wire:confirm="Remove this guard?" danger>Delete</x-row-menu-item>
                            </x-row-menu>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="7">
                        <x-empty-state
                            compact
                            :title="$hasActiveFilters ? 'No matching guards' : 'No guards'"
                            :description="$hasActiveFilters ? 'Try adjusting your filters.' : 'Add guards to enable scheduling and field operations.'"
                        >
                            <x-slot:actions>
                                @if (! $hasActiveFilters)
                                    <x-button size="sm" wire:click="openCreate">Add guard</x-button>
                                @endif
                            </x-slot:actions>
                        </x-empty-state>
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$guards" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingId ? 'Edit guard' : 'Add guard'" width="lg">
            <x-drawer-form wire:submit="save" :submit-label="$editingId ? 'Update guard' : 'Create guard'">
                <x-input wire:model="form.employee_number" label="Employee #" placeholder="G-001" />
                <x-select wire:model="form.status" label="Status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </x-select>
                <x-input wire:model="form.first_name" label="First name" />
                <x-input wire:model="form.last_name" label="Last name" />
                <x-input wire:model="form.phone" label="Phone" />
                <x-input wire:model="form.email" label="Email" type="email" />
                <x-input wire:model="form.license_number" label="License #" />
                <x-input wire:model="form.license_expires_at" label="License expires" type="date" />
                <x-input wire:model="form.rank" label="Rank / position" />
                <x-select wire:model="form.duty_type" label="Duty type">
                    @foreach($dutyTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
                <div>
                    <x-select wire:model="form.branch_id" label="Branch">
                        <option value="">None</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </x-select>
                    <a href="{{ route('settings.branches') }}" class="mt-1 inline-block text-xs font-medium text-accent-600 hover:underline">Manage branches</a>
                </div>
                <x-input wire:model="form.monthly_rate" label="Monthly rate" type="number" step="0.01" class="sm:col-span-2" />
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
