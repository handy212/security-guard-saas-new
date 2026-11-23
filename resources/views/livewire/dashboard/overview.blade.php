@php
    $nav = app(\App\Services\NavigationBuilder::class);
    $canOpen = fn (string $route, string $permission) => $nav->routeVisible($route, $permission);
    $canSchedule = $canOpen('schedules.index', 'schedules.manage');
    $canDispatch = $canOpen('dispatch.control-room', 'dispatch.manage');
    $canIncidents = $canOpen('incidents.index', 'incidents.manage');
    $canTrack = $canOpen('tracking.live', 'dispatch.manage');
    $canPatrol = $canOpen('patrols.index', 'patrols.manage');
    $canAttendance = $canOpen('schedules.attendance', 'attendance.manage');
@endphp

<div wire:poll.30s class="operations-home">
    <x-page-shell
        title="Operations desk"
        :description="now()->format('l, j F Y').' · '.\App\Support\TenantContext::current()?->name"
    >
        <x-slot:actions>
            @if ($canSchedule)
                <x-button size="sm" variant="secondary" :href="route('schedules.index', ['date' => today()->toDateString()])">
                    Today's roster
                </x-button>
            @endif
            @if ($canOpen('schedules.deploy', 'schedules.manage'))
                <x-button size="sm" :href="route('schedules.deploy', ['date' => today()->toDateString()])">
                    Deployment wizard
                </x-button>
            @endif
            @if ($canDispatch)
                <x-button size="sm" variant="secondary" :href="route('dispatch.control-room')">
                    Open dispatch
                </x-button>
            @endif
        </x-slot:actions>

        @php
            $sosKpi = collect($kpis)->firstWhere('key', 'sos');
            $sosCount = $canDispatch ? ($sosKpi['value'] ?? 0) : 0;
            $unassignedCount = $canDispatch ? (collect($kpis)->firstWhere('key', 'alerts')['unassigned_dispatches'] ?? 0) : 0;
            $understaffedCount = $canSchedule ? (collect($kpis)->firstWhere('key', 'shifts')['understaffed'] ?? 0) : 0;
            $rosterUrl = route('schedules.index', ['date' => today()->toDateString()]);
            $displayKpis = collect($kpis)->whereIn('key', array_keys(array_filter([
                'reports' => $canOpen('reports.daily', 'reports.approve'),
                'incidents' => $canIncidents,
                'patrols' => $canPatrol,
                'guards' => $canTrack,
                'shifts' => $canSchedule,
                'alerts' => $canDispatch,
            ])));
            $quickActions = array_filter([
                ['label' => 'Clients', 'route' => 'clients.index', 'href' => route('clients.index'), 'icon' => 'clients', 'permission' => 'clients.manage'],
                ['label' => 'Sites', 'route' => 'sites.index', 'href' => route('sites.index'), 'icon' => 'sites', 'permission' => 'sites.manage'],
                ['label' => 'Guards', 'route' => 'guards.index', 'href' => route('guards.index'), 'icon' => 'guards', 'permission' => 'guards.manage'],
                ['label' => 'Schedules', 'route' => 'schedules.index', 'href' => route('schedules.index', ['date' => today()->toDateString()]), 'icon' => 'schedules', 'permission' => 'schedules.manage'],
                ['label' => 'Dispatch', 'route' => 'dispatch.control-room', 'href' => route('dispatch.control-room'), 'icon' => 'dispatch', 'permission' => 'dispatch.manage'],
                ['label' => 'Incidents', 'route' => 'incidents.index', 'href' => route('incidents.index'), 'icon' => 'incidents', 'permission' => 'incidents.manage'],
                ['label' => 'Live tracking', 'route' => 'tracking.live', 'href' => route('tracking.live'), 'icon' => 'gps', 'permission' => 'dispatch.manage'],
                ['label' => 'Messenger', 'route' => 'messenger.index', 'href' => route('messenger.index'), 'icon' => 'messenger', 'permission' => 'dispatch.manage'],
            ], fn ($action) => $canOpen($action['route'], $action['permission']));
        @endphp

        @if ($sosCount || $unassignedCount || $understaffedCount)
            <section aria-labelledby="priority-work-heading" class="space-y-2">
                <h2 id="priority-work-heading" class="text-base font-semibold">Needs attention</h2>
                <div class="flex flex-wrap gap-2">
                    @if ($sosCount)
                        <a href="{{ route('dispatch.control-room') }}#dispatch-sos" class="flex min-h-11 items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-900 hover:bg-red-100 dark:border-red-900 dark:bg-red-950/50 dark:text-red-200">
                            {{ $sosCount }} active {{ \Illuminate\Support\Str::plural('SOS alert', $sosCount) }}
                            <span aria-hidden="true">→</span>
                        </a>
                    @endif
                    @if ($unassignedCount)
                        <a href="{{ route('dispatch.control-room', ['status' => 'unassigned']) }}" class="flex min-h-11 items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-200">
                            {{ $unassignedCount }} unassigned {{ \Illuminate\Support\Str::plural('dispatch', $unassignedCount) }}
                            <span aria-hidden="true">→</span>
                        </a>
                    @endif
                    @if ($understaffedCount)
                        <a href="{{ $rosterUrl }}" class="flex min-h-11 items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-200">
                            {{ $understaffedCount }} understaffed {{ \Illuminate\Support\Str::plural('shift', $understaffedCount) }}
                            <span aria-hidden="true">→</span>
                        </a>
                    @endif
                </div>
            </section>
        @endif

        @if ($canSchedule || $canDispatch)
        <div class="ops-section-heading">
            <div>

                <h2>Coverage &amp; response</h2>
            </div>
            <p class="ops-period">Updates every 30 seconds</p>
        </div>

        <div @class(['ops-primary-grid', 'ops-two-column' => $canSchedule && $canDispatch])>
            @if ($canSchedule)
                <section class="card-surface overflow-hidden">
                    <div class="card-header">
                        <div>
                            <h2 class="card-header-title">Today's schedule</h2>
                            <p class="card-header-meta">
                                {{ trans_choice('{0} No shifts shown|{1} :count shift shown|[2,*] :count shifts shown', $todayShifts->count()) }}
                                @if ($understaffedCount)
                                    · <span class="font-medium text-amber-600 dark:text-amber-400">{{ $understaffedCount }} understaffed</span>
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('schedules.index', ['date' => today()->toDateString()]) }}" class="page-link shrink-0">Day roster</a>
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
                            <div class="shrink-0 text-right tabular-nums">
                                <div @class([
                                    'text-xs',
                                    'font-medium text-amber-700 dark:text-amber-400' => $shift->is_understaffed,
                                    'text-zinc-500' => ! $shift->is_understaffed,
                                ])>
                                    {{ $shift->staffed_count }}/{{ $shift->required_guards }} staffed
                                </div>
                            </div>
                            <span class="hidden sm:inline-flex"><x-badge :status="$shift->status" /></span>
                        </a>
                    @empty
                        <div class="flex min-h-[3.75rem] flex-wrap items-center justify-between gap-2 px-3.5 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <p>No shifts scheduled today.</p>
                            <a href="{{ route('schedules.index', ['date' => today()->toDateString()]) }}" class="page-link">Create shift</a>
                        </div>
                    @endforelse
                </section>
