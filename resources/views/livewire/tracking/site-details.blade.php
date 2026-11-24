@if ($selectedCoverage)
    @php
        $site = $selectedCoverage['site'];
        $status = $selectedCoverage['status'];
        $statusLabel = $coverageStates[$status];
        $lastCheckIn = $selectedCoverage['deployments']->where('verified', true)->pluck('checkIn.clock_in_at')->filter()->max();
        $lastPosition = $selectedCoverage['deployments']->pluck('location.recorded_at')->filter()->max();
        $accountReferences = $selectedAccounts->map(fn ($account) => $account->account_number ?? $account->account_reference)
            ->push($selectedAccounts->isEmpty() ? $site->patriot_client_ref : null)->filter()->unique();
    @endphp
    <x-section-card compact :title="$site->name" :description="$site->clientAccount?->name !== $site->name ? $site->clientAccount?->name : null"
        aria-label="Selected site details" data-selected-site tabindex="-1" class="scroll-mt-[calc(var(--app-header-height)+0.75rem)]">
        <x-slot:actions>
            <span class="inline-flex max-w-36 items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <span class="tracking-shield tracking-shield--{{ $status }} shrink-0" aria-hidden="true">{{ match ($status) { 'protected' => '✓', 'unprotected' => '×', 'inactive', 'not_scheduled' => '–', default => '!' } }}</span>
                {{ $statusLabel }}
            </span>
        </x-slot:actions>

        <div class="space-y-3 text-sm">
            @if ($selectedCoverage['required'] > 0)
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $selectedCoverage['verified'] }} verified guards · {{ $selectedCoverage['filled'] }} of {{ $selectedCoverage['required'] }} required positions covered</p>
            @endif
            <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1.5 rounded-lg bg-zinc-50 px-3 py-2.5 text-xs dark:bg-zinc-800/50 [&>dd]:break-words">
                @if ($site->address)<dt class="text-zinc-500">Address</dt><dd>{{ $site->address }}</dd>@endif
                @if ($site->latitude !== null && $site->longitude !== null)
                    <dt class="text-zinc-500">Coordinates</dt><dd class="tabular-nums">{{ $site->latitude }}, {{ $site->longitude }}</dd>
                @endif
                @if ($site->supervisor)<dt class="text-zinc-500">Supervisor</dt><dd>{{ $site->supervisor->name }}</dd>@endif
                @if ($accountReferences->isNotEmpty())<dt class="text-zinc-500">Patriot accounts</dt><dd>{{ $accountReferences->implode(', ') }}</dd>@endif
                @if ($lastCheckIn || $selectedCoverage['required'] > 0)
                    <dt class="text-zinc-500">Verified check-in</dt><dd>{{ $lastCheckIn?->format('M j, H:i') ?? 'Awaiting check-in' }}</dd>
                @endif
                @if ($lastPosition || $selectedCoverage['required'] > 0)
                    <dt class="text-zinc-500">Last GPS update</dt><dd>{{ $lastPosition?->format('M j, H:i') ?? 'Unavailable' }}</dd>
                @endif
            </dl>

            <div class="flex flex-wrap gap-x-3 gap-y-1">
                @can('sites.manage')
                    <a class="page-link" href="{{ route('sites.show', $site) }}">View site</a>
                @endcan
                @can('schedules.manage')
                    <a class="page-link" href="{{ route('schedules.index') }}">View deployments</a>
                @endcan
            </div>

            @if ($selectedCoverage['shifts']->isNotEmpty())
                <div>
                    <h3 class="font-semibold">Current shifts</h3>
                    <ul class="mt-1 space-y-1 text-xs">
                        @foreach ($selectedCoverage['shifts'] as $shift)
                            <li>{{ $shift->title ?: 'Scheduled shift' }} · {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }} · {{ $shift->required_guards }} required</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($selectedCoverage['deployments']->isNotEmpty())
                <div>
                    <h3 class="font-semibold">Assigned guards</h3>
                    <ul class="mt-1 space-y-1 text-xs">
                        @foreach ($selectedCoverage['deployments'] as $deployment)
                            @if ($deployment['guard'])
                                <li wire:key="site-deployment-{{ $deployment['assignment']->id }}">
                                    <button type="button" class="page-link" wire:click="openDeployment({{ $deployment['assignment']->id }})">{{ $deployment['guard']->full_name }}</button>
                                    · {{ $deploymentStates[$deployment['status']] }}
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($recentAttendance->isNotEmpty())
                <details wire:ignore.self class="border-t border-zinc-200 pt-2 dark:border-zinc-800">
                    <summary class="cursor-pointer font-medium">Recent deployment activity</summary>
                    <ul class="mt-2 space-y-1 text-xs">
                        @foreach ($recentAttendance as $log)
                            <li>{{ $log->assignedGuard?->full_name ?? 'Guard' }} · {{ Str::headline($log->type) }} · {{ $log->recorded_at?->format('M j, H:i') }}</li>
                            @if ($log->clock_out_at)
                                <li>{{ $log->assignedGuard?->full_name ?? 'Guard' }} · Check out · {{ $log->clock_out_at->format('M j, H:i') }}</li>
                            @endif
                        @endforeach
                    </ul>
                </details>
            @endif

            @if ($relatedIncidents->isNotEmpty())
                <details wire:ignore.self class="border-t border-zinc-200 pt-2 dark:border-zinc-800">
                    <summary class="cursor-pointer font-medium">Related incidents ({{ $relatedIncidents->count() }})</summary>
                    <ul class="mt-1 space-y-1">
                        @foreach ($relatedIncidents as $incident)
                            <li><a class="page-link" href="{{ route('incidents.index', ['incident' => $incident->id]) }}">{{ $incident->title }}</a></li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    </x-section-card>
