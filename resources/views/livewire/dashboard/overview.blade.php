<div>
    <x-page-shell
        :title="$greeting.', '.auth()->user()->name"
        :description="now()->format('l, F j').' · Operations overview'"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('schedules.index')">Schedules</x-button>
            <x-button :href="route('incidents.index')">Report incident</x-button>
        </x-slot:actions>

        @php
            $sosKpi = collect($kpis)->firstWhere('key', 'sos');
            $hasUrgent = ($sosKpi['value'] ?? 0) > 0;
            $displayKpis = collect($kpis)->whereIn('key', ['reports', 'incidents', 'patrols', 'guards', 'shifts', 'alerts']);
        @endphp

        @if ($hasUrgent)
            <div class="flex items-center justify-between gap-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex h-3 w-3 rounded-full bg-red-500"></span>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-red-900">{{ $sosKpi['value'] }} active SOS alert{{ $sosKpi['value'] > 1 ? 's' : '' }}</p>
                        <p class="text-xs text-red-700">Open dispatch to respond immediately.</p>
                    </div>
                </div>
                <a href="{{ route('dispatch.control-room') }}" class="btn-danger shrink-0">Open dispatch</a>
            </div>
        @endif

        <div class="kpi-grid">
            @foreach ($displayKpis as $kpi)
                <x-stat-card
                    stacked
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
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $todayShifts->count() }} shift{{ $todayShifts->count() === 1 ? '' : 's' }} scheduled</p>
                        </div>
                        <a href="{{ route('schedules.index') }}" class="text-xs font-medium text-accent-600 hover:text-accent-700">View all</a>
                    </div>

                    @forelse ($todayShifts as $shift)
                        <div class="flex items-center gap-4 border-t border-zinc-100 px-4 py-3 first:border-t-0 dark:border-zinc-800">
                            <div class="w-14 shrink-0 text-center">
                                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $shift->starts_at->format('H:i') }}</div>
                                <div class="text-[10px] text-zinc-400">{{ $shift->ends_at->format('H:i') }}</div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $shift->title }}</div>
                                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $shift->site?->name ?? 'No site' }}</div>
                            </div>
                            <div class="hidden shrink-0 text-right sm:block">
                                <div class="text-xs text-zinc-500">
                                    {{ $shift->assignments->count() }}/{{ $shift->required_guards }} staffed
                                </div>
                            </div>
                            <x-badge :status="$shift->status" />
                        </div>
                    @empty
                        <div class="px-4 py-10 text-center">
                            <p class="text-sm text-zinc-500">No shifts scheduled for today.</p>
                            <a href="{{ route('schedules.index') }}" class="mt-2 inline-block text-sm font-medium text-accent-600 hover:underline">Create a shift</a>
                        </div>
                    @endforelse
                </section>

                <section class="card-surface overflow-hidden">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Recent incidents</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $weekSummary['incidents'] }} this week</p>
                        </div>
                        <a href="{{ route('incidents.index') }}" class="text-xs font-medium text-accent-600 hover:text-accent-700">View all</a>
                    </div>

                    @forelse ($incidentsList as $incident)
                        <a href="{{ route('incidents.index') }}" class="flex items-start gap-3 border-t border-zinc-100 px-4 py-3 transition first:border-t-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60">
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
                        <div class="px-4 py-10 text-center">
                            <p class="text-sm text-zinc-500">No incidents logged yet.</p>
                            <p class="mt-1 text-xs text-zinc-400">That's a good sign.</p>
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
                    <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">On duty now</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Guards currently clocked in</p>
                    </div>

                    @forelse ($attendance as $log)
                        <div class="flex items-center gap-3 border-t border-zinc-100 px-4 py-3 first:border-t-0 dark:border-zinc-800">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-accent-50 text-xs font-semibold text-accent-700">
                                {{ strtoupper(substr($log->assignedGuard?->first_name ?? 'G', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $log->assignedGuard?->full_name ?? 'Guard' }}</div>
                                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $log->site?->name }} · since {{ $log->clock_in_at?->format('H:i') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center">
                            <p class="text-sm text-zinc-500">Nobody clocked in.</p>
                            <a href="{{ route('schedules.attendance') }}" class="mt-2 inline-block text-xs font-medium text-accent-600 hover:underline">Attendance</a>
                        </div>
                    @endforelse
                </section>

                <section class="card-surface p-4">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Quick actions</h2>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <a href="{{ route('guards.index') }}" class="rounded-lg border border-zinc-200 px-3 py-2.5 text-center text-xs font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">Guards</a>
                        <a href="{{ route('patrols.index') }}" class="rounded-lg border border-zinc-200 px-3 py-2.5 text-center text-xs font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">Patrols</a>
                        <a href="{{ route('dispatch.control-room') }}" class="rounded-lg border border-zinc-200 px-3 py-2.5 text-center text-xs font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">Dispatch</a>
                        <a href="{{ route('guards.kyg') }}" class="rounded-lg border border-zinc-200 px-3 py-2.5 text-center text-xs font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">Know Your Guard</a>
                    </div>
                </section>

                <section class="card-surface p-4">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">7-day patrol activity</h2>
                    <p class="mb-4 text-xs text-zinc-500 dark:text-zinc-400">{{ $weekSummary['patrols'] }} patrols · {{ $weekSummary['missed_patrols'] }} missed</p>
                    <x-dashboard.trend-chart :series="$patrolTrend" color="accent" />
                </section>
            </div>
        </div>
    </x-page-shell>
</div>
