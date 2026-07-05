<div>
    <x-page-shell title="Dispatch Center" description="Create dispatches, assign guards, and track response status.">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('tracking.live') }}">Live map</x-button>
            <x-button wire:click="openForm">New dispatch</x-button>
        </x-slot:actions>

        <div class="stat-grid">
            <x-stat-card compact label="Active" :value="$stats['active']" icon="dispatch" :tone="$stats['active'] ? 'warning' : 'success'" />
            <x-stat-card compact label="Critical" :value="$stats['critical']" icon="incidents" :tone="$stats['critical'] ? 'danger' : 'default'" />
            <x-stat-card compact label="En route" :value="$stats['en_route']" icon="gps" tone="info" />
            <x-stat-card compact label="Open SOS" :value="$stats['sos']" icon="incidents" :tone="$stats['sos'] ? 'danger' : 'success'" />
        </div>

        <x-flash-status />

        <x-page-toolbar search="search" searchPlaceholder="Search dispatches…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['active' => 'Active', 'all' => 'All', 'closed' => 'Closed']" />
            </x-slot:tabs>
            <x-slot:controls>
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
            <x-section-card title="SOS Alerts" class="border-red-200 bg-red-50/50">
                @foreach($sosAlerts as $alert)
                    <div class="flex items-start justify-between gap-2 border-t border-red-100 py-2 text-sm first:border-0" wire:key="sos-{{ $alert->id }}">
                        <div>
                            <div class="font-semibold text-red-800">{{ $alert->assignedGuard?->full_name ?? 'Guard' }}</div>
                            <div class="text-xs text-red-600">{{ $alert->site?->name }} · {{ $alert->message ?? 'SOS' }}</div>
                        </div>
                        <div class="flex shrink-0 flex-col gap-1">
                            @if($alert->status === 'open')
                                <x-button size="sm" variant="danger" wire:click="acknowledgeSos({{ $alert->id }})">Ack</x-button>
                            @endif
                            <x-button size="sm" variant="secondary" wire:click="dispatchFromSos({{ $alert->id }})">Dispatch</x-button>
                        </div>
                    </div>
                @endforeach
            </x-section-card>
        @endif

        <div class="grid gap-4 lg:grid-cols-5 lg:items-stretch">
            <x-section-card title="Dispatch queue" class="flex min-h-[28rem] flex-col lg:col-span-2">
                <div class="-mx-1 flex-1 overflow-y-auto px-1">
                    @forelse($dispatches as $dispatch)
                        <button
                            type="button"
                            wire:click="selectDispatch({{ $dispatch->id }})"
                            class="w-full rounded-lg border border-transparent px-2 py-3 text-left text-sm transition hover:border-zinc-200 hover:bg-zinc-50 {{ $selectedId === $dispatch->id ? 'border-accent-200 bg-accent-50' : '' }}"
                            wire:key="dispatch-{{ $dispatch->id }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="font-semibold text-zinc-900">{{ $dispatch->dispatch_number ?? '#'.$dispatch->id }}</div>
                                    <div class="truncate text-xs text-zinc-500">{{ $dispatch->clientAccount?->name }} · {{ $dispatch->site?->name }}</div>
                                    <div class="mt-1 truncate text-xs text-zinc-600">{{ $dispatch->caller_name }} — {{ $dispatch->incident_location }}</div>
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
                class="flex min-h-[28rem] flex-col lg:col-span-3"
            >
                @if($selected)
                    <div class="flex-1 overflow-y-auto">
                        <div class="mb-3 flex flex-wrap gap-2">
                            <x-badge :status="$selected->priority->value" :map="['critical'=>'danger','high'=>'warning','normal'=>'info','low'=>'neutral']" />
                            <x-badge :status="$selected->status->value" :map="['open'=>'info','assigned'=>'info','en_route'=>'warning','on_scene'=>'warning','resolved'=>'success','closed'=>'neutral','cancelled'=>'danger']" />
                        </div>

                        <dl class="mb-4 grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Client</dt>
                                <dd class="mt-0.5 font-medium text-zinc-900">{{ $selected->clientAccount?->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500">Site</dt>
                                <dd class="mt-0.5 font-medium text-zinc-900">{{ $selected->site?->name }}</dd>
                            </div>
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
                                <div class="flex flex-wrap gap-2">
                                    <x-button size="sm" variant="secondary" wire:click="assignGuard">Assign guard</x-button>
                                    @if($selected->status->next())
                                        <x-button size="sm" wire:click="advanceStatus">
                                            Mark {{ strtolower($selected->status->next()->label()) }}
                                        </x-button>
                                    @endif
                                    <x-button size="sm" variant="danger" wire:click="cancelDispatch">Cancel</x-button>
                                </div>
                            </div>
                        @endif

                        <form wire:submit="saveDetail" class="space-y-2 border-t border-zinc-100 pt-3">
                            <x-textarea wire:model="detail.action_taken" label="Action taken" rows="2" />
                            <x-textarea wire:model="detail.internal_notes" label="Internal notes" rows="2" />
                            <x-file-input wire:model="attachmentFile" label="Attachment" />
                            <x-button type="submit" size="sm" variant="secondary">Save notes</x-button>
                        </form>

                        @if($selected->activityLogs->isNotEmpty())
                            <div class="mt-4 border-t border-zinc-100 pt-3">
                                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Activity</h4>
                                <div class="max-h-40 space-y-2 overflow-y-auto text-xs">
                                    @foreach($selected->activityLogs as $log)
                                        <div wire:key="log-{{ $log->id }}">
                                            <span class="font-medium">{{ $log->user?->name ?? 'System' }}</span>
                                            <span class="text-zinc-500"> — {{ $log->message }}</span>
                                            <div class="text-[10px] text-zinc-400">{{ $log->created_at->diffForHumans() }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
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
