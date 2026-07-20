<div>
    <x-page-shell
        title="Dispatch Center"
        description="Create dispatches, assign guards, and track response status."
        :breadcrumbs="[['label' => 'Dispatch']]"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('tracking.live')">Live map</x-button>
            <x-button wire:click="openForm">New dispatch</x-button>
        </x-slot:actions>

        <div class="stat-grid">
            <x-stat-card compact label="Active" :value="$stats['active']" icon="dispatch" :tone="$stats['active'] ? 'warning' : 'success'" wire:click="applyStatFilter('active')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'active' && $priorityFilter === 'all'" />
            <x-stat-card compact label="Critical" :value="$stats['critical']" icon="incidents" :tone="$stats['critical'] ? 'danger' : 'default'" wire:click="applyStatFilter('critical')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$priorityFilter === 'critical'" />
            <x-stat-card compact label="En route" :value="$stats['en_route']" icon="gps" tone="info" wire:click="applyStatFilter('en_route')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'en_route'" />
            <x-stat-card compact label="Open SOS" :value="$stats['sos']" icon="incidents" :tone="$stats['sos'] ? 'danger' : 'success'" wire:click="applyStatFilter('sos')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$priorityFilter === 'critical' && $stats['sos'] > 0" />
        </div>

        <x-flash-status />

        <x-page-toolbar search="search" searchPlaceholder="Search dispatches…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['active' => 'Active', 'all' => 'All', 'en_route' => 'En route', 'closed' => 'Closed']" />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="table-action">Clear filters</button>
                @endif
                <x-filter-select wire:model.live="priorityFilter">
                    <option value="all">All priority</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="normal">Normal</option>
                    <option value="low">Low</option>
                </x-filter-select>
            </x-slot:controls>
        </x-page-toolbar>

        @if($sosAlerts->isNotEmpty())
            <section class="card-surface overflow-hidden border-red-200/90 dark:border-red-900/50">
                <div class="card-header border-red-100 bg-red-50/80 dark:border-red-900/40 dark:bg-red-950/40">
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                        </span>
                        <div>
                            <h2 class="card-header-title text-red-900 dark:text-red-100">SOS alerts require response</h2>
                            <p class="card-header-meta text-red-700/90 dark:text-red-300">{{ $sosAlerts->count() }} open · acknowledge or dispatch now</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-red-100 dark:divide-red-900/40">
                    @foreach($sosAlerts as $alert)
                        <div class="flex flex-wrap items-start justify-between gap-3 bg-red-50/40 px-4 py-3 dark:bg-red-950/20" wire:key="sos-{{ $alert->id }}">
                            <div class="min-w-0">
                                <div class="font-semibold text-red-900 dark:text-red-100">{{ $alert->assignedGuard?->full_name ?? 'Guard' }}</div>
                                <div class="text-xs text-red-700 dark:text-red-300">{{ $alert->site?->name }} · {{ $alert->message ?? 'SOS' }} · {{ $alert->raised_at?->diffForHumans() }}</div>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if($alert->status === 'open')
                                    <x-button size="sm" variant="danger" wire:click="acknowledgeSos({{ $alert->id }})">Ack</x-button>
                                @endif
                                <x-button size="sm" wire:click="dispatchFromSos({{ $alert->id }})">Dispatch</x-button>
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

        <div class="page-board min-h-[28rem]">
            <x-section-card title="Dispatch queue" flush class="flex flex-col">
                <div class="flex-1 overflow-y-auto px-2 py-2">
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
                            wire:click="selectDispatch({{ $dispatch->id }})"
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
                                    <div class="board-item-meta">{{ $dispatch->clientAccount?->name ?? '—' }} · {{ $dispatch->site?->name }}</div>
                                    <div class="mt-1 truncate text-xs text-zinc-600 dark:text-zinc-300">{{ $dispatch->caller_name }} — {{ $dispatch->incident_location }}</div>
                                    <div class="mt-1 text-[11px] text-zinc-400">
                                        {{ $dispatch->assignedGuard?->full_name ?? 'Unassigned' }}
                                        · {{ $dispatch->opened_at?->diffForHumans() ?? $dispatch->created_at?->diffForHumans() }}
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    <x-badge :status="$dispatch->priority->value" :map="['critical'=>'danger','high'=>'warning','normal'=>'info','low'=>'neutral']" />
                                    <x-badge :status="$dispatch->status->value" :map="['open'=>'info','assigned'=>'info','en_route'=>'warning','on_scene'=>'warning','resolved'=>'success','closed'=>'neutral','cancelled'=>'danger']" />
                                </div>
                            </div>
                        </button>
                    @empty
                        <x-empty-state compact title="No dispatches" description="Create a dispatch when a client or site needs response.">
                            <x-slot:actions>
                                <x-button size="sm" wire:click="openForm">New dispatch</x-button>
                                <x-button size="sm" variant="secondary" :href="route('schedules.index')">Day roster</x-button>
                            </x-slot:actions>
                        </x-empty-state>
                    @endforelse
                </div>
            </x-section-card>

            <x-section-card
                :title="$selected ? ($selected->dispatch_number ?? 'Dispatch #'.$selected->id) : 'Dispatch detail'"
                class="flex flex-col"
            >
                @if($selected)
                    <div class="flex-1 overflow-y-auto">
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <x-badge :status="$selected->priority->value" :map="['critical'=>'danger','high'=>'warning','normal'=>'info','low'=>'neutral']" />
                            <x-badge :status="$selected->status->value" :map="['open'=>'info','assigned'=>'info','en_route'=>'warning','on_scene'=>'warning','resolved'=>'success','closed'=>'neutral','cancelled'=>'danger']" />
                            @if ($trackingUrl)
                                <a href="{{ $trackingUrl }}" class="page-link">View on live map</a>
                            @endif
                        </div>

                        <div class="meta-tile mb-4">
                            <div class="meta-tile-value">{{ $selected->assignedGuard?->full_name ?? 'Unassigned' }}</div>
                            <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $selected->clientAccount?->name ?? '—' }} · {{ $selected->site?->name ?? '—' }}</div>
                        </div>

                        <ol class="mb-4 grid gap-2 sm:grid-cols-3">
                            @foreach ($timeline as $step)
                                <li @class(['timeline-step', $step['at'] ? 'timeline-step-done' : 'timeline-step-pending'])>
                                    <div class="font-semibold">{{ $step['label'] }}</div>
                                    <div class="tabular-nums">{{ $step['at']?->format('M j, H:i') ?? 'Pending' }}</div>
                                </li>
                            @endforeach
                        </ol>

                        @if (! empty($mapMarkers))
                            <div class="mb-4">
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

                        <dl class="mb-4 grid gap-2 text-sm sm:grid-cols-2">
                            <div class="meta-tile">
                                <dt class="meta-tile-label">Caller</dt>
                                <dd class="meta-tile-value">{{ ucfirst($selected->caller_type) }} — {{ $selected->caller_name }}</dd>
                            </div>
                            <div class="meta-tile">
                                <dt class="meta-tile-label">Incident</dt>
                                <dd class="meta-tile-value">{{ $incidentTypes[$selected->event_type] ?? $selected->event_type }}</dd>
                            </div>
                            <div class="meta-tile sm:col-span-2">
                                <dt class="meta-tile-label">Location</dt>
                                <dd class="meta-tile-value">{{ $selected->incident_location }}</dd>
                            </div>
                            <div class="meta-tile">
                                <dt class="meta-tile-label">When</dt>
                                <dd class="meta-tile-value tabular-nums">{{ $selected->incident_date?->format('M j, Y') }} {{ $selected->incident_time }}</dd>
                            </div>
                            <div class="meta-tile sm:col-span-2">
                                <dt class="meta-tile-label">Details</dt>
                                <dd class="mt-0.5 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $selected->description ?: '—' }}</dd>
                            </div>
                            @if ($selected->attachment_path)
                                <div class="meta-tile sm:col-span-2">
                                    <dt class="meta-tile-label">Attachment</dt>
                                    <dd class="mt-0.5">
                                        <button type="button" wire:click="downloadAttachment" class="page-link text-sm">Download attachment</button>
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        @if($selected->isActive())
                            <div class="mb-4 space-y-2 rounded-md border border-zinc-200/90 bg-zinc-50/80 p-3 dark:border-zinc-800 dark:bg-zinc-900/50">
                                <p class="meta-tile-label">Actions</p>
                                <x-select wire:model="assignGuardId" label="Assign guard">
                                    <option value="">Select guard</option>
                                    @foreach($guards as $guard)
                                        <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                                    @endforeach
                                </x-select>
                                @error('assignGuardId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                <div class="flex flex-wrap gap-2">
                                    <x-button size="sm" variant="secondary" wire:click="assignGuard" :disabled="! $assignGuardId">Assign guard</x-button>
                                    @if($selected->status->next())
                                        <x-button size="sm" wire:click="advanceStatus">
                                            Mark {{ strtolower($selected->status->next()->label()) }}
                                        </x-button>
                                    @endif
                                    @can('incidents.manage')
                                        @if (! $selected->incident_id)
                                            <x-button size="sm" variant="secondary" wire:click="promoteToIncident">Create incident</x-button>
                                        @endif
                                    @endcan
                                    <x-button size="sm" variant="danger" wire:click="confirmCancel">Cancel</x-button>
                                </div>
                                @if ($selected->incident_id)
                                    <p class="text-xs text-zinc-500">
                                        Linked incident #{{ $selected->incident_id }}
                                        · <a href="{{ route('incidents.index') }}" class="page-link">Open incidents</a>
                                    </p>
                                @endif
                            </div>
                        @endif

                        <form wire:submit="saveDetail" class="space-y-2 border-t border-zinc-100 pt-3">
                            <x-textarea wire:model="detail.action_taken" label="Action taken" rows="2" />
                            <x-textarea wire:model="detail.internal_notes" label="Internal notes" rows="2" />
                            <x-file-input wire:model="attachmentFile" label="Attachment" />
                            <x-button type="submit" size="sm" variant="secondary">Save notes</x-button>
                        </form>

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
            <x-drawer-form wire:submit.prevent="save" submit-label="Submit dispatch">
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

                    <x-select wire:model="form.site_id" label="Post site *" class="sm:col-span-2" :disabled="! $form['client_account_id']">
                        <option value="">Select site</option>
                        @foreach($sitesForClient as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>

                    <x-select wire:model="form.guard_id" label="Assign guard" class="sm:col-span-2">
                        <option value="">Unassigned</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>

                    <x-select wire:model="form.priority" label="Priority *">
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </x-select>
                </x-form-section>

                <x-form-section title="Caller">
                    <x-select wire:model="form.caller_type" label="Caller type *">
                        @foreach($callerTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="form.caller_name" label="Caller name *" />
                </x-form-section>

                <x-form-section title="Incident">
                    <x-input wire:model="form.incident_location" label="Incident location *" class="sm:col-span-2" />
                    <x-select wire:model="form.event_type" label="Incident type *" class="sm:col-span-2">
                        <option value="">Select type</option>
                        @foreach($incidentTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="form.incident_date" type="date" label="Incident date *" />
                    <x-input wire:model="form.incident_time" type="time" label="Incident time *" />
                    <x-textarea wire:model="form.description" label="Incident details *" rows="4" class="sm:col-span-2" />
                </x-form-section>

                <x-form-section title="Follow-up" description="Optional notes and evidence for the response record.">
                    <x-textarea wire:model="form.action_taken" label="Action taken" rows="3" class="sm:col-span-2" />
                    <x-textarea wire:model="form.internal_notes" label="Internal notes" rows="3" class="sm:col-span-2" />
                    <x-file-input wire:model="attachmentFile" label="Attachment" class="sm:col-span-2" />
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
        window.Echo.channel('tenant.{{ auth()->user()->tenant_id }}.dispatch')
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
