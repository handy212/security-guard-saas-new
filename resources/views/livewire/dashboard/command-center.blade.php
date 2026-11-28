@php
    $nav = app(\App\Services\NavigationBuilder::class);
    $can = fn (string $route, string $permission) => $nav->routeVisible($route, $permission);
    $schedule = $can('schedules.index', 'schedules.manage');
    $dispatch = $can('dispatch.control-room', 'dispatch.manage');
    $sites = $can('sites.index', 'sites.manage');
    $tracking = $can('tracking.live', 'dispatch.manage');
    $incidents = $can('incidents.index', 'incidents.manage');
    $patrols = $can('patrols.index', 'patrols.manage');
    $canAttendance = $can('schedules.attendance', 'attendance.manage');
    $metrics = collect($kpis)->keyBy('key');
    $rosterUrl = route('schedules.index', ['date' => today()->toDateString()]);
    $deployUrl = route('schedules.deploy', ['date' => today()->toDateString()]);
    $coverageGaps = $coverage->filter(fn ($row) => $row['onDuty'] < $row['required']);
    $urgent = $urgentIncidents;
    $sos = $dispatch ? $metrics['sos']['value'] : 0;
    $guardCount = $metrics['guards']['value'];
    $guardRoster = $metrics['guards']['roster'];
    $tiles = array_filter([
        $tracking ? ['Guards on duty', $guardCount, $guardRoster ? round(100 * $guardCount / $guardRoster).'% on duty' : 'No roster', 'workforce', 'green', $guardRoster] : null,
        $sites && $schedule ? ['Sites covered', $coverage->count() - $coverageGaps->count(), $coverage->isEmpty() ? 'No active shifts' : ($coverageGaps->isEmpty() ? 'All covered' : 'Coverage gaps'), 'clients', 'green', null] : null,
        $sites && $schedule ? ['Coverage gaps', $coverageGaps->count(), $coverageGaps->isEmpty() ? 'No gaps' : 'Needs staffing', 'guards', 'red', null] : null,
        $dispatch ? ['Open SOS alerts', $sos, $sos ? 'Response needed' : 'No alerts', 'siren', 'red', null] : null,
        $incidents ? ['Open incidents', $metrics['incidents']['value'], $metrics['incidents']['value'] ? 'Open cases' : 'No open cases', 'plans', 'blue', null] : null,
        $patrols ? ['Patrols in progress', $metrics['patrols']['active'], $metrics['patrols']['active'] ? 'Active now' : 'None active', 'route', 'blue', null] : null,
    ]);
    $week = collect(range(6, 0))->map(fn (int $offset) => today()->subDays($offset));
    $responseCeiling = max(60, (int) ceil($response->max() / 60) * 60);
    $responsePoints = $week->map(function ($day, int $index) use ($response, $responseCeiling) {
        $seconds = $response->get($day->toDateString());

        return $seconds === null ? null : ['x' => 10 + 50 * $index, 'y' => 72 - 60 * $seconds / $responseCeiling, 'seconds' => $seconds];
    });
    $dailyAttendance = $attendanceByDay->keyBy('day');
    $lateCheckIns = $attendanceByDay->sum('late');
    $checkIns = $attendanceByDay->sum('total');
    $patrolTotal = $patrolResults->sum();
    $patrolRate = $patrolTotal ? (int) round(100 * ($patrolResults['completed'] ?? 0) / $patrolTotal) : null;
    $events = collect()
        ->when($dispatch, fn ($feed) => $feed->concat($openDispatches->map(fn ($event) => [
            'title' => $event->dispatch_number.' · '.str_replace('_', ' ', $event->event_type ?? 'Dispatch'),
            'context' => $event->site?->name ?? 'No site',
            'time' => $event->opened_at ?? $event->created_at,
            'icon' => 'radio', 'tone' => 'red',
            'url' => route('dispatch.control-room', ['q' => $event->dispatch_number]),
        ])))
        ->when($tracking, fn ($feed) => $feed->concat($attendance->filter(fn ($log) => $log->clock_in_at)->map(fn ($log) => [
            'title' => ($log->assignedGuard?->full_name ?? 'Guard').' clocked in',
            'context' => $log->site?->name ?? 'No site',
            'time' => $log->clock_in_at,
            'icon' => 'check', 'tone' => 'green',
            'url' => route('tracking.live', ['guard' => $log->guard_id]),
        ])))
        ->when($incidents, fn ($feed) => $feed->concat($incidentsList->map(fn ($incident) => [
            'title' => $incident->title, 'context' => $incident->site?->name ?? 'No site',
            'time' => $incident->created_at, 'icon' => 'incidents', 'tone' => 'blue',
            'url' => route('incidents.index', ['incident' => $incident->id]).'#incident-detail',
        ])))
        ->when($patrols, fn ($feed) => $feed->concat($recentPatrols->map(fn ($session) => [
            'title' => 'Patrol started',
            'context' => $session->route?->name ?? 'Patrol route',
            'time' => $session->started_at,
            'icon' => 'route', 'tone' => 'blue',
            'url' => route('patrols.index'),
        ])))
        ->filter(fn ($event) => $event['time'] !== null)->sortByDesc('time')->take(5);
