<div>
    <x-page-shell
        :title="$greeting.', '.auth()->user()->name"
        :description="now()->format('l, F j').' · Operations overview'"
    >
        <x-slot:actions>
            <a href="{{ route('schedules.index') }}" class="btn-secondary">Schedules</a>
            <a href="{{ route('incidents.index') }}" class="btn-primary">Report incident</a>
        </x-slot:actions>

        @php
            $sosKpi = collect($kpis)->firstWhere('key', 'sos');
            $hasUrgent = ($sosKpi['value'] ?? 0) > 0;
            $onDuty = collect($kpis)->firstWhere('key', 'guards');
            $shifts = collect($kpis)->firstWhere('key', 'shifts');
            $incidents = collect($kpis)->firstWhere('key', 'incidents');
            $patrols = collect($kpis)->firstWhere('key', 'patrols');
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

        <x-stat-grid>
            <x-stat-card compact :label="$onDuty['label']" :value="$onDuty['value']" :hint="$onDuty['hint']" icon="guards" :href="url($onDuty['href'])" />
            <x-stat-card compact :label="$shifts['label']" :value="$shifts['value']" :hint="$shifts['hint']" icon="shifts" tone="info" :href="url($shifts['href'])" />
            <x-stat-card compact :label="$incidents['label']" :value="$incidents['value']" :hint="$incidents['hint']" :tone="$incidents['tone']" icon="incidents" :href="url($incidents['href'])" />
            <x-stat-card compact :label="$patrols['label']" :value="$patrols['value']" :hint="$patrols['hint']" :tone="$patrols['tone']" icon="chart" :href="url($patrols['href'])" />
        </x-stat-grid>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900">Today's schedule</h2>
                            <p class="text-xs text-zinc-500">{{ $todayShifts->count() }} shift{{ $todayShifts->count() === 1 ? '' : 's' }} scheduled</p>
                        </div>
                        <a href="{{ route('schedules.index') }}" class="text-xs font-medium text-zinc-600 hover:text-zinc-900">View all</a>
                    </div>

                    @forelse ($todayShifts as $shift)
                        <div class="flex items-center gap-4 border-t border-zinc-100 px-4 py-3 first:border-t-0">
                            <div class="w-14 shrink-0 text-center">
                                <div class="text-sm font-semibold text-zinc-900">{{ $shift->starts_at->format('H:i') }}</div>
                                <div class="text-[10px] text-zinc-400">{{ $shift->ends_at->format('H:i') }}</div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-zinc-900">{{ $shift->title }}</div>
                                <div class="truncate text-xs text-zinc-500">{{ $shift->site?->name ?? 'No site' }}</div>
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
                            <a href="{{ route('schedules.index') }}" class="mt-2 inline-block text-sm font-medium text-zinc-700 hover:underline">Create a shift</a>
                        </div>
                    @endforelse
                </section>

                <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
                        <div>
                            <h2 class="text-sm font-semibold text-zinc-900">Recent incidents</h2>
                            <p class="text-xs text-zinc-500">{{ $weekSummary['incidents'] }} this week</p>
                        </div>
                        <a href="{{ route('incidents.index') }}" class="text-xs font-medium text-zinc-600 hover:text-zinc-900">View all</a>
                    </div>

                    @forelse ($incidentsList as $incident)
                        <a href="{{ route('incidents.index') }}" class="flex items-start gap-3 border-t border-zinc-100 px-4 py-3 transition first:border-t-0 hover:bg-zinc-50">
                            <div @class([
                                'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                'bg-red-500' => in_array($incident->severity, ['critical', 'high']),
                                'bg-amber-500' => $incident->severity === 'medium',
                                'bg-zinc-300' => ! in_array($incident->severity, ['critical', 'high', 'medium']),
                            ])></div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-zinc-900">{{ $incident->title }}</div>
                                <div class="text-xs text-zinc-500">
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

                <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
                    <div class="border-b border-zinc-100 px-4 py-3">
                        <h2 class="text-sm font-semibold text-zinc-900">On duty now</h2>
                        <p class="text-xs text-zinc-500">Guards currently clocked in</p>
                    </div>

                    @forelse ($attendance as $log)
                        <div class="flex items-center gap-3 border-t border-zinc-100 px-4 py-3 first:border-t-0">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600">
                                {{ strtoupper(substr($log->assignedGuard?->first_name ?? 'G', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-zinc-900">{{ $log->assignedGuard?->full_name ?? 'Guard' }}</div>
                                <div class="truncate text-xs text-zinc-500">{{ $log->site?->name }} · since {{ $log->clock_in_at?->format('H:i') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center">
                            <p class="text-sm text-zinc-500">Nobody clocked in.</p>
                            <a href="{{ route('attendance.timekeeping') }}" class="mt-2 inline-block text-xs font-medium text-zinc-600 hover:underline">Timekeeping</a>
                        </div>
                    @endforelse
                </section>

                <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-semibold text-zinc-900">Quick actions</h2>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <a href="{{ route('guards.index') }}" class="rounded-lg border border-zinc-200 px-3 py-2.5 text-center text-xs font-medium text-zinc-700 transition hover:bg-zinc-50">Guards</a>
                        <a href="{{ route('patrols.index') }}" class="rounded-lg border border-zinc-200 px-3 py-2.5 text-center text-xs font-medium text-zinc-700 transition hover:bg-zinc-50">Patrols</a>
                        <a href="{{ route('dispatch.control-room') }}" class="rounded-lg border border-zinc-200 px-3 py-2.5 text-center text-xs font-medium text-zinc-700 transition hover:bg-zinc-50">Dispatch</a>
                        <a href="{{ route('guards.kyg') }}" class="rounded-lg border border-zinc-200 px-3 py-2.5 text-center text-xs font-medium text-zinc-700 transition hover:bg-zinc-50">Know Your Guard</a>
                    </div>
                </section>

                <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-semibold text-zinc-900">7-day activity</h2>
                    <p class="mb-4 text-xs text-zinc-500">{{ $weekSummary['patrols'] }} patrols · {{ $weekSummary['missed_patrols'] }} missed</p>
                    <x-dashboard.trend-chart :series="$patrolTrend" color="zinc" />
                    <div class="mt-4 border-t border-zinc-100 pt-4">
                        <x-dashboard.trend-chart title="Incidents" :series="$incidentTrend" color="amber" />
                    </div>
                </section>
            </div>
        </div>
    </x-page-shell>
</div>
