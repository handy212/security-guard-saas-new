<div>
    <x-page-shell
        title="Analytics"
        description="Historical KPI snapshots and operational trends."
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Analytics'],
        ]"
    >
        <x-slot:actions>
            <x-input wire:model="snapshotDate" type="date" class="w-auto text-sm" />
            <x-button wire:click="refreshSnapshot" size="sm" variant="secondary">Refresh snapshot</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>

        <x-flash-status type="success" />

        @if($snapshot)
            <p class="text-xs text-zinc-500">Latest snapshot: {{ \Carbon\Carbon::parse($snapshot->snapshot_date)->format('l, M j, Y') }} · refreshed nightly or on demand</p>

            <div class="stat-grid">
                <x-stat-card compact label="Active guards" :value="$snapshot->active_guards" icon="guards" />
                <x-stat-card compact label="Patrol completion" :value="$snapshot->patrol_completion_rate.'%'" icon="patrols" tone="success" />
                <x-stat-card compact label="Missed patrols" :value="$snapshot->missed_patrols" icon="incidents" :tone="$snapshot->missed_patrols ? 'danger' : 'success'" />
                <x-stat-card compact label="SLA coverage" :value="$snapshot->client_sla_performance.'%'" icon="check" tone="info" />
            </div>

            <div class="stat-grid">
                <x-stat-card compact label="Active sites" :value="$snapshot->active_sites" icon="sites" />
                <x-stat-card compact label="Late shifts" :value="$snapshot->late_shifts" icon="schedules" :tone="$snapshot->late_shifts ? 'warning' : 'default'" />
                <x-stat-card compact label="No-shows" :value="$snapshot->no_show_shifts" icon="guards" :tone="$snapshot->no_show_shifts ? 'warning' : 'default'" />
                <x-stat-card compact label="Revenue (day)" :value="'₦'.number_format($snapshot->revenue_total, 0)" icon="billing" />
            </div>

            <div class="page-grid-2">
                <x-section-card title="30-day patrol completion">
                    <div class="flex h-36 items-end gap-1">
                        @foreach($history->reverse() as $point)
                            <div class="flex flex-1 flex-col items-center gap-1">
                                <div class="w-full rounded-t bg-accent-600 dark:bg-accent-500" style="height: {{ max(6, (float) $point->patrol_completion_rate) }}%"></div>
                                <span class="text-[9px] text-zinc-400">{{ \Carbon\Carbon::parse($point->snapshot_date)->format('d') }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-section-card>

                <x-section-card title="Incidents by severity (snapshot day)">
                    @php $severities = $snapshot->incidents_by_severity ?? []; @endphp
                    @if (count($severities))
                        <div class="space-y-2">
                            @foreach($severities as $severity => $count)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="capitalize text-zinc-600">{{ $severity }}</span>
                                    <span class="font-semibold text-zinc-900">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-empty-state compact title="No incidents" description="No incidents logged on the snapshot date." />
                    @endif
                </x-section-card>
            </div>

            <x-data-table title="Snapshot history" class="mt-4">
                <x-table.head>
                    <tr>
                        <x-table.th>Date</x-table.th>
                        <x-table.th>Guards</x-table.th>
                        <x-table.th>Sites</x-table.th>
                        <x-table.th>Patrol %</x-table.th>
                        <x-table.th>Missed</x-table.th>
                        <x-table.th responsive="md">Late</x-table.th>
                        <x-table.th responsive="md">No-show</x-table.th>
                        <x-table.th>Revenue</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse ($history as $row)
                        <tr class="table-row-hover" wire:key="snap-{{ $row->id }}">
                            <x-table.td class="font-medium">{{ \Carbon\Carbon::parse($row->snapshot_date)->format('M j, Y') }}</x-table.td>
                            <x-table.td muted>{{ $row->active_guards }}</x-table.td>
                            <x-table.td muted>{{ $row->active_sites }}</x-table.td>
                            <x-table.td>{{ $row->patrol_completion_rate }}%</x-table.td>
                            <x-table.td muted>{{ $row->missed_patrols }}</x-table.td>
                            <x-table.td responsive="md" muted>{{ $row->late_shifts }}</x-table.td>
                            <x-table.td responsive="md" muted>{{ $row->no_show_shifts }}</x-table.td>
                            <x-table.td>₦{{ number_format($row->revenue_total, 0) }}</x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="8"><x-empty-state title="No history yet" /></x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
        @else
            <x-empty-state title="No analytics yet" description="Run a snapshot to populate KPIs and trends.">
                <x-button wire:click="refreshSnapshot" size="sm" class="mt-3">Run first snapshot</x-button>
            </x-empty-state>
        @endif
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
