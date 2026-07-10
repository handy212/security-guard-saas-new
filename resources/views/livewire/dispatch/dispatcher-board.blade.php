<div>
    <x-page-shell title="Dispatch Center" description="Create dispatches, assign guards, and track response status.">
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
            <section class="mb-4 rounded-xl border border-red-300 bg-red-50 p-4 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-600"></span>
                    </span>
                    <h2 class="text-sm font-semibold text-red-900">SOS alerts require response</h2>
                </div>
                <div class="space-y-3">
                    @foreach($sosAlerts as $alert)
                        <div class="flex flex-wrap items-start justify-between gap-3 border-t border-red-200/80 pt-3 first:border-0 first:pt-0" wire:key="sos-{{ $alert->id }}">
                            <div>
                                <div class="font-semibold text-red-900">{{ $alert->assignedGuard?->full_name ?? 'Guard' }}</div>
                                <div class="text-xs text-red-700">{{ $alert->site?->name }} · {{ $alert->message ?? 'SOS' }} · {{ $alert->raised_at?->diffForHumans() }}</div>
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
            <x-section-card title="Dispatch queue" class="flex flex-col">
                <div class="-mx-1 flex-1 overflow-y-auto px-1">
                    @forelse($dispatches as $dispatch)
                        @php
                            $priorityTone = match ($dispatch->priority->value ?? '') {
                                'critical' => 'border-l-red-500',
                                'high' => 'border-l-amber-500',
                                'low' => 'border-l-zinc-300',
                                default => 'border-l-sky-400',
                            };
                        @endphp
                        <button
                            type="button"
                            wire:click="selectDispatch({{ $dispatch->id }})"
                            class="w-full rounded-lg border border-transparent border-l-4 {{ $priorityTone }} px-2 py-3 text-left text-sm transition hover:border-zinc-200 hover:bg-zinc-50 {{ $selectedId === $dispatch->id ? 'border-accent-200 bg-accent-50' : '' }}"
                            wire:key="dispatch-{{ $dispatch->id }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="font-semibold text-zinc-900">{{ $dispatch->dispatch_number ?? '#'.$dispatch->id }}</div>
                                    <div class="truncate text-xs text-zinc-500">{{ $dispatch->clientAccount?->name ?? '—' }} · {{ $dispatch->site?->name }}</div>
                                    <div class="mt-1 truncate text-xs text-zinc-600">{{ $dispatch->caller_name }} — {{ $dispatch->incident_location }}</div>
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
                        <x-empty-state compact title="No dispatches" description="Create a new dispatch to get started." />
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
                                <a href="{{ $trackingUrl }}" class="text-xs font-medium text-accent-600 hover:underline">View on live map</a>
                            @endif
                        </div>

                        <div class="mb-4 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm">
                            <div class="font-medium text-zinc-900">{{ $selected->assignedGuard?->full_name ?? 'Unassigned' }}</div>
                            <div class="text-xs text-zinc-500">{{ $selected->clientAccount?->name ?? '—' }} · {{ $selected->site?->name ?? '—' }}</div>
                        </div>

                        <ol class="mb-4 grid gap-2 sm:grid-cols-3">
                            @foreach ($timeline as $step)
                                <li class="rounded-lg border px-2 py-1.5 text-xs {{ $step['at'] ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-zinc-200 bg-white text-zinc-400' }}">
                                    <div class="font-semibold">{{ $step['label'] }}</div>
                                    <div>{{ $step['at']?->format('M j, H:i') ?? 'Pending' }}</div>
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

                        <dl class="mb-4 grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Caller</dt>
                                <dd class="mt-0.5 font-medium text-zinc-900">{{ ucfirst($selected->caller_type) }} — {{ $selected->caller_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Incident</dt>
                                <dd class="mt-0.5 font-medium text-zinc-900">{{ $incidentTypes[$selected->event_type] ?? $selected->event_type }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Location</dt>
                                <dd class="mt-0.5 font-medium text-zinc-900">{{ $selected->incident_location }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">When</dt>
                                <dd class="mt-0.5 font-medium text-zinc-900">{{ $selected->incident_date?->format('M j, Y') }} {{ $selected->incident_time }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Details</dt>
                                <dd class="mt-0.5 text-zinc-700">{{ $selected->description ?: '—' }}</dd>
                            </div>
                            @if ($selected->attachment_path)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Attachment</dt>
                                    <dd class="mt-0.5">
                                        <button type="button" wire:click="downloadAttachment" class="text-sm font-medium text-accent-600 hover:underline">Download attachment</button>
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        @if($selected->isActive())
                            <div class="mb-4 space-y-2 rounded-lg border border-zinc-200 bg-zinc-50 p-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Actions</p>
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
                                        · <a href="{{ route('incidents.index') }}" class="font-medium text-accent-600 hover:underline">Open incidents</a>
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

                        <div class="mt-4 border-t border-zinc-100 pt-3">
                            <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Activity</h4>
                            @forelse($selected->activityLogs as $log)
                                <div class="border-t border-zinc-100 py-2 text-xs first:border-0" wire:key="log-{{ $log->id }}">
                                    <span class="font-medium text-zinc-800">{{ $log->user?->name ?? 'System' }}</span>
                                    <span class="text-zinc-500"> — {{ $log->message }}</span>
                                    <div class="text-[10px] text-zinc-400">{{ $log->created_at->format('M j, Y H:i') }} · {{ $log->created_at->diffForHumans() }}</div>
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
        <x-drawer title="New dispatch" width="lg">
            <x-drawer-form wire:submit.prevent="save" submit-label="Submit dispatch">
                @if ($errors->any())
                    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 sm:col-span-2">
                        <p class="font-medium">Please fix the following:</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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

                <x-select wire:model="form.caller_type" label="Caller type *">
                    @foreach($callerTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>

                <x-input wire:model="form.caller_name" label="Caller name *" class="sm:col-span-2" />

                <div class="sm:col-span-2 rounded-lg border border-zinc-200 bg-zinc-50 p-3">
                    <h3 class="mb-3 text-sm font-semibold text-zinc-800">Incident details</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
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
                    </div>
                </div>

                <x-textarea wire:model="form.action_taken" label="Action taken" rows="3" class="sm:col-span-2" />
                <x-textarea wire:model="form.internal_notes" label="Internal notes" rows="3" class="sm:col-span-2" />
                <x-file-input wire:model="attachmentFile" label="Attachment" class="sm:col-span-2" />
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