@endphp

<div wire:poll.30s class="command-center">
    <header class="command-heading">
        <div>
            <h1>Operations Desk</h1>
            <p class="command-muted">Live Security Command Center · {{ now()->format('l, j F Y, H:i') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($schedule)
                <a class="command-button" href="{{ $rosterUrl }}"><x-nav-icon name="schedules" class="size-4 shrink-0" />Today’s roster</a>
                <a class="command-button command-button-primary" href="{{ $deployUrl }}"><x-nav-icon name="rocket" class="size-4 shrink-0" />Deployment wizard</a>
            @endif
            @if ($dispatch)
                <a class="command-button" href="{{ route('dispatch.control-room') }}"><x-nav-icon name="radio" class="size-4 shrink-0" />Open dispatch</a>
            @endif
        </div>
    </header>

    @if ($tiles)
        <section class="command-metrics" aria-label="Operations summary">
            @foreach ($tiles as [$label, $value, $hint, $icon, $tone, $total])
                <article class="command-panel command-metric command-tone-{{ $tone }}" wire:key="command-metric-{{ $icon }}">
                    <x-nav-icon :name="$icon" class="command-icon size-6 shrink-0" />
                    <div class="min-w-0">
                        <h2>{{ $label }}</h2>
                        <p class="command-value">{{ $value }}@if ($total)<span class="command-metric-total"> / {{ $total }}</span>@endif</p>
                    </div>
                    <p class="command-metric-status">{{ $hint }}</p>
                </article>
            @endforeach
        </section>
    @endif

    @if ($sites && $schedule || $tracking || $dispatch || $incidents)
        <div class="command-grid command-grid-coverage">
            @if ($sites && $schedule)
                <section class="command-panel">
                    <div class="command-panel-head"><h2><x-nav-icon name="clients" class="size-4 shrink-0" />Site coverage</h2><a href="{{ route('sites.index') }}">View all sites →</a></div>
                    @if ($coverage->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="command-table">
                                <thead><tr><th scope="col">Site / Location</th><th scope="col">Required</th><th scope="col">On duty</th><th scope="col">Status</th></tr></thead>
                                <tbody>
                                    @foreach ($coverage->take(6) as $site)
                                        @php
                                            $coverageStatus = match (true) {
                                                $site['onDuty'] >= $site['required'] => 'good',
                                                $site['onDuty'] > 0 => 'warning',
                                                default => 'alert',
                                            };
                                        @endphp
                                        <tr class="command-status-{{ $coverageStatus }}">
                                            <td><a class="flex items-center gap-2" href="{{ $site['url'] }}"><span class="command-status-dot" aria-hidden="true"></span>{{ $site['name'] }}</a></td>
                                            <td>{{ $site['required'] }}</td>
                                            <td>{{ $site['onDuty'] }}</td>
                                            <td><span class="command-chip command-chip-{{ $coverageStatus }}">{{ ['good' => 'Covered', 'warning' => 'Understaffed', 'alert' => 'Uncovered'][$coverageStatus] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="command-empty">No active shifts to cover.</p>
                    @endif
                </section>
            @endif

            @if ($tracking)
                <section class="command-panel">
                    <div class="command-panel-head"><h2><x-nav-icon name="patrols" class="size-4 shrink-0" />Live Tactical Map</h2>
                        <div class="command-card-tools">
                            @if ($markers)<span class="command-chip command-chip-alert"><span aria-hidden="true">●</span> Live</span>@endif
                            <a href="{{ route('tracking.live') }}">Open map ↗</a>
                        </div></div>
                    <x-map id="command-center-map" height="clamp(210px, 29dvh, 400px)" :markers="$markers"
                           :viewport-key="$markers ? 'guards' : 'overview'" data-map-fit-control
                           :lat="$mapSite?->latitude ?? 0" :lng="$mapSite?->longitude ?? 0" :zoom="$markers || $mapSite ? 13 : 2" class="m-2" />
                    @if ($markers)
                        <div class="command-map-key"><span class="command-status-dot" aria-hidden="true"></span>{{ count($markers) }} recent guard {{ \Illuminate\Support\Str::plural('location', count($markers)) }} <span>· Last hour</span></div>
                    @else
                        <p class="command-empty">No guard locations received in the last hour.</p>
                    @endif
                </section>
            @endif

            @if ($dispatch || $incidents)
                <section class="command-panel command-alert-panel">
                    <div class="command-panel-head">
                        <h2><x-nav-icon name="incidents" class="size-4 shrink-0" />Urgent Alerts &amp; Incidents</h2>
                        @if ($incidents)<a href="{{ route('incidents.index') }}">View incidents →</a>@endif
                    </div>
                    @if ($sos)
                        <a class="command-row command-alert" href="{{ route('dispatch.control-room') }}#dispatch-sos"><x-nav-icon name="siren" class="size-5 shrink-0" /><span class="grow">{{ $sos }} open SOS {{ \Illuminate\Support\Str::plural('alert', $sos) }}</span><span>Respond →</span></a>
                    @endif
                    @if ($incidents)
                        @foreach ($urgent->take(4) as $incident)
                            <a class="command-row" href="{{ route('incidents.index', ['incident' => $incident->id]) }}#incident-detail">
                                <x-nav-icon name="incidents" class="size-5 shrink-0 text-red-300" />
                                <div class="min-w-0 grow"><p>{{ $incident->title }}</p><p class="command-muted">{{ $incident->site?->name ?? 'No site' }} · {{ $incident->created_at->diffForHumans() }}</p></div>
                                <span class="command-chip command-chip-alert">{{ ucfirst($incident->severity) }}</span>
                            </a>
                        @endforeach
                    @endif
                    @if (! $sos && (! $incidents || $urgent->isEmpty()))
                        <p class="command-empty">No urgent alerts in the current queue.</p>
                    @endif
                </section>
            @endif
        </div>
    @endif

    @if ($tracking || $patrols || $dispatch)
        <div class="command-grid">
            @if ($tracking)
                <section class="command-panel">
                    <div class="command-panel-head"><h2><x-nav-icon name="visitors" class="size-4 shrink-0" />Active Guards <span class="command-muted">({{ $metrics['guards']['value'] }} on duty)</span></h2><a href="{{ route('tracking.live') }}">View all →</a></div>
                    @forelse ($attendance->whereNotNull('clock_in_at')->unique('guard_id')->take(4) as $log)
                        @php $guard = $log->assignedGuard; @endphp
                        <a class="command-row command-status-good" href="{{ route('tracking.live', ['guard' => $log->guard_id]) }}">
                            <span class="command-status-dot" aria-hidden="true"></span>
                            @if ($guard?->photo_path && auth()->user()->can('guards.manage'))
                                <img src="{{ route('files.guard-photo', $guard) }}" alt="" class="size-8 shrink-0 rounded-full object-cover">
                            @else
                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-white/10 text-xs font-semibold" aria-hidden="true">{{ $guard ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($guard->first_name, 0, 1).\Illuminate\Support\Str::substr($guard->last_name, 0, 1)) : 'G' }}</span>
                            @endif
                            <div class="min-w-0 grow"><p>{{ $guard?->full_name ?? 'Guard' }}</p><p class="command-muted">{{ $log->site?->name ?? 'No site' }} · Since {{ $log->clock_in_at->format('H:i') }}</p></div>
                            <span class="command-chip command-chip-good">On duty</span>
                            <span class="command-muted" aria-hidden="true">›</span>
                        </a>
                    @empty
                        <p class="command-empty">No guards clocked in.</p>
                    @endforelse
                </section>
            @endif

            @if ($patrols)
                <section class="command-panel">
                    <div class="command-panel-head"><h2><x-nav-icon name="analytics" class="size-4 shrink-0" />Patrol Compliance <span class="command-muted">(Last 7 days)</span></h2><a href="{{ route('patrols.index') }}">Patrols</a></div>
                    @if ($patrolTotal)
                        <div class="flex items-center gap-4 p-3">
                            <div class="command-ring" style="--completion: {{ $patrolRate }}%"><strong>{{ $patrolRate }}%<small>Completion</small></strong></div>
                            <dl class="flex-1 text-sm">
                                <div class="command-row"><dt class="command-status-good"><span class="command-status-dot" aria-hidden="true"></span> Completed</dt><dd>{{ $patrolResults['completed'] ?? 0 }}</dd></div>
                                <div class="command-row"><dt class="command-status-alert"><span class="command-status-dot" aria-hidden="true"></span> Missed</dt><dd>{{ $patrolResults['missed'] ?? 0 }}</dd></div>
                                <div class="command-row"><dt>Recorded</dt><dd>{{ $patrolTotal }}</dd></div>
                            </dl>
                        </div>
                    @else
                        <p class="command-empty">No completed or missed patrols in the last seven days.</p>
                    @endif
                </section>
            @endif

            @if ($dispatch)
                <section class="command-panel">
                    <div class="command-panel-head"><h2><x-nav-icon name="bolt" class="size-4 shrink-0" />Response Time</h2></div>
                    @if ($averageResponse !== null)
                        <div class="space-y-2 p-3">
                            <p class="text-sm">Average <strong>{{ intdiv((int) $averageResponse, 60) }}m {{ (int) $averageResponse % 60 }}s</strong> <span class="command-muted">· Opened to on scene</span></p>
                            <div class="command-response-chart">
                                <svg viewBox="0 0 320 78" role="img" aria-label="Average dispatch response by day">
                                    @foreach ([12, 32, 52, 72] as $line)
                                        <line x1="10" x2="310" y1="{{ $line }}" y2="{{ $line }}" stroke="#ffffff18" />
                                    @endforeach
                                    @foreach ($responsePoints as $index => $point)
                                        @continue($point === null)
                                        @php $previous = $responsePoints->get($index - 1); @endphp
                                        @if ($previous)
                                            <polygon points="{{ $previous['x'] }},72 {{ $previous['x'] }},{{ $previous['y'] }} {{ $point['x'] }},{{ $point['y'] }} {{ $point['x'] }},72" fill="var(--command-accent)" fill-opacity=".22" />
                                            <line x1="{{ $previous['x'] }}" y1="{{ $previous['y'] }}" x2="{{ $point['x'] }}" y2="{{ $point['y'] }}" stroke="var(--command-accent)" stroke-width="2" />
                                        @endif
                                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3" fill="#fff" stroke="var(--command-accent)">
                                            <title>{{ $week[$index]->format('l') }} · {{ $point['seconds'] }} seconds</title>
                                        </circle>
                                    @endforeach
                                </svg>
                                <div class="command-chart-days">
                                    @foreach ($week as $day)
                                        <span>{{ $day->format('D') }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <p class="command-empty">No dispatch arrivals recorded in the last seven days.</p>
                    @endif
                </section>
            @endif
        </div>
    @endif

    @if ($schedule || $dispatch || $tracking || $incidents || $patrols)
        <div class="command-grid command-grid-two command-grid-roster">
            @if ($schedule)
                <section class="command-panel">
                    <div class="command-panel-head"><h2><x-nav-icon name="schedules" class="size-4 shrink-0" />Live Shift Roster <span class="command-muted">(Today)</span></h2><a href="{{ $rosterUrl }}">View full roster →</a></div>
                    @if ($todayShifts->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="command-table command-roster-table">
                                <thead><tr><th scope="col">Shift</th><th scope="col" title="Assigned / required">Guards</th><th scope="col">Site</th><th scope="col">Status</th></tr></thead>
                                <tbody>
                                    @foreach ($todayShifts->take(5) as $shift)
                                        <tr>
                                            <td><a class="font-medium" href="{{ $rosterUrl }}">{{ $shift->title }}</a><div class="command-muted">{{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}</div></td>
                                            <td @class(['text-amber-300' => $shift->is_understaffed])>{{ $shift->staffed_count }}/{{ $shift->required_guards }}</td>
                                            <td>{{ $shift->site?->name ?? 'No site' }}</td>
                                            <td><span @class(['command-chip', 'command-chip-good' => $shift->status === \App\Enums\ShiftStatus::IN_PROGRESS])>{{ \Illuminate\Support\Str::headline($shift->status->value) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="command-empty">No shifts scheduled today.</p>
                    @endif
                </section>
            @endif
            @if ($dispatch || $tracking || $incidents || $patrols)
                <section class="command-panel">
                    <div class="command-panel-head"><h2><x-nav-icon name="activity-list" class="size-4 shrink-0" />Recent Events <span class="command-muted">(Live feed)</span></h2>@if ($dispatch)<a href="{{ route('dispatch.control-room') }}">Control room</a>@endif</div>
                    @forelse ($events as $event)
                        <a @class(['command-row command-event', 'command-tone-'.$event['tone']]) href="{{ $event['url'] }}">
                            <time class="command-muted tabular-nums" datetime="{{ $event['time']->toIso8601String() }}" title="{{ $event['time']->format('j M Y, H:i') }}">{{ $event['time']->isToday() ? $event['time']->format('H:i') : $event['time']->format('j M') }}</time>
                            <x-nav-icon :name="$event['icon']" class="command-icon size-4 shrink-0" />
                            <div class="command-event-detail min-w-0 flex-1"><p>{{ $event['title'] }}</p><p class="command-muted">{{ $event['context'] }}</p></div>
                        </a>
                    @empty
                        <p class="command-empty">No recent events.</p>
                    @endforelse
                </section>
            @endif
        </div>
    @endif

    @if ($showOnboarding)
        <x-onboarding-checklist :steps="$onboardingSteps" :progress="$onboardingProgress" />
    @endif

    <div class="command-grid command-grid-two command-grid-attendance">
    @if ($canAttendance)
        <section class="command-panel">
            <div class="command-panel-head"><h2><x-nav-icon name="schedules" class="size-4 shrink-0" />Guard Attendance <span class="command-muted">(Last 7 days)</span></h2><a href="{{ route('schedules.attendance') }}">Attendance</a></div>
            @if ($attendanceByDay->isNotEmpty())
                <div class="space-y-2 p-3">
                    <div class="command-attendance-key">
                        <span class="command-status-good"><span class="command-status-dot" aria-hidden="true"></span> {{ $checkIns - $lateCheckIns }} other check-ins</span>
                        <span class="command-status-alert"><span class="command-status-dot" aria-hidden="true"></span> {{ $lateCheckIns }} late</span>
                    </div>
                    <div class="command-bars">
                        @foreach ($week as $day)
                            @php $dayAttendance = $dailyAttendance->get($day->toDateString()); @endphp
                            <div>
                                @if ($dayAttendance)
                                    <span title="{{ $dayAttendance->total }} check-ins, {{ $dayAttendance->late }} late">{{ $dayAttendance->total }}<span class="sr-only"> check-ins, {{ $dayAttendance->late }} late</span></span>
                                    <i class="command-attendance-bar" aria-hidden="true" style="--late: {{ $dayAttendance->late / $dayAttendance->total * 100 }}%; height: {{ $dayAttendance->total / $attendanceByDay->max('total') * 52 }}px"></i>
                                @endif
                                <span>{{ $day->format('D') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="command-empty">No check-ins recorded in the last seven days.</p>
            @endif
        </section>
    @endif

    @if ($schedule || $patrols || $incidents || $sites)
        <section class="command-panel">
            <div class="command-panel-head"><h2><x-nav-icon name="bolt" class="size-4 shrink-0" />Quick Actions</h2></div>
            <div class="command-actions">
                @if ($schedule)<a href="{{ $deployUrl }}"><x-nav-icon name="person-add" class="size-5" />Assign guard</a>@endif
                @if ($patrols)<a href="{{ route('patrols.index') }}"><x-nav-icon name="route" class="size-5" />Manage patrols</a>@endif
                @if ($incidents)<a href="{{ route('incidents.index') }}"><x-nav-icon name="incidents" class="size-5" />Open incidents</a>@endif
                @if ($sites)<a href="{{ route('sites.index') }}"><x-nav-icon name="clients" class="size-5" />Open sites</a>@endif
            </div>
        </section>
    @endif
    </div>
</div>
