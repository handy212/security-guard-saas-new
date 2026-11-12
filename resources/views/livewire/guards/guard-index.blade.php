<div class="directory-page guards-page">
    <x-page-shell
        title="Guards & Officers"
        description="Roster, profiles, and Know Your Guard verification."
        :breadcrumbs="[['label' => 'Guards']]"
    >
        <x-slot:actions>
            @if ($activeTab === 'directory')
            @if ($verifiedIdCardCount > 0)
                <details class="directory-export">
                    <summary class="btn-secondary">Export ID cards</summary>
                    <div class="directory-export-menu">
                        <a href="{{ route('guards.id-cards.bulk', ['format' => 'pdf']) }}">Download ID cards (PDF)</a>
                        <a href="{{ route('guards.id-cards.bulk', ['format' => 'png']) }}">Download ID cards (PNG)</a>
                    </div>
                </details>
            @endif
            <x-button wire:click="openCreate">Add guard</x-button>
            @endif
        </x-slot:actions>

        <div class="guards-directory-nav">
        <nav class="directory-nav" aria-label="Guard workspace">
            @foreach (['directory' => 'Directory', 'applications' => 'Applications', 'verification' => 'Verification queue'] as $tab => $label)
                <button type="button" wire:click="setTab('{{ $tab }}')" @if ($activeTab === $tab) aria-current="page" @endif>{{ $label }}</button>
            @endforeach
        </nav>
            @if ($activeTab === 'directory' && $guardStats['pending'])
                <button type="button" wire:click="applyStatFilter('pending')" class="directory-attention">
                    <span class="font-semibold tabular-nums">{{ $guardStats['pending'] }}</span>
                    <span>Pending verification <span aria-hidden="true">→</span></span>
                </button>
            @endif
        </div>

        @if ($activeTab === 'applications')
            <livewire:guards.guard-application-queue :embedded="true" key="guard-applications" />
        @elseif ($activeTab === 'verification')
            <livewire:guards.know-your-guard-queue :embedded="true" key="guard-verification" />
        @else
        <section class="directory-panel" aria-label="Guard directory">
        <x-page-toolbar class="guards-toolbar" search="search" searchPlaceholder="Search by name, email, or ID…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All ('.$guardStats['total'].')', 'active' => 'Active ('.$guardStats['active'].')', 'inactive' => 'Inactive ('.$guardStats['inactive'].')']" />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="table-action">Clear filters</button>
                @endif
                <x-filter-select aria-label="Verification status" wire:model.live="verificationFilter">
                    <option value="all">Verification: all</option>
                    <option value="verified">Verified</option>
                    <option value="pending">Pending</option>
                </x-filter-select>
                <x-filter-select aria-label="Duty type" wire:model.live="dutyFilter">
                    <option value="all">All duty types</option>
                    @foreach ($dutyTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-filter-select>
                <x-filter-select aria-label="Branch" wire:model.live="branchFilter">
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
                    <x-table.th responsive="lg">Branch</x-table.th>
                    <x-table.th>Verification</x-table.th>
                    <x-table.th responsive="md">Status</x-table.th>
                    <x-table.th align="right" class="w-12"><span class="sr-only">Actions</span></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($guards as $guard)
                    <tr class="table-row-hover" wire:key="guard-{{ $guard->id }}">
                        <x-table.td>
                            <a href="{{ route('guards.show', $guard) }}" class="group flex items-center gap-2.5">
                                @if ($guard->photo_path)
                                    <img src="{{ route('files.guard-photo', $guard) }}" alt="" class="h-8 w-8 shrink-0 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-zinc-700">
                                @else
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent-50 text-[10px] font-semibold text-accent-700 ring-1 ring-accent-100 dark:bg-accent-950 dark:text-accent-300 dark:ring-accent-800/50">
                                        {{ strtoupper(substr($guard->first_name, 0, 1).substr($guard->last_name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="min-w-0">
                                    <span class="block font-medium text-zinc-900 group-hover:text-accent-700 dark:text-zinc-100 dark:group-hover:text-accent-300">{{ $guard->full_name }}</span>
                                    <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $guard->dutyTypeLabel() }}<span class="md:hidden"> · {{ $guard->employee_number ?: 'No ID' }} · {{ ucfirst(\App\Support\EnumHelper::value($guard->status)) }}</span></span>
                                </span>
                            </a>
                        </x-table.td>
                        <x-table.td responsive="md" mono>{{ $guard->employee_number ?: '—' }}</x-table.td>
                        <x-table.td responsive="lg" muted>{{ $guard->branch?->name ?? '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$guard->verification_status" /></x-table.td>
                        <x-table.td responsive="md"><x-badge :status="$guard->status" /></x-table.td>
                        <x-table.td align="right">
                            <x-row-menu>
                                <x-row-menu-item :href="route('guards.show', $guard)">View profile</x-row-menu-item>
                                <x-row-menu-item :href="route('guards.show', $guard).'?tab=profile'">Edit profile</x-row-menu-item>
                                <x-row-menu-item wire:click="delete({{ $guard->id }})" wire:confirm="Remove this guard?" danger>Delete</x-row-menu-item>
                            </x-row-menu>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6">
                        <x-empty-state
                            compact
                            :title="$hasActiveFilters ? 'No matching guards' : 'No guards yet'"
                            :description="$hasActiveFilters ? 'Try adjusting your filters.' : 'Add guards after clients and sites, then schedule coverage.'"
                        >
                            <x-slot:actions>
                                @if (! $hasActiveFilters)
                                    <x-button size="sm" wire:click="openCreate">Add guard</x-button>
                                    <x-button size="sm" variant="secondary" :href="route('sites.index')">View sites</x-button>
                                @endif
                            </x-slot:actions>
                        </x-empty-state>
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$guards" per-page="perPage" />
        </section>
        @endif
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingId ? 'Edit guard' : 'Add guard'"
            :description="$editingId ? 'Update roster details. Full profile edits live on the guard page.' : 'Create a roster record. You can complete KYG and profile details next.'"
            width="lg"
        >
            <x-drawer-form wire:submit="save" :submit-label="$editingId ? 'Update guard' : 'Create guard'">
                <x-form-section title="Identity">
                    <x-input wire:model.live="form.first_name" label="First name" required autocomplete="given-name" />
                    <x-input wire:model.live="form.last_name" label="Last name" required autocomplete="family-name" />
                    <x-input wire:model.live="form.employee_number" label="Employee #" placeholder="G-001" hint="Optional unique ID" />
                    <x-select wire:model.live="form.status" label="Status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </x-select>
                </x-form-section>

                <x-form-section title="Contact">
                    <x-input wire:model.live="form.phone" label="Phone" />
                    <x-input wire:model.live="form.email" label="Email" type="email" />
                </x-form-section>

                <x-form-section title="Assignment">
                    <x-select wire:model.live="form.duty_type" label="Duty type">
                        @foreach($dutyTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model.live="form.rank" label="Rank / position" />
                    <div class="sm:col-span-2">
                        <x-select wire:model.live="form.branch_id" label="Branch">
                            <option value="">None</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </x-select>
                        <a href="{{ route('settings.branches') }}" class="mt-1.5 inline-block text-xs font-medium text-accent-700 hover:underline">Manage branches</a>
                    </div>
                </x-form-section>

                <x-form-section title="License & rate" description="Optional now — required for KYG verification later.">
                    <x-input wire:model.live="form.license_number" label="License #" />
                    <x-input wire:model.live="form.license_expires_at" label="License expires" type="date" />
                    <x-input wire:model.live="form.monthly_rate" label="Monthly rate" type="number" step="0.01" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
