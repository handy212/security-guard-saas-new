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
                        <x-table.td><span class="font-medium text-zinc-900">{{ $guard->full_name }}</span></x-table.td>
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
                        <x-empty-state title="No guards" description="No guards match this Know Your Guard filter." />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$guards" />
    </x-page-shell>
</div>
