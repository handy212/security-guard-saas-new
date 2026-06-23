<div>
    <x-page-header title="Operations Dashboard" description="Live command center for guards, sites, incidents, patrols, and attendance." />

    @if($showOnboarding)
        <div class="px-6 pb-2">
            <x-onboarding-checklist :steps="$onboardingSteps" :progress="$onboardingProgress" />
        </div>
    @endif

    <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach($stats as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :tone="$stat['tone']" />
        @endforeach
    </div>

    <div class="grid gap-6 px-6 pb-6 lg:grid-cols-2">
        <x-section-card title="Incidents — last 7 days">
            @if($incidentTrend->isNotEmpty())
                <div class="flex h-36 items-end gap-2">
                    @foreach($incidentTrend as $day => $count)
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <div class="w-full rounded-t bg-amber-500" style="height: {{ max(8, $count * 20) }}px" title="{{ $day }}: {{ $count }}"></div>
                            <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($day)->format('D') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state title="No incidents this week" description="That's a good sign — your sites are quiet." />
            @endif
        </x-section-card>

        <x-section-card title="Patrol sessions — last 7 days">
            @if($patrolTrend->isNotEmpty())
                <div class="flex h-36 items-end gap-2">
                    @foreach($patrolTrend as $day => $count)
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <div class="w-full rounded-t bg-sky-500" style="height: {{ max(8, $count * 16) }}px" title="{{ $day }}: {{ $count }}"></div>
                            <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($day)->format('D') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state title="No patrol data yet" description="Start a patrol from the guard app to see trends here." action="/guard" actionLabel="Open guard app" />
            @endif
        </x-section-card>
    </div>

    <div class="grid gap-6 px-6 pb-8 lg:grid-cols-2">
        <x-section-card title="Latest incidents">
            @forelse($incidents as $incident)
                <div class="flex items-start justify-between border-t border-slate-100 py-3 first:border-t-0">
                    <div>
                        <div class="font-medium text-slate-900">{{ $incident->title }}</div>
                        <div class="text-sm text-slate-500">{{ $incident->site?->name }} · {{ $incident->severity }} · {{ $incident->status }}</div>
                    </div>
                    <a href="/incidents" class="text-xs text-sky-700 hover:underline">View</a>
                </div>
            @empty
                <x-empty-state title="No incidents" description="Incident reports from guards and supervisors will appear here." />
            @endforelse
        </x-section-card>

        <x-section-card title="Live attendance">
            @forelse($attendance as $log)
                <div class="border-t border-slate-100 py-3 first:border-t-0">
                    <div class="font-medium text-slate-900">{{ $log->assignedGuard?->full_name ?? 'Guard' }}</div>
                    <div class="text-sm text-slate-500">
                        {{ $log->site?->name }}
                        · {{ $log->status }}
                        · {{ $log->clock_in_at?->format('M j, H:i') ?? $log->recorded_at?->format('M j, H:i') }}
                    </div>
                </div>
            @empty
                <x-empty-state title="No attendance yet" description="Guards clock in from the field app or supervisor board." action="/attendance/timekeeping" actionLabel="Timekeeping" />
            @endforelse
        </x-section-card>
    </div>
</div>
