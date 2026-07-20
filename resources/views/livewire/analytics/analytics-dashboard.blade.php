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
            <x-button wire:click="refreshSnapshot" size="sm" variant="secondary" wire:loading.attr="disabled" wire:target="refreshSnapshot">
                <span wire:loading.remove wire:target="refreshSnapshot">Refresh snapshot</span>
                <span wire:loading wire:target="refreshSnapshot">Refreshing…</span>
            </x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            <x-page-toolbar>
                <x-slot:controls>
                    <div class="date-nav">
                        <x-input wire:model.live="snapshotDate" type="date" label="Snapshot date" class="w-auto text-sm" />
                        <x-button type="button" size="sm" variant="secondary" wire:click="goToday" :disabled="$snapshotDate === today()->toDateString()">Today</x-button>
                        <x-button type="button" size="sm" wire:click="refreshSnapshot" wire:loading.attr="disabled" wire:target="refreshSnapshot">
                            <span wire:loading.remove wire:target="refreshSnapshot">Run snapshot</span>
                            <span wire:loading wire:target="refreshSnapshot">Running…</span>
                        </x-button>
                    </div>
                </x-slot:controls>
            </x-page-toolbar>

            @if($snapshot)
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Showing {{ \Carbon\Carbon::parse($snapshot->snapshot_date)->format('l, M j, Y') }}
                    · refreshed nightly or on demand
                </p>

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
                    <x-section-card title="30-day patrol completion" description="Daily completion rate across recent snapshots">
                        @if ($chartHistory->isNotEmpty())
                            <div class="chart-bars" role="img" aria-label="Patrol completion trend">
                                @foreach($chartHistory as $point)
                                    @php
                                        $rate = (float) $point->patrol_completion_rate;
                                        $height = max(8, min(100, $rate));
                                        $isSelected = \Carbon\Carbon::parse($point->snapshot_date)->toDateString() === $snapshotDate;
                                    @endphp
                                    <button
                                        type="button"
                                        wire:click="selectDate('{{ \Carbon\Carbon::parse($point->snapshot_date)->toDateString() }}')"
                                        class="chart-bar-col group"
                                        title="{{ \Carbon\Carbon::parse($point->snapshot_date)->format('M j') }}: {{ $rate }}%"
                                    >
                                        <div
                                            @class([
                                                'chart-bar',
                                                'ring-2 ring-accent-400 ring-offset-1 dark:ring-offset-zinc-900' => $isSelected,
                                                'opacity-50 group-hover:opacity-100' => ! $isSelected,
                                            ])
                                            style="height: {{ $height }}%"
                                        ></div>
                                        <span class="chart-bar-label">{{ \Carbon\Carbon::parse($point->snapshot_date)->format('d') }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <x-empty-state compact title="Not enough history" description="Run more daily snapshots to build a trend." />
                        @endif
                    </x-section-card>

                    <x-section-card title="Incidents by severity" description="Counts for the selected snapshot day" flush>
                        @php
                            $severities = $snapshot->incidents_by_severity ?? [];
                            $maxSeverity = max(1, ...(count($severities) ? array_map('intval', array_values($severities)) : [0]));
                        @endphp
                        @if (count($severities))
                            @foreach($severities as $severity => $count)
                                @php $pct = min(100, ((int) $count / $maxSeverity) * 100); @endphp
                                <div class="list-row-start" wire:key="sev-{{ $severity }}">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-sm capitalize text-zinc-700 dark:text-zinc-300">{{ $severity }}</span>
                                            <span class="text-sm font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $count }}</span>
                                        </div>
                                        <div class="chart-meter mt-1.5">
                                            <div class="chart-meter-fill" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="p-3">
                                <x-empty-state compact title="No incidents" description="No incidents logged on the snapshot date." />
                            </div>
                        @endif
                    </x-section-card>
                </div>

                <x-section-card title="Snapshot history" description="Click a row to view that day’s KPIs." class="mt-4" flush>
                    <x-data-table>
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
                                @php $rowDate = \Carbon\Carbon::parse($row->snapshot_date)->toDateString(); @endphp
                                <tr
                                    wire:key="snap-{{ $row->id }}"
                                    wire:click="selectDate('{{ $rowDate }}')"
                                    @class([
                                        'table-row-hover cursor-pointer',
                                        'bg-accent-50/70 dark:bg-accent-950/30' => $rowDate === $snapshotDate,
                                    ])
                                >
                                    <x-table.td class="font-medium tabular-nums">{{ \Carbon\Carbon::parse($row->snapshot_date)->format('M j, Y') }}</x-table.td>
                                    <x-table.td muted class="tabular-nums">{{ $row->active_guards }}</x-table.td>
                                    <x-table.td muted class="tabular-nums">{{ $row->active_sites }}</x-table.td>
                                    <x-table.td class="tabular-nums">{{ $row->patrol_completion_rate }}%</x-table.td>
                                    <x-table.td muted class="tabular-nums">{{ $row->missed_patrols }}</x-table.td>
                                    <x-table.td responsive="md" muted class="tabular-nums">{{ $row->late_shifts }}</x-table.td>
                                    <x-table.td responsive="md" muted class="tabular-nums">{{ $row->no_show_shifts }}</x-table.td>
                                    <x-table.td class="tabular-nums">₦{{ number_format($row->revenue_total, 0) }}</x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="8"><x-empty-state title="No history yet" /></x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>
            @elseif ($hasAnySnapshot)
                <x-empty-state
                    title="No snapshot for this date"
                    description="Pick another day from history, or run a snapshot for {{ \Carbon\Carbon::parse($snapshotDate)->format('M j, Y') }}."
                >
                    <x-slot:actions>
                        <x-button size="sm" wire:click="refreshSnapshot">Run snapshot</x-button>
                        @if ($history->isNotEmpty())
                            <x-button size="sm" variant="secondary" wire:click="selectDate('{{ \Carbon\Carbon::parse($history->first()->snapshot_date)->toDateString() }}')">Latest snapshot</x-button>
                        @endif
                    </x-slot:actions>
                </x-empty-state>
            @else
                <x-empty-state title="No analytics yet" description="Run a snapshot to populate KPIs and trends.">
                    <x-slot:actions>
                        <x-button wire:click="refreshSnapshot" size="sm">Run first snapshot</x-button>
                    </x-slot:actions>
                </x-empty-state>
            @endif
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