@endif

                @if ($canDispatch)
                    <section class="card-surface overflow-hidden">
                    <div class="card-header">
                        <div>
                            <h2 class="card-header-title">Open dispatches</h2>
                            <p class="card-header-meta">Active control-room events</p>
                        </div>
                        <a href="{{ route('dispatch.control-room') }}" class="page-link shrink-0">Control room</a>
                    </div>

                    @forelse ($openDispatches as $dispatch)
                        <a href="{{ route('dispatch.control-room', ['q' => $dispatch->dispatch_number]) }}" class="list-row-start">
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
                            <div class="ops-event-status">
                                <span @class(['ops-priority', 'ops-priority-urgent' => in_array(\App\Support\EnumHelper::value($dispatch->priority), ['critical', 'high'], true)])>{{ ucfirst(\App\Support\EnumHelper::value($dispatch->priority)) }} priority</span>
                                <x-badge :status="$dispatch->status" />
                            </div>
                        </a>
                    @empty
                        <div class="flex min-h-[3.75rem] flex-wrap items-center justify-between gap-2 px-3.5 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <p>No open dispatches.</p>
                        </div>
                    @endforelse
                </section>
@endif
        </div>

        @endif

        @if ($displayKpis->isNotEmpty())
        <section class="ops-summary" aria-label="Operations at a glance">
            @foreach ($displayKpis as $kpi)
                <x-dashboard.kpi
                    class="ops-summary-card"
                    :label="$kpi['label']"
                    :value="$kpi['value']"
                    :hint="$kpi['hint']"
                    :tone="$kpi['tone']"
                    :href="url($kpi['href'])"
                    :icon="$kpi['key']"
                />
            @endforeach
        </section>

        @endif

        @if ($canIncidents || $canTrack || $showOnboarding)
        <div @class(['ops-secondary-grid' => $canIncidents && ($canTrack || $showOnboarding)])>
            <div class="space-y-3">
                @if ($canIncidents)
                    <section class="card-surface overflow-hidden">
                    <div class="card-header">
                        <div>
                            <h2 class="card-header-title">Recent incidents</h2>
                            <p class="card-header-meta">{{ $weekSummary['incidents'] }} this week</p>
                        </div>
                        <a href="{{ route('incidents.index', ['status' => 'open']) }}" class="page-link shrink-0">View open</a>
                    </div>

                    @forelse ($incidentsList as $incident)
                        <a href="{{ route('incidents.index', ['incident' => $incident->id]) }}#incident-detail" class="list-row-start">
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
                        <div class="flex min-h-[3.75rem] flex-wrap items-center justify-between gap-2 px-3.5 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <p>No recent incidents.</p>
                        </div>
                    @endforelse
                </section>
