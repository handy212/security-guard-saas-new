<div wire:poll.15s x-data>
    <x-page-shell
        title="Dispatch Center"
        compact
        :breadcrumbs="[['label' => 'Dispatch']]"
    >
        <x-slot:headingIcon>
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-teal-700 text-white"><x-nav-icon name="dispatch" class="size-5" /></span>
        </x-slot:headingIcon>
        <x-slot:actions>
            <div class="dispatch-metrics dispatch-heading-metrics" aria-label="Dispatch totals">
                <x-stat-card compact label="Critical" :value="$stats['critical']" icon="incidents" tone="danger" wire:click="applyStatFilter('critical')" :active="$priorityFilter === 'critical'" />
                <x-stat-card compact label="Pending" :value="$stats['pending']" icon="pause" tone="warning" wire:click="applyStatFilter('unassigned')" :active="$statusFilter === 'unassigned'" />
                <x-stat-card compact label="Assigned" :value="$stats['assigned']" icon="users" tone="info" wire:click="applyStatFilter('assigned')" :active="$statusFilter === 'assigned'" />
                <x-stat-card compact label="En route" :value="$stats['en_route']" icon="gps" tone="info" wire:click="applyStatFilter('en_route')" :active="$statusFilter === 'en_route'" />
            </div>
            <x-button size="sm" wire:click="openForm">New dispatch</x-button>
        </x-slot:actions>

        @if($sosAlerts->isNotEmpty())
            <section id="dispatch-sos" class="card-surface scroll-mt-20 overflow-hidden border-red-200/90 dark:border-red-900/50">
                <div class="card-header border-red-100 bg-red-50/80 dark:border-red-900/40 dark:bg-red-950/40">
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2.5 w-2.5" aria-hidden="true">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                        </span>
                        <div>
                            <h2 class="card-header-title text-red-900 dark:text-red-100">{{ $stats['sos'] > 0 ? 'SOS alerts require response' : 'Acknowledged SOS alerts' }}</h2>
                            <p class="card-header-meta text-red-700/90 dark:text-red-300">{{ $stats['sos'] }} open · {{ $sosAlerts->count() - $stats['sos'] }} acknowledged</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-red-100 dark:divide-red-900/40">
                    @foreach($sosAlerts as $alert)
                        <div class="flex flex-wrap items-start justify-between gap-3 bg-red-50/40 px-4 py-3 dark:bg-red-950/20" wire:key="sos-{{ $alert->id }}">
                            <div class="min-w-0">
                                <div class="font-semibold text-red-900 dark:text-red-100">{{ $alert->assignedGuard?->full_name ?? 'Guard' }}</div>
                                <div class="text-xs text-red-700 dark:text-red-300">{{ $alert->site?->name }} · {{ $alert->message ?? 'SOS' }} · {{ $alert->raised_at?->diffForHumans() }}</div>
                                <p class="mt-1 text-xs font-semibold text-red-800 dark:text-red-200">{{ $alert->status === 'open' ? 'Open — response needed' : 'Acknowledged' }}</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if($alert->status === 'open')
                                    <x-button size="sm" variant="danger" wire:click="acknowledgeSos({{ $alert->id }})" wire:target="acknowledgeSos({{ $alert->id }})">
                                        <span wire:loading.remove wire:target="acknowledgeSos({{ $alert->id }})">Acknowledge</span>
                                        <span wire:loading wire:target="acknowledgeSos({{ $alert->id }})">Acknowledging…</span>
                                    </x-button>
                                @endif
                                <x-button size="sm" wire:click="dispatchFromSos({{ $alert->id }})" wire:target="dispatchFromSos({{ $alert->id }})">
                                    <span wire:loading.remove wire:target="dispatchFromSos({{ $alert->id }})">Dispatch</span>
                                    <span wire:loading wire:target="dispatchFromSos({{ $alert->id }})">Creating dispatch…</span>
                                </x-button>
                                @if ($alert->latitude && $alert->longitude)
                                    <x-button
                                        size="sm"
                                        variant="secondary"
                                        :href="route('tracking.live', ['lat' => $alert->latitude, 'lng' => $alert->longitude, 'guard' => $alert->guard_id])"
                                    >View on map</x-button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <x-flash-status />

        @if ($criticalAlert)
            <section class="dispatch-critical-banner" aria-label="Critical alarm">
                <span class="dispatch-critical-label"><x-nav-icon name="siren" class="size-5 shrink-0" /> Critical alarm</span>
                <div class="min-w-0 grow">
                    <p class="font-semibold">{{ $criticalAlert->dispatch_number ?? 'Dispatch #'.$criticalAlert->id }} · {{ $criticalAlert->patriot_payload['alarm_description'] ?? ($incidentTypes[$criticalAlert->event_type] ?? $criticalAlert->event_type) }}</p>
                    @if ($criticalAlert->site)<p class="text-xs">{{ $criticalAlert->site->name }}</p>@endif
                </div>
                <x-button size="sm" class="dispatch-critical-action" x-on:click="
                    await $wire.selectDispatch({{ $criticalAlert->id }});
                    $nextTick(() => {
                        $refs.dispatchResponder?.focus({ preventScroll: true });
                        $refs.dispatchResponder?.scrollIntoView({ block: 'start' });
                    });
                " wire:loading.attr="disabled" wire:target="selectDispatch">{{ $criticalAlert->mayAssignFromPatriot() ? 'Assign responder' : 'Review dispatch' }}</x-button>
            </section>
        @endif

        <x-page-toolbar class="dispatch-filter-bar" search="search" searchPlaceholder="Dispatch, account, or ticket…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['active' => 'Active', 'unassigned' => 'Unassigned', 'assigned' => 'Assigned', 'en_route' => 'En route', 'returning' => 'Returning', 'closed' => 'Closed', 'all' => 'All dispatches']" />
            </x-slot:tabs>
            <x-slot:controls>
                <button type="button" wire:click="clearFilters" @disabled(! $hasActiveFilters)
                    class="table-action whitespace-nowrap disabled:cursor-default disabled:opacity-50">Clear filters</button>
                <x-filter-select wire:model.live="priorityFilter">
                    <option value="all">All priority</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="normal">Normal</option>
                    <option value="low">Low</option>
                </x-filter-select>
                @if ($patriotCounts['attention'])
                    <x-button size="sm" variant="secondary" wire:click="applyStatFilter('patriot-attention')" aria-pressed="{{ $statusFilter === 'patriot-attention' ? 'true' : 'false' }}">Needs attention</x-button>
                @endif
            </x-slot:controls>
        </x-page-toolbar>

        <div class="dispatch-workbench relative">
            <div
                wire:loading.delay
                wire:target="search,statusFilter,priorityFilter,applyStatFilter,clearFilters"
                role="status"
                class="pointer-events-none absolute inset-x-0 top-0 z-10 h-0.5 rounded bg-teal-600 dark:bg-teal-400"
            ><span class="sr-only">Updating dispatch queue…</span></div>

            <x-section-card compact title="Dispatch queue" aria-label="Priority queue" :description="match ($statusFilter) {
                'patriot' => 'Requests received from Patriot',
                'patriot-attention' => 'Patriot updates requiring review',
                'returning' => 'Teams awaiting return confirmation',
                'unassigned' => 'Active dispatches awaiting a responder',
                'en_route' => 'Responders travelling to an incident',
                'closed' => 'Resolved, closed, and cancelled dispatches',
                default => 'Highest priority first',
            }" flush class="dispatch-queue flex flex-col">
                <div class="px-2 py-2 lg:max-h-[65dvh] lg:overflow-y-auto lg:[scrollbar-gutter:stable]">
                    @forelse($dispatches as $dispatch)
                        @php
                            $priorityTone = match ($dispatch->priority->value ?? '') {
                                'critical' => 'border-l-red-500',
                                'high' => 'border-l-amber-500',
                                'low' => 'border-l-zinc-300 dark:border-l-zinc-600',
                                default => 'border-l-accent-500',
                            };
                        @endphp
                        <button
                            type="button"
                            x-on:click="
                                await $wire.selectDispatch({{ $dispatch->id }});
                                if (matchMedia('(max-width: 1023px)').matches) {
                                    $nextTick(() => {
                                        const panel = $refs.dispatchResponder ?? $refs.dispatchDetail;
                                        panel.focus({ preventScroll: true });
                                        panel.scrollIntoView({ block: 'start' });
                                    });
                                }
                            "
                            wire:loading.attr="disabled"
                            wire:target="selectDispatch"
                            aria-pressed="{{ $selectedId === $dispatch->id ? 'true' : 'false' }}"
                            @class([
                                'board-item',
                                $priorityTone,
                                'board-item-active' => $selectedId === $dispatch->id,
                            ])
                            wire:key="dispatch-{{ $dispatch->id }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="board-item-title tabular-nums">{{ $dispatch->dispatch_number ?? '#'.$dispatch->id }}</div>
                                    @if ($dispatch->patriot_job_id !== null)
                                        <p class="text-xs font-medium {{ $dispatch->patriot_writeback_error ? 'text-red-700 dark:text-red-300' : 'text-zinc-600 dark:text-zinc-300' }}">
                                            {{ $dispatch->patriot_writeback_error ? 'Patriot update needs attention' : ($dispatch->patriot_pending_action ? 'Reporting to Patriot' : ($dispatch->patriot_response_state ? str_replace('_', ' ', ucfirst($dispatch->patriot_response_state)).' · Reported to Patriot' : ($dispatch->guard_id ? 'Awaiting officer response' : 'Assign a responder'))) }}
                                        </p>
                                    @endif
                                    @if ($dispatch->patriot_job_id !== null && ! $dispatch->hasPatriotResponseLocation())
                                        <p class="text-xs font-medium text-red-700 dark:text-red-300">Verify response location</p>
                                    @endif
                                    <div class="board-item-meta">{{ $dispatch->clientAccount?->name ?? '—' }} · {{ $dispatch->site?->name }}</div>
                                    @if ($dispatch->caller_name || $dispatch->incident_location)
                                        <div class="mt-1 truncate text-xs text-zinc-600 dark:text-zinc-300">{{ collect([$dispatch->caller_name, $dispatch->incident_location])->filter()->join(' · ') }}</div>
                                    @endif
                                    <div class="mt-1 text-[11px] text-zinc-400">
                                        {{ $dispatch->assignedGuard?->full_name ?? 'Unassigned' }}
                                        · {{ $dispatch->opened_at?->diffForHumans() ?? $dispatch->created_at?->diffForHumans() }}
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    <x-badge :status="$dispatch->priority->value" :map="['critical'=>'danger','high'=>'warning','normal'=>'info','low'=>'neutral']" />
                                    @if ($dispatch->awaitingStationReturn())
                                        <x-badge status="returning" :map="['returning' => 'warning']" />
                                    @endif
                                    <x-badge :status="$dispatch->status->value" :map="['open'=>'info','assigned'=>'info','en_route'=>'warning','on_scene'=>'warning','resolved'=>'success','closed'=>'neutral','cancelled'=>'danger']" />
                                </div>
                            </div>
                        </button>
                    @empty
                        @if ($hasActiveFilters)
                            <x-empty-state compact title="No matching dispatches" description="Try changing or clearing the filters.">
                                <x-slot:actions>
                                    <x-button size="sm" variant="secondary" wire:click="clearFilters">Clear filters</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        @else
                            <x-empty-state compact title="No dispatches" description="Create a dispatch when a client or site needs response.">
                                <x-slot:actions>
                                    <x-button size="sm" wire:click="openForm">New dispatch</x-button>
                                    <x-button size="sm" variant="secondary" :href="route('schedules.index')">Day roster</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        @endif
                    @endforelse
                </div>
            </x-section-card>

            @if ($selected)
                <div class="dispatch-response min-w-0 space-y-3">
                    <x-section-card compact title="Responder & actions" x-ref="dispatchResponder" tabindex="-1" aria-label="Responder and actions" class="scroll-mt-20">
                        @if ($selected->patriot_job_id !== null)
                            <div class="space-y-2">
                                @error('patriotDecision') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                                @if ($selected->patriot_writeback_error)
                                    <p role="alert" class="text-sm text-red-600 dark:text-red-300">Patriot needs attention: {{ $selected->patriot_writeback_error }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        @if ($selected->patriot_pending_action === 'decline')
                                            <x-button size="sm" variant="secondary" wire:click="reviewPatriotSync('confirm-release')" wire:confirm="Only continue if you verified in Patriot that this request was released.">Record verified release</x-button>
                                        @else
                                            <x-button size="sm" variant="secondary" wire:click="reviewPatriotSync('check')">Check Patriot status</x-button>
                                        @endif
                                        <x-button size="sm" variant="secondary" wire:click="reviewPatriotSync('retry')" wire:confirm="Only retry if you checked in Patriot and confirmed this update was not applied.">Retry verified update</x-button>
                                    </div>
                                @elseif ($selected->patriot_pending_action)
                                    <p role="status" class="text-sm text-amber-700 dark:text-amber-300">Waiting for Patriot to confirm {{ str_replace(['check:', '-'], ['', ' '], $selected->patriot_pending_action) }}.</p>
                                @elseif ($selected->patriot_response_state)
                                    <p class="text-sm">{{ match ($selected->patriot_response_state) {
                                        'accepted' => 'Officer acceptance reported to Patriot.',
                                        'declined' => 'Officer decline reported to Patriot.',
                                        'on_site' => 'Arrival reported to Patriot.',
                                        'completed' => 'Completion reported to Patriot.',
                                        default => 'Patriot update confirmed.',
                                    } }}</p>
                                @elseif ($selected->guard_id && $selected->status === \App\Enums\DispatchStatus::ASSIGNED)
                                    <p class="text-sm">Waiting for the assigned officer to accept or decline.</p>
                                @endif

                                @if (! $selected->hasPatriotResponseLocation())
                                    <p role="alert" class="text-sm text-red-700 dark:text-red-300">Verify the response location before assigning a responder.</p>
                                @endif

                                @if ($patriotSites->isNotEmpty())
                                    <form wire:submit="linkPatriotSite" class="space-y-2">
                                        <x-select wire:model="patriotSiteId" label="Reconcile with a verified site">
                                            <option value="">Select site</option>
                                            @foreach ($patriotSites as $site)
                                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                                            @endforeach
                                        </x-select>
                                        @error('patriotSiteId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                        <x-button type="submit" size="sm" wire:loading.attr="disabled" wire:target="linkPatriotSite">Link site</x-button>
                                    </form>
                                @endif
                            </div>
                        @endif

                        @if ($selected->dispatchTeam)
                            <div class="mb-4 rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-800">
                                <p><strong>{{ $selected->dispatchTeam->name }}</strong> · {{ $selected->dispatchTeam->standbyPoint?->name ?? 'Standby point unavailable' }}</p>
                                @if ($selected->acknowledged_at)
                                    <p>Acknowledged at {{ $selected->acknowledged_at->format('H:i') }}</p>
                                @endif
                                @if ($selected->awaitingStationReturn())
                                    <p class="my-2">Returning to station</p>
                                    <x-button size="sm" wire:click="confirmTeamReturn">Confirm back at station</x-button>
                                @elseif ($selected->returned_at)
                                    <p>Return confirmed at {{ $selected->returned_at->format('H:i') }}</p>
                                @endif
                            </div>
                        @elseif ($selected->mayAssignFromPatriot() && $selected->status === \App\Enums\DispatchStatus::OPEN && $dispatchTeams->isNotEmpty())
                            <form wire:submit="assignDispatchTeam" class="mb-4 space-y-2">
                                <x-select wire:model="assignTeamId" label="Assign response team">
                                    <option value="">Choose an available team</option>
                                    @foreach ($dispatchTeams as $team)
                                        <option value="{{ $team->id }}" @disabled($team->outstandingDispatches->isNotEmpty() || ! $team->standbyPoint?->region?->is_active || ! $team->active_officers_count)>{{ $team->name }} · {{ $team->standbyPoint?->name }}</option>
                                    @endforeach
                                </x-select>
                                @error('assignTeamId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                <x-button type="submit" size="sm" wire:loading.attr="disabled">Assign team</x-button>
                            </form>
                        @endif

                        @if($selected->isActive() && ($selected->mayAssignFromPatriot() || $selected->status->next() || auth()->user()->can('incidents.manage')))
                            <div class="mb-4 space-y-2 rounded-md border border-zinc-200/90 bg-zinc-50/80 p-3 dark:border-zinc-800 dark:bg-zinc-900/50">
                                <p class="meta-tile-label">Actions</p>
                                @if ($selected->patriot_job_id === null)
                                    @error('patriotDecision') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                @endif
                                @if (! $selected->dispatch_team_id && $selected->mayAssignFromPatriot())
                                <fieldset class="space-y-2">
                                    <legend class="mb-2 text-sm font-semibold">{{ $selected->patriot_job_id !== null ? 'Dispatch officers' : 'Responders' }}</legend>
                                    <div class="max-h-64 space-y-1.5 overflow-y-auto pr-1">
                                        @forelse ($assignmentGuards as $guard)
                                            <label class="dispatch-responder-option" wire:key="dispatch-responder-{{ $guard->id }}">
                                                <input type="radio" wire:model.live="assignGuardId" value="{{ $guard->id }}" class="shrink-0 accent-teal-700">
                                                <span class="dispatch-responder-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($guard->first_name, 0, 1).\Illuminate\Support\Str::substr($guard->last_name, 0, 1)) }}</span>
                                                <span class="min-w-0">
                                                    <strong class="block break-words text-sm">{{ $guard->full_name }}</strong>
                                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                                        @if ($busyResponders->has($guard->id))
                                                            Responding · {{ $busyResponders[$guard->id]->first()['number'] ?? 'Active job' }}
                                                        @elseif ($onDutyResponders->has($guard->id))
                                                            On duty · No active dispatch
                                                        @else
                                                            No active check-in
                                                        @endif
                                                    </span>
                                                </span>
                                            </label>
                                        @empty
                                            <p class="text-xs text-zinc-500">{{ $selected->patriot_job_id !== null ? 'No active dispatch officers are configured.' : 'No eligible responders for this dispatch.' }}</p>
                                            @can('guards.manage')
                                                <a href="{{ route('guards.index') }}" class="page-link">Manage guard duty types</a>
                                            @endcan
                                        @endforelse
                                    </div>
                                </fieldset>
                                @error('assignGuardId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    <x-button size="sm" class="dispatch-assign-button" wire:click="assignGuard" wire:loading.attr="disabled" wire:target="assignGuard" :disabled="! $assignGuardId || ! $selected->mayAssignFromPatriot()">Assign responder</x-button>
                                @endif
                                <div class="flex flex-wrap gap-2">
                                    @if ($selected->status->next() && $selected->status !== \App\Enums\DispatchStatus::OPEN
                                        && ($selected->patriot_job_id === null || $selected->patriot_response_state !== null))
                                        <x-button size="sm" wire:click="advanceStatus" :disabled="(bool) $selected->patriot_pending_action" wire:loading.attr="disabled" wire:target="advanceStatus">
                                            Mark {{ strtolower($selected->status->next()->label()) }}
                                        </x-button>
                                    @endif
                                    @can('incidents.manage')
                                        @if (! $selected->incident_id)
                                            <x-button size="sm" variant="secondary" wire:click="promoteToIncident">Create incident</x-button>
                                        @endif
                                    @endcan
                                    @if ($selected->patriot_job_id === null && ! in_array($selected->status, [\App\Enums\DispatchStatus::RESOLVED, \App\Enums\DispatchStatus::CLOSED], true))
                                        <x-button size="sm" variant="secondary" wire:click="confirmCancel">Cancel dispatch</x-button>
                                    @endif
                                </div>
                                @if ($selected->incident_id)
                                    <p class="text-xs text-zinc-500">
                                        Linked incident #{{ $selected->incident_id }}
                                        · <a href="{{ route('incidents.index') }}" class="page-link">Open incidents</a>
                                    </p>
                                @endif
                            </div>
                        @endif

                    </x-section-card>
                    <x-section-card compact title="Dispatch activity">
                        @if (collect($timeline)->contains(fn ($step) => $step['at']) || $selected->acknowledged_at || $selected->returned_at)
                            <ol class="space-y-2 border-l border-zinc-200 pl-3 text-xs dark:border-zinc-700" aria-label="Dispatch timeline">
                                @foreach (collect($timeline)->concat([
                                    ['label' => 'Officer accepted', 'at' => $selected->acknowledged_at],
                                    ['label' => 'Back at station', 'at' => $selected->returned_at],
                                ])->filter(fn ($step) => $step['at'])->sortBy('at') as $step)
                                    <li class="flex flex-wrap justify-between gap-x-2">
                                        <strong>{{ $step['label'] }}</strong>
                                        <time class="tabular-nums text-zinc-500 dark:text-zinc-400" datetime="{{ $step['at']->toIso8601String() }}">{{ $step['at']->format('M j, H:i') }}</time>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                        <div class="mt-4 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                            <h4 class="meta-tile-label mb-2">Activity</h4>
                            @forelse($selected->activityLogs as $log)
                                <div class="border-t border-zinc-100 py-2 text-xs first:border-0 dark:border-zinc-800" wire:key="log-{{ $log->id }}">
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $log->user?->name ?? 'System' }}</span>
                                    <span class="text-zinc-500 dark:text-zinc-400"> — {{ $log->message }}</span>
                                    <div class="tabular-nums text-[10px] text-zinc-400">{{ $log->created_at->format('M j, Y H:i') }} · {{ $log->created_at->diffForHumans() }}</div>
                                </div>
                            @empty
                                <p class="text-xs text-zinc-500">No activity yet.</p>
                            @endforelse
                        </div>
                    </x-section-card>
                </div>
            @endif
            <x-section-card
                compact
                :title="$selected ? ($selected->dispatch_number ?? 'Dispatch #'.$selected->id) : 'Dispatch detail'"
                class="dispatch-detail flex flex-col scroll-mt-20"
                x-ref="dispatchDetail"
                tabindex="-1"
                aria-label="Dispatch details"
            >
                @if($selected)
                    <div x-data="{ panel: 'overview' }" wire:key="dispatch-detail-{{ $selected->id }}" class="min-w-0">
                        <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold">{{ $selected->assignedGuard?->full_name ?? 'Unassigned' }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ collect([$selected->site?->name, $selected->clientAccount?->name])->filter()->unique()->join(' · ') ?: 'Site not linked' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <x-badge :status="$selected->priority->value" :map="['critical'=>'danger','high'=>'warning','normal'=>'info','low'=>'neutral']" />
                                <x-badge :status="$selected->status->value" :map="['open'=>'info','assigned'=>'info','en_route'=>'warning','on_scene'=>'warning','resolved'=>'success','closed'=>'neutral','cancelled'=>'danger']" />
                            </div>
                        </div>

                        <div class="dispatch-detail-tabs" role="group" aria-label="Job details navigation">
                            @foreach (['overview' => 'Overview', 'site' => 'Site details', 'notes' => 'Notes & files'] as $key => $label)
                                <button type="button" x-bind:aria-pressed="panel === @js($key)"
                                    x-on:click="panel = @js($key); if (panel === 'overview') $nextTick(() => window.dispatchEvent(new Event('resize')));">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        @php
                            $location = $selected->incident_location ?: ($patriotPayload['location']['address'] ?? $selected->site?->address);
                            $customerName = $selected->clientAccount?->name ?? $patriotPayload['customer_name'] ?? null;
                            $incidentTime = trim(($selected->incident_date?->format('M j, Y') ?? '').' '.$selected->incident_time);
                        @endphp
                        <section x-show="panel === 'overview'" aria-label="Job overview">
                            <div class="dispatch-overview">
                                <div class="dispatch-event-summary space-y-2 rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-800">
                                    <h3 class="font-semibold">{{ $patriotPayload['alarm_description'] ?? ($incidentTypes[$selected->event_type] ?? $selected->event_type) }}</h3>
                                    @if ($selected->description && $selected->description !== ($patriotPayload['alarm_description'] ?? null))<p>{{ $selected->description }}</p>@endif
                                    @if ($location || $incidentTime || $selected->caller_name)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ collect([$location, $incidentTime, $selected->caller_name])->filter()->join(' · ') }}</p>
                                    @endif
                                </div>

                                @if (! empty($mapMarkers))
                                    <div class="min-w-0">
                                        <x-map
                                            id="dispatch-detail-map-{{ $selected->id }}"
                                            :lat="$mapMarkers[0]['lat']"
                                            :lng="$mapMarkers[0]['lng']"
                                            :markers="$mapMarkers"
                                            :fit-bounds="false"
                                            height="180px"
                                        />
                                    </div>
                                @endif
                            </div>
                            @if ($selected->opened_at)
                                @php
                                    $timerEnd = $selected->on_scene_at ?? $selected->resolved_at ?? $selected->closed_at ?? now();
                                    $elapsed = max(0, (int) $selected->opened_at->diffInSeconds($timerEnd));
                                @endphp
                                <div class="dispatch-timer">
                                    <x-nav-icon name="attendance" class="size-5 shrink-0" />
                                    <div>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $selected->on_scene_at ? 'Time to arrive' : ($selected->closed_at || $selected->resolved_at ? 'Elapsed before closure' : 'Elapsed since opened') }}</p>
                                        <p class="font-semibold tabular-nums">{{ sprintf('%02d:%02d:%02d', intdiv($elapsed, 3600), intdiv($elapsed % 3600, 60), $elapsed % 60) }}</p>
                                    </div>
                                </div>
                            @endif
                        </section>
                        <section x-show="panel === 'site'" x-cloak aria-label="Site details" class="space-y-3">
                            <div class="dispatch-tab-card space-y-2">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <h3 class="font-semibold">{{ $selected->site?->name ?? 'Site not linked' }}</h3>
                                        @if ($customerName && $customerName !== $selected->site?->name)
                                            <p>{{ $customerName }}</p>
                                        @endif
                                    </div>
                                    @if ($trackingUrl)
                                        <a href="{{ $trackingUrl }}" class="page-link">{{ empty($mapMarkers) ? 'Track responder' : 'View on live map' }}</a>
                                    @endif
                                </div>
                                @if ($location)
                                    <p class="text-zinc-500 dark:text-zinc-400">{{ $location }}</p>
                                @elseif (! empty($mapMarkers))
                                    <p class="text-zinc-500 dark:text-zinc-400">GPS {{ $mapMarkers[0]['lat'] }}, {{ $mapMarkers[0]['lng'] }}</p>
                                @endif
                            </div>

                            @if ($selected->patriot_job_id !== null)
                                <div class="dispatch-tab-card space-y-2">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="font-semibold">Patriot job #{{ $selected->patriot_job_id }}</h3>
                                        @can('sites.manage')
                                            @if ($selected->patriot_instance && $selected->patriot_client_ref)
                                                <x-button size="sm" variant="secondary" wire:click="refreshPatriotAccount" wire:loading.attr="disabled" wire:target="refreshPatriotAccount">Refresh Patriot account</x-button>
                                            @endif
                                        @endcan
                                    </div>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        @if ($selected->site_id)
                                            <strong>Site linked</strong> ·
                                        @endif
                                        {{ match ($selected->patriot_sync_status) {
                                            'synchronized' => 'Customer synchronized',
                                            'verified' => 'Customer verified',
                                            'failed' => 'Customer lookup failed',
                                            'review' => 'Customer identity under review',
                                            default => $selected->site_id && $selected->patriot_sync_status === null ? 'Customer verified' : 'Customer details pending',
                                        } }}
                                    </p>
                                    @php
                                        $patriotFields = [
                                            'Activation ticket' => $patriotPayload['activation_id'] ?? null,
                                            'Account' => $patriotPayload['account_number'] ?? null,
                                            'Client reference' => $selected->patriot_client_ref,
                                            'Alarm category' => $patriotPayload['dispatch_category'] ?? null,
                                            'Priority code' => $patriotPayload['priority'] ?? null,
                                            'Dispatched at' => isset($patriotPayload['dispatch_time'])
                                                ? \Illuminate\Support\Carbon::parse($patriotPayload['dispatch_time'])->utc()->format('j M Y, H:i \U\T\C')
                                                : null,
                                        ];
                                    @endphp
                                    <dl class="dispatch-patriot-fields">
                                        @foreach ($patriotFields as $label => $value)
                                            @if ($value !== null)
                                                <div>
                                                    <dt>{{ $label }}</dt>
                                                    <dd>{{ $value }}</dd>
                                                </div>
                                            @endif
                                        @endforeach
                                    </dl>
                                </div>
                                @if ($patriotResponseDetails !== [])
                                    <div class="dispatch-tab-card space-y-2">
                                        <h3 class="font-semibold">Patriot response contacts</h3>
                                        @if ($patriotResponseDetails['site_phone'] ?? null)
                                            <p>Site phone {{ $patriotResponseDetails['site_phone'] }}</p>
                                        @endif
                                        @if (($patriotResponseDetails['contact_sync_status'] ?? null) === 'access_denied')
                                            <p>Patriot denied access to the response call list. The integration user needs call-list read access.</p>
                                        @elseif (($patriotResponseDetails['contact_sync_status'] ?? null) === 'unavailable')
                                            <p>Patriot call list is unavailable.</p>
                                        @elseif (($patriotResponseDetails['contact_sync_status'] ?? null) === 'partial')
                                            <p>Patriot returned a partial call list.</p>
                                        @elseif (($patriotResponseDetails['contact_sync_status'] ?? null) === 'complete' && empty($patriotResponseDetails['contacts']))
                                            <p>Patriot returned an empty call list.</p>
                                        @elseif (! isset($patriotResponseDetails['contact_sync_status']))
                                            <p>Patriot call list has not been retrieved.</p>
                                        @endif
                                        @if (! empty($patriotResponseDetails['contacts']))
                                            <ul class="space-y-1">
                                                @foreach ($patriotResponseDetails['contacts'] as $contact)
                                                    <li>
                                                        <span class="font-medium">{{ $contact['name'] ?? 'Response contact' }}</span>
                                                        @foreach (['telephone' => 'Phone', 'mobile' => 'Mobile', 'after_hours' => 'After hours'] as $field => $label)
                                                            @if (isset($contact[$field]))
                                                                <span class="block">{{ $label }} {{ $contact[$field] }}</span>
                                                            @endif
                                                        @endforeach
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endif
                                @if ($patriotPayload['instructions'] ?? null)
                                    <div class="dispatch-tab-card">
                                        <h3 class="font-semibold">Dispatch instructions</h3>
                                        <p class="whitespace-pre-wrap break-words">{{ $patriotPayload['instructions'] }}</p>
                                    </div>
                                @endif
                            @endif
                        </section>

                        <section x-show="panel === 'notes'" x-cloak aria-label="Notes and files" class="dispatch-tab-card space-y-3">
                            @if ($patriotPayload['job_notes'] ?? null)
                                <div>
                                    <h3 class="font-semibold">Patriot job notes</h3>
                                    <p class="whitespace-pre-wrap break-words">{{ $patriotPayload['job_notes'] }}</p>
                                </div>
                            @endif
                            @if ($selected->attachment_path)
                                <button type="button" wire:click="downloadAttachment" class="page-link">Download attachment</button>
                            @endif
                            <form wire:submit="saveDetail" class="grid gap-3">
                            <x-textarea wire:model.live="detail.action_taken" label="Action taken" rows="2" />
                            <x-textarea wire:model.live="detail.internal_notes" label="Internal notes" rows="2" />
                            <x-file-input wire:model.live="attachmentFile" label="Attachment" />
                            <x-button type="submit" size="sm" variant="secondary">Save notes</x-button>
                            </form>
                        </section>

                    </div>
                @else
                    <div class="flex flex-1 items-center justify-center py-8">
                        <x-empty-state compact title="Select a dispatch" description="Choose a dispatch from the queue to view details and take action." />
                    </div>
                @endif
            </x-section-card>
        </div>
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="New dispatch" description="Assign a guard and capture the call so the response team can act." width="lg">
            <x-drawer-form wire:submit="save" submit-label="Submit dispatch">
                @if ($errors->any())
                    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 sm:col-span-2 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200">
                        <p class="font-medium">Please fix the following:</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <x-form-section title="Assignment">
                    <x-select wire:model.live="form.client_account_id" label="Client *" class="sm:col-span-2">
                        <option value="">Select client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </x-select>

                    <x-select wire:model.live="form.site_id" label="Post site *" class="sm:col-span-2" :disabled="! $form['client_account_id']">
                        <option value="">Select site</option>
                        @foreach($sitesForClient as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>

                    <x-select wire:model.live="form.guard_id" label="Assign guard" class="sm:col-span-2">
                        <option value="">Unassigned</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>

                    <x-select wire:model.live="form.priority" label="Priority *">
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </x-select>
                </x-form-section>

                <x-form-section title="Caller">
                    <x-select wire:model.live="form.caller_type" label="Caller type *">
                        @foreach($callerTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model.live="form.caller_name" label="Caller name *" />
                </x-form-section>

                <x-form-section title="Incident">
                    <x-input wire:model.live="form.incident_location" label="Incident location *" class="sm:col-span-2" />
                    <x-select wire:model.live="form.event_type" label="Incident type *" class="sm:col-span-2">
                        <option value="">Select type</option>
                        @foreach($incidentTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model.live="form.incident_date" type="date" label="Incident date *" />
                    <x-input wire:model.live="form.incident_time" type="time" label="Incident time *" />
                    <x-textarea wire:model.live="form.description" label="Incident details *" rows="4" class="sm:col-span-2" />
                </x-form-section>

                <x-form-section title="Follow-up" description="Optional notes and evidence for the response record.">
                    <x-textarea wire:model.live="form.action_taken" label="Action taken" rows="3" class="sm:col-span-2" />
                    <x-textarea wire:model.live="form.internal_notes" label="Internal notes" rows="3" class="sm:col-span-2" />
                    <x-file-input wire:model.live="attachmentFile" label="Attachment" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($confirmingCancel && $selected)
        <x-modal title="Cancel dispatch" :description="'Cancel '.$selected->dispatch_number.'? This cannot be undone.'" closeMethod="closeCancelConfirm">
            <div class="flex justify-end gap-2 p-1">
                <x-button type="button" variant="secondary" wire:click="closeCancelConfirm">Keep open</x-button>
                <x-button type="button" variant="danger" wire:click="cancelDispatch">Cancel dispatch</x-button>
            </div>
        </x-modal>
    @endif
</div>

@script
<script>
    if (window.Echo && @json(auth()->user()?->tenant_id)) {
        window.Echo.private('tenant.{{ auth()->user()->tenant_id }}.dispatch')
            .listen('.dispatch.created', () => {
                if (!$wire.showForm) {
                    $wire.$refresh();
                }
            })
            .listen('.dispatch.updated', () => $wire.$refresh())
            .listen('.sos.raised', () => $wire.$refresh());
    }
</script>
@endscript