@endif

@if ($selectedDeployment && $selectedDeployment['guard'])
    @php
        $guard = $selectedDeployment['guard'];
        $shift = $selectedDeployment['shift'];
        $position = $selectedDeployment['location'];
    @endphp
    <x-section-card compact :title="$guard->full_name" :description="$guard->employee_number" aria-label="Selected guard details">
        <div class="space-y-2 text-sm">
            <p>{{ $selectedDeployment['site']->clientAccount?->name }} · {{ $selectedDeployment['site']->name }}</p>
            <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-1 text-xs">
                <dt class="text-zinc-500">Deployment</dt><dd>{{ $deploymentStates[$selectedDeployment['status']] }}</dd>
                <dt class="text-zinc-500">Duty status</dt><dd>{{ $selectedDeployment['onDuty'] ? 'Checked in' : 'Not checked in' }}</dd>
                <dt class="text-zinc-500">Shift</dt><dd>{{ $shift->starts_at->format('M j, H:i') }} – {{ $shift->ends_at->format('M j, H:i') }}</dd>
                <dt class="text-zinc-500">Geofence</dt><dd>{{ Str::headline($selectedDeployment['geofence']) }}</dd>
                <dt class="text-zinc-500">GPS</dt>
                <dd>{{ $position ? ($selectedDeployment['stale'] ? 'Last known' : 'Recent') : 'Unavailable' }}</dd>
                @if ($position)
                    <dt class="text-zinc-500">Last position</dt><dd class="tabular-nums">{{ $position->latitude }}, {{ $position->longitude }}</dd>
                    <dt class="text-zinc-500">Received</dt><dd>{{ $position->recorded_at->format('M j, H:i:s') }}</dd>
                @endif
            </dl>

            @if ($currentDispatch)
                <p class="text-xs">
                    <a class="page-link" href="{{ route('dispatch.control-room', ['q' => $currentDispatch->dispatch_number]) }}">{{ $currentDispatch->dispatch_number ?? 'Current dispatch' }}</a>
                    · {{ $currentDispatch->status->label() }}
                    @if ($currentDispatch->incident_id && auth()->user()->can('incidents.manage'))
                        · <a class="page-link" href="{{ route('incidents.index', ['incident' => $currentDispatch->incident_id]) }}">Open incident</a>
                    @endif
                </p>
            @endif

            <div class="flex flex-wrap gap-x-3 gap-y-1">
                @if ($position)
                    <button type="button" class="page-link" wire:click="focusGuard({{ $guard->id }})">View guard position</button>
                @endif
                @can('guards.manage')
                    <a class="page-link" href="{{ route('guards.show', $guard) }}">Guard profile</a>
                    @if ($guard->phone)<a class="page-link" href="tel:{{ $guard->phone }}">Call {{ $guard->phone }}</a>@endif
                @endcan
            </div>
        </div>
    </x-section-card>
@endif
