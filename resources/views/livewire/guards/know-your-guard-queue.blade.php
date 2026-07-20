<div>
    <x-page-shell title="Know Your Guard" description="Review verification status across the roster.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('guards.applications')">Applications</x-button>
            <x-button :href="route('guards.index')">Roster</x-button>
        </x-slot:actions>

        <div class="stat-grid">
            <x-stat-card compact label="Awaiting review" :value="$counts['queue']" icon="guards" :tone="$counts['queue'] ? 'warning' : 'success'" wire:click="$set('statusFilter', 'queue')" class="cursor-pointer text-left" :active="$statusFilter === 'queue'" />
            <x-stat-card compact label="Verified" :value="$counts['verified']" icon="check" tone="success" wire:click="$set('statusFilter', 'verified')" class="cursor-pointer text-left" :active="$statusFilter === 'verified'" />
            <x-stat-card compact label="Suspended" :value="$counts['suspended']" icon="incidents" :tone="$counts['suspended'] ? 'danger' : 'default'" wire:click="$set('statusFilter', 'suspended')" class="cursor-pointer text-left" :active="$statusFilter === 'suspended'" />
            <x-stat-card compact label="All guards" :value="$counts['all']" icon="workforce" wire:click="$set('statusFilter', 'all')" class="cursor-pointer text-left" :active="$statusFilter === 'all'" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search guards…">
            <x-slot:tabs>
                <x-segment-control
                    field="statusFilter"
                    :active="$statusFilter"
                    :options="[
                        'queue' => 'Queue',
                        'pending' => 'Pending',
                        'unverified' => 'Unverified',
                        'verified' => 'Verified',
                        'suspended' => 'Suspended',
                        'all' => 'All',
                    ]"
                />
            </x-slot:tabs>
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Guard</x-table.th>
                    <x-table.th responsive="md">Branch</x-table.th>
                    <x-table.th responsive="lg">Duty type</x-table.th>
                    <x-table.th>Employment</x-table.th>
                    <x-table.th>KYG</x-table.th>
                    <x-table.th align="right">Action</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($guards as $guard)
                    <tr class="table-row-hover" wire:key="kyg-{{ $guard->id }}">
                        <x-table.td>
                            <div class="flex items-center gap-2.5">
                                @if ($guard->photo_path)
                                    <img src="{{ route('files.guard-photo', $guard) }}" alt="" class="h-8 w-8 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-zinc-700">
                                @else
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-accent-50 text-[10px] font-semibold text-accent-700 ring-1 ring-accent-100 dark:bg-accent-950 dark:text-accent-300 dark:ring-accent-800/50">
                                        {{ strtoupper(substr($guard->first_name, 0, 1).substr($guard->last_name, 0, 1)) }}
                                    </div>
                                @endif
                                <a href="{{ route('guards.show', $guard) }}?tab=overview" class="font-medium text-zinc-900 transition hover:text-accent-700 dark:text-zinc-100 dark:hover:text-accent-300">
                                    {{ $guard->full_name }}
                                </a>
                            </div>
                        </x-table.td>
                        <x-table.td responsive="md" muted>{{ $guard->branch?->name ?? '—' }}</x-table.td>
                        <x-table.td responsive="lg" muted>{{ $guard->dutyTypeLabel() }}</x-table.td>
                        <x-table.td><x-badge :status="$guard->status" /></x-table.td>
                        <x-table.td><x-badge :status="$guard->verification_status" /></x-table.td>
                        <x-table.td align="right">
                            <x-button size="sm" variant="secondary" :href="route('guards.show', $guard).'?tab=overview'">Review</x-button>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6">
                        <x-empty-state
                            compact
                            title="No guards match"
                            description="No guards match this Know Your Guard filter."
                        >
                            <x-slot:actions>
                                <x-button size="sm" variant="secondary" wire:click="$set('statusFilter', 'all')">Show all</x-button>
                                <x-button size="sm" variant="secondary" :href="route('guards.index')">Roster</x-button>
                            </x-slot:actions>
                        </x-empty-state>
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$guards" />
    </x-page-shell>
</div>
