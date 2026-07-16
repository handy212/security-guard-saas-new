<div wire:poll.30s>
    <x-page-shell
        :title="$greeting.', '.auth()->user()->name"
        :description="now()->format('l, F j').' · Operations overview'"
    >
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
            $attentionTone = [
                'danger' => 'border-red-200 bg-red-50 text-red-900 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-100',
                'warning' => 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100',
                'info' => 'border-accent-200 bg-accent-50 text-accent-900 dark:border-accent-800/50 dark:bg-accent-950/40 dark:text-accent-100',
            ];
        @endphp

        @if ($hasUrgent)
            <div class="flex items-center justify-between gap-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900/50 dark:bg-red-950/40">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex h-3 w-3 rounded-full bg-red-500"></span>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-red-900 dark:text-red-100">{{ $sosKpi['value'] }} active SOS alert{{ $sosKpi['value'] > 1 ? 's' : '' }}</p>
                        <p class="text-xs text-red-700 dark:text-red-300">Open dispatch to respond immediately.</p>
                    </div>
                </div>
                <a href="{{ route('dispatch.control-room') }}" class="btn-danger shrink-0">Open dispatch</a>
            </div>
        @endif

        @if (count($attentionItems))
            <section class="card-surface overflow-hidden">
                <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Needs attention</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ count($attentionItems) }} item{{ count($attentionItems) === 1 ? '' : 's' }} requiring action</p>
                    </div>
                </div>
                <div class="grid gap-2 p-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($attentionItems as $item)
                        <a
                            href="{{ $item['href'] }}"
                            @class([
                                'flex items-start justify-between gap-3 rounded-lg border px-3 py-2.5 transition hover:opacity-90',
                                $attentionTone[$item['tone']] ?? $attentionTone['info'],
                            ])
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-semibold">{{ $item['label'] }}</p>
                                <p class="text-xs opacity-80">{{ $item['detail'] }}</p>
                            </div>
                            <span class="shrink-0 text-xs font-medium opacity-70">Open →</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="kpi-grid">
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

        <section class="section-block !gap-2">
            <div>
                <h2 class="section-heading">Quick actions</h2>
                <p class="section-subheading">Clients → Sites → Guards → Schedule → Dispatch</p>
            </div>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($quickActions as $action)
                    <a href="{{ $action['href'] }}" class="inline-flex items-center gap-2 rounded-md border border-zinc-200/90 bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-700 transition hover:border-accent-300 hover:bg-accent-50/50 hover:text-accent-800 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800/60">
                        <x-nav-icon :name="$action['icon']" class="h-3.5 w-3.5 text-zinc-500" />
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </section>

        <div class="page-grid-2">
            <x-dashboard.incident-donut :breakdown="$incidentBreakdown" />
            <x-dashboard.incident-bar-chart :series="$incidentTrend" />
        </div>

        <div class="page-dashboard">
            <div class="space-y-4">
                <section class="card-surface overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Today's schedule</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $todayShifts->count() }} shift{{ $todayShifts->count() === 1 ? '' : 's' }}
                                @if ($todayShifts->where('is_understaffed', true)->count())
                                    · <span class="text-amber-600 dark:text-amber-400">{{ $todayShifts->where('is_understaffed', true)->count() }} understaffed</span>
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('schedules.index', ['date' => today()->toDateString()]) }}" class="text-xs font-medium text-accent-600 hover:text-accent-700">View all</a>
                    </div>

                    @forelse ($todayShifts as $shift)
                        <a
                            href="{{ route('schedules.index', ['date' => $shift->starts_at->toDateString()]) }}"
                            @class([
                                'flex items-center gap-3 border-t border-zinc-100 px-4 py-2.5 transition first:border-t-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60',
                                'bg-amber-50/60 dark:bg-amber-950/20' => $shift->is_understaffed,
                            ])
                        >
                            <div class="w-14 shrink-0 text-center">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $shift->starts_at->format('H:i') }}</div>
                                <div class="text-[10px] text-zinc-400">{{ $shift->ends_at->format('H:i') }}</div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $shift->title }}</div>
                                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $shift->site?->name ?? 'No site' }}</div>
                            </div>
                            <div class="hidden shrink-0 text-right sm:block">
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
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Open dispatches</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Active control-room events</p>
                        </div>
                        <a href="{{ route('dispatch.control-room') }}" class="text-xs font-medium text-accent-600 hover:text-accent-700">View all</a>
                    </div>

                    @forelse ($openDispatches as $dispatch)
                        <a href="{{ route('dispatch.control-room') }}" class="flex items-start gap-3 border-t border-zinc-100 px-4 py-2.5 transition first:border-t-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60">
                            <div @class([
                                'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                'bg-red-500' => in_array(\App\Support\EnumHelper::value($dispatch->priority), ['critical', 'high'], true),
                                'bg-amber-500' => \App\Support\EnumHelper::value($dispatch->priority) === 'medium',
                                'bg-zinc-300' => ! in_array(\App\Support\EnumHelper::value($dispatch->priority), ['critical', 'high', 'medium'], true),
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
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Recent incidents</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $weekSummary['incidents'] }} this week</p>
                        </div>
                        <a href="{{ route('incidents.index', ['status' => 'open']) }}" class="text-xs font-medium text-accent-600 hover:text-accent-700">View open</a>
                    </div>

                    @forelse ($incidentsList as $incident)
                        <a href="{{ route('incidents.index', ['status' => 'open']) }}" class="flex items-start gap-3 border-t border-zinc-100 px-4 py-2.5 transition first:border-t-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60">
                            <div @class([
                                'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                'bg-red-500' => in_array($incident->severity, ['critical', 'high']),
                                'bg-amber-500' => $incident->severity === 'medium',
                                'bg-zinc-300' => ! in_array($incident->severity, ['critical', 'high', 'medium']),
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

            <div class="space-y-4">
                @if ($showOnboarding)
                    <x-onboarding-checklist :steps="$onboardingSteps" :progress="$onboardingProgress" />
                @endif

                <x-dashboard.activity-summary :summary="$activitySummary" />

                <section class="card-surface overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">On duty now</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Guards currently clocked in</p>
                        </div>
                        <a href="{{ route('tracking.live') }}" class="text-xs font-medium text-accent-600 hover:text-accent-700">Live map</a>
                    </div>

                    @forelse ($attendance as $log)
                        <a
                            href="{{ route('tracking.live', array_filter(['guard' => $log->guard_id])) }}"
                            class="flex items-center gap-3 border-t border-zinc-100 px-4 py-2.5 transition first:border-t-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60"
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

                <section class="card-surface p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">7-day patrol activity</h2>
                            <p class="mb-4 text-xs text-zinc-500 dark:text-zinc-400">{{ $weekSummary['patrols'] }} patrols · {{ $weekSummary['missed_patrols'] }} missed</p>
                        </div>
                        <a href="{{ route('patrols.index') }}" class="text-xs font-medium text-accent-600 hover:text-accent-700">Patrols</a>
                    </div>
                    <x-dashboard.trend-chart :series="$patrolTrend" color="accent" />
                </section>
            </div>
        </div>
    </x-page-shell>
</div>