@endif
            </div>

            <div class="space-y-3">
                @if ($showOnboarding)
                    <x-onboarding-checklist :steps="$onboardingSteps" :progress="$onboardingProgress" />
                @endif

                @if ($canTrack)
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
                        <div class="flex min-h-[3.75rem] flex-wrap items-center justify-between gap-2 px-3.5 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <p>Nobody is clocked in.</p>
                        </div>
                    @endforelse
                </section>
@endif


            </div>
        </div>

        @endif

        @if ($canIncidents || $canPatrol || $canTrack || $canAttendance || $canOpen('passdown.index', 'patrols.manage'))
            <section class="ops-reporting" aria-labelledby="ops-reporting-title">
            <div class="ops-section-heading">
                <div>

                    <h2 id="ops-reporting-title">This week in review</h2>
                </div>
                <span class="ops-period">Last 7 days</span>
            </div>
            <div class="ops-report-grid">
                @if ($canPatrol)
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
                        @if ($patrolTotal > 0)
                            <x-dashboard.trend-chart :series="$patrolTrend" color="accent" />
                        @endif
                    </div>
                </section>
@endif
                @if ($canIncidents)
                <x-dashboard.incident-donut :breakdown="$incidentBreakdown" />
                <x-dashboard.incident-bar-chart :series="$incidentTrend" />
                @endif
            </div>
            <x-dashboard.activity-summary :summary="$activitySummary" />
        </section>

        @endif

        @if (count($quickActions))
            <section class="ops-tools">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <h2 class="section-heading">Quick actions</h2>

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

    </x-page-shell>
</div>
