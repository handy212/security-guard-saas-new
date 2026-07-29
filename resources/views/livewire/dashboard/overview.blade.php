<div wire:poll.30s>
    <x-page-shell
        :title="$greeting.', '.auth()->user()->name"
        :description="now()->format('l, F j').' · Operations overview'"
    >
        <x-slot:actions>
            @can('schedules.manage')
                <x-button size="sm" variant="secondary" :href="route('schedules.index', ['date' => today()->toDateString()])">
                    Today's roster
                </x-button>
            @endcan
            @can('dispatch.manage')
                <x-button size="sm" :href="route('dispatch.control-room')">
                    Open dispatch
                </x-button>
            @endcan
        </x-slot:actions>

        @php
            $sosKpi = collect($kpis)->firstWhere('key', 'sos');
            $hasUrgent = ($sosKpi['value'] ?? 0) > 0;
            $displayKpis = collect($kpis)->whereIn('key', ['reports', 'incidents', 'patrols', 'guards', 'shifts', 'alerts']);
            $quickActions = array_filter([
                ['label' => 'Clients', 'href' => route('clients.index'), 'icon' => 'clients', 'permission' => 'clients.manage'],
                ['label' => 'Sites', 'href' => route('sites.index'), 'icon' => 'sites', 'permission' => 'sites.manage'],
                ['label' => 'Guards', 'href' => route('guards.index'), 'icon' => 'guards', 'permission' => 'guards.manage'],
                ['label' => 'Schedules', 'href' => route('schedules.index', ['date' => today()->toDateString()]), 'icon' => 'schedules', 'permission' => 'schedules.manage'],
                ['label' => 'Dispatch', 'href' => route('dispatch.control-room'), 'icon' => 'dispatch', 'permission' => 'dispatch.manage'],
                ['label' => 'Incidents', 'href' => route('incidents.index'), 'icon' => 'incidents', 'permission' => 'incidents.manage'],
                ['label' => 'Live tracking', 'href' => route('tracking.live'), 'icon' => 'gps', 'permission' => 'dispatch.manage'],
                ['label' => 'Messenger', 'href' => route('messenger.index'), 'icon' => 'messenger', 'permission' => 'dispatch.manage'],
            ], fn ($action) => empty($action['permission']) || auth()->user()?->can($action['permission']));
        @endphp

        @if ($hasUrgent)
            <x-alert tone="danger" :title="$sosKpi['value'].' active SOS alert'.($sosKpi['value'] > 1 ? 's' : '')" class="animate-fade-in-up">
                Respond from dispatch immediately.
                <x-slot:action>
                    <a href="{{ route('dispatch.control-room') }}" class="btn-danger shrink-0">Open dispatch</a>
                </x-slot:action>
            </x-alert>
        @endif

        <div class="kpi-grid animate-fade-in-up">
            @foreach ($displayKpis as $kpi)
                <x-stat-card
                    compact
                    :label="$kpi['label']"
                    :value="$kpi['value']"
                    :hint="$kpi['hint']"
                    :tone="$kpi['tone']"
                    :icon="match($kpi['key']) {
                        'reports' => 'plan',
                        'incidents' => 'incidents',
                        'patrols' => 'patrols',
                        'guards' => 'guards',
                        'shifts' => 'shifts',
                        'alerts' => 'dispatch',
                        default => 'chart',
                    }"
                    :href="url($kpi['href'])"
                />
            @endforeach
        </div>

        @if (count($quickActions))
            <section class="section-block !gap-2">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <h2 class="section-heading">Quick actions</h2>
                        <p class="section-subheading">Jump into the most used modules</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($quickActions as $action)
                        <a href="{{ $action['href'] }}" class="quick-action">
                            <x-nav-icon :name="$action['icon']" class="h-3.5 w-3.5 text-zinc-500" />
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="page-dashboard">
            <div class="space-y-3">
                <section class="card-surface overflow-hidden">
                    <div class="card-header">
                        <div>
                            <h2 class="card-header-title">Today's schedule</h2>
                            <p class="card-header-meta">
                                {{ $todayShifts->count() }} shift{{ $todayShifts->count() === 1 ? '' : 's' }}
                                @if ($todayShifts->where('is_understaffed', true)->count())
                                    · <span class="font-medium text-amber-600 dark:text-amber-400">{{ $todayShifts->where('is_understaffed', true)->count() }} understaffed</span>
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('schedules.index', ['date' => today()->toDateString()]) }}" class="page-link shrink-0">View all</a>
                    </div>

                    @forelse ($todayShifts as $shift)
                        <a
                            href="{{ route('schedules.index', ['date' => $shift->starts_at->toDateString()]) }}"
                            @class([
                                'list-row',
                                'bg-amber-50/50 dark:bg-amber-950/20' => $shift->is_understaffed,
                            ])
                        >
                            <div class="w-14 shrink-0 text-center tabular-nums">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $shift->starts_at->format('H:i') }}</div>
                                <div class="text-[10px] text-zinc-400">{{ $shift->ends_at->format('H:i') }}</div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $shift->title }}</div>
                                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $shift->site?->name ?? 'No site' }}</div>
                            </div>
                            <div class="hidden shrink-0 text-right tabular-nums sm:block">
                                <div @class([
                                    'text-xs',
                                    'font-medium text-amber-700 dark:text-amber-400' => $shift->is_understaffed,
                                    'text-zinc-500' => ! $shift->is_understaffed,
                                ])>
                                    {{ $shift->staffed_count }}/{{ $shift->required_guards }} staffed
                                </div>
                            </div>
                            <x-badge :status="$shift->status" />
                        </a>
                    @empty
                        <div class="p-3">
                            <x-empty-state
                                compact
                                title="No shifts today"
                                description="Create a shift on the day roster to staff coverage."
                            >
                                <x-slot:actions>
                                    <x-button size="sm" :href="route('schedules.index', ['date' => today()->toDateString()])">Create shift</x-button>
                                    <x-button size="sm" variant="secondary" :href="route('sites.index')">View sites</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </section>

                <section class="card-surface overflow-hidden">
                    <div class="card-header">
                        <div>
                            <h2 class="card-header-title">Open dispatches</h2>
                            <p class="card-header-meta">Active control-room events</p>
                        </div>
                        <a href="{{ route('dispatch.control-room') }}" class="page-link shrink-0">View all</a>
                    </div>

                    @forelse ($openDispatches as $dispatch)
                        <a href="{{ route('dispatch.control-room') }}" class="list-row-start">
                            <div @class([
                                'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                'bg-red-500' => in_array(\App\Support\EnumHelper::value($dispatch->priority), ['critical', 'high'], true),
                                'bg-amber-500' => \App\Support\EnumHelper::value($dispatch->priority) === 'medium',
                                'bg-zinc-300 dark:bg-zinc-600' => ! in_array(\App\Support\EnumHelper::value($dispatch->priority), ['critical', 'high', 'medium'], true),
                            ])></div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $dispatch->dispatch_number ?? 'Dispatch' }}
                                    @if ($dispatch->event_type)
                                        · {{ str_replace('_', ' ', $dispatch->event_type) }}
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $dispatch->site?->name ?? $dispatch->clientAccount?->name ?? 'Unassigned site' }}
                                    · {{ $dispatch->assignedGuard?->full_name ?? 'Unassigned' }}
                                    · {{ $dispatch->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <x-badge :status="$dispatch->status" />
                        </a>
                    @empty
                        <div class="p-3">
                            <x-empty-state
                                compact
                                title="No open dispatches"
                                description="Create a dispatch when a client or site needs response."
                            >
                                <x-slot:actions>
                                    <x-button size="sm" :href="route('dispatch.control-room')">Open dispatch</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </section>

                <section class="card-surface overflow-hidden">
                    <div class="card-header">
                        <div>
                            <h2 class="card-header-title">Recent incidents</h2>
                            <p class="card-header-meta">{{ $weekSummary['incidents'] }} this week</p>
                        </div>
                        <a href="{{ route('incidents.index', ['status' => 'open']) }}" class="page-link shrink-0">View open</a>
                    </div>

                    @forelse ($incidentsList as $incident)
                        <a href="{{ route('incidents.index', ['status' => 'open']) }}" class="list-row-start">
                            <div @class([
                                'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                'bg-red-500' => in_array($incident->severity, ['critical', 'high']),
                                'bg-amber-500' => $incident->severity === 'medium',
                                'bg-zinc-300 dark:bg-zinc-600' => ! in_array($incident->severity, ['critical', 'high', 'medium']),
                            ])></div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $incident->title }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $incident->site?->name ?? 'Unknown site' }}
                                    · {{ $incident->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <x-badge :status="$incident->status" />
                        </a>
                    @empty
                        <div class="p-3">
                            <x-empty-state
                                compact
                                title="No incidents yet"
                                description="Quiet is good — open the board when something needs review."
                            >
                                <x-slot:actions>
                                    <x-button size="sm" variant="secondary" :href="route('incidents.index')">Incidents board</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </section>
            </div>

            <div class="space-y-3">
                @if ($showOnboarding)
                    <x-onboarding-checklist :steps="$onboardingSteps" :progress="$onboardingProgress" />
                @endif

                <section class="card-surface overflow-hidden">
                    <div class="card-header">
                        <div>
                            <h2 class="card-header-title">On duty now</h2>
                            <p class="card-header-meta">Guards currently clocked in</p>
                        </div>
                        <a href="{{ route('tracking.live') }}" class="page-link shrink-0">Live map</a>
                    </div>

                    @forelse ($attendance as $log)
                        <a
                            href="{{ route('tracking.live', array_filter(['guard' => $log->guard_id])) }}"
                            class="list-row"
                        >
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent-50 text-xs font-semibold text-accent-700 dark:bg-accent-950 dark:text-accent-300">
                                {{ strtoupper(substr($log->assignedGuard?->first_name ?? 'G', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $log->assignedGuard?->full_name ?? 'Guard' }}</div>
                                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $log->site?->name }} · since {{ $log->clock_in_at?->format('H:i') }}</div>
                            </div>
                        </a>
                    @empty
                        <div class="p-3">
                            <x-empty-state
                                compact
                                title="Nobody on duty"
                                description="Clock-ins from today's shifts appear here."
                            >
                                <x-slot:actions>
                                    <x-button size="sm" variant="secondary" :href="route('schedules.attendance')">Attendance</x-button>
                                    <x-button size="sm" variant="secondary" :href="route('tracking.live')">Live map</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </section>

                <x-dashboard.activity-summary :summary="$activitySummary" />

                <section class="card-surface overflow-hidden">
                    <div class="card-header">
                        <div>
                            <h2 class="card-header-title">7-day patrol activity</h2>
                            <p class="card-header-meta">Completed vs missed checkpoints</p>
                        </div>
                        <a href="{{ route('patrols.index') }}" class="page-link shrink-0">Patrols</a>
                    </div>
                    @php
                        $patrolTotal = collect($patrolTrend)->sum();
                        $patrolValues = collect($patrolTrend)->values();
                        $patrolRecent = $patrolValues->slice(-3)->sum();
                        $patrolPrior = $patrolValues->slice(-6, 3)->sum();
                        $patrolDelta = $patrolPrior > 0
                            ? round((($patrolRecent - $patrolPrior) / $patrolPrior) * 100, 1)
                            : ($patrolRecent > 0 ? 100.0 : null);
                    @endphp
                    <div class="space-y-3 p-4 pt-3">
                        <x-dashboard.chart-metric
                            :value="$patrolTotal"
                            label="Patrols"
                            :hint="$weekSummary['missed_patrols'].' missed this week'"
                            :delta="$patrolTotal > 0 ? $patrolDelta : null"
                        />
                        <x-dashboard.trend-chart :series="$patrolTrend" color="accent" />
                    </div>
                </section>
            </div>
        </div>

        <div class="page-grid-2">
            <x-dashboard.incident-donut :breakdown="$incidentBreakdown" />
            <x-dashboard.incident-bar-chart :series="$incidentTrend" />
        </div>
    </x-page-shell>
</div>
