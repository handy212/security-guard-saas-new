<div>
    <x-page-shell
        title="Incident Reports"
        description="Log, review, and resolve security incidents."
        :breadcrumbs="[['label' => 'Incidents']]"
    >
        <x-slot:actions>
            <x-button wire:click="openCreate">Report incident</x-button>
            <x-button variant="secondary" wire:click="$set('showMediaForm', true)">Attach media</x-button>
        </x-slot:actions>

        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$incidentStats['total']" icon="incidents" wire:click="applyStatFilter('total')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'all' && $severityFilter === 'all' && $search === ''" />
            <x-stat-card compact label="Open" :value="$incidentStats['open']" icon="pause" :tone="$incidentStats['open'] > 0 ? 'warning' : 'default'" wire:click="applyStatFilter('open')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'open'" />
            <x-stat-card compact label="High risk" :value="$incidentStats['critical']" icon="incidents" :tone="$incidentStats['critical'] > 0 ? 'danger' : 'default'" wire:click="applyStatFilter('critical')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$severityFilter === 'high_risk'" />
            <x-stat-card compact label="Closed" :value="$incidentStats['closed']" icon="check" tone="success" wire:click="applyStatFilter('closed')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'closed'" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search incidents…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'open' => 'Open', 'submitted' => 'Submitted', 'approved' => 'Approved', 'closed' => 'Closed', 'rejected' => 'Rejected']" />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="table-action">Clear filters</button>
                @endif
                <x-filter-select wire:model.live="severityFilter">
                    <option value="all">All severity</option>
                    <option value="high_risk">High risk</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </x-filter-select>
            </x-slot:controls>
        </x-page-toolbar>

        <div class="page-board min-h-[28rem]">
            <x-section-card title="Incident queue" flush class="flex flex-col">
                <div class="flex flex-1 flex-col overflow-hidden">
                <div class="flex-1 overflow-y-auto px-2 py-2">
                    @forelse($incidents as $incident)
                        <button
                            type="button"
                            wire:click="viewIncident({{ $incident->id }})"
                            class="mb-1 w-full rounded-lg border border-transparent border-l-4 px-2 py-3 text-left text-sm transition hover:border-zinc-200 hover:bg-zinc-50 {{ $viewingIncidentId === $incident->id ? 'border-accent-200 bg-accent-50' : '' }} {{ match($incident->severity) { 'critical' => 'border-l-red-500', 'high' => 'border-l-amber-500', default => 'border-l-zinc-300' } }}"
                            wire:key="incident-{{ $incident->id }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="font-semibold text-zinc-900">{{ $incident->title }}</div>
                                    <div class="truncate text-xs text-zinc-500">{{ $incident->site?->name ?? '—' }} · {{ $incidentTypes[$incident->type ?? $incident->incident_type] ?? ($incident->type ?? $incident->incident_type) }}</div>
                                    <div class="mt-1 text-[11px] text-zinc-400">{{ $incident->reported_at?->diffForHumans() ?? $incident->created_at?->diffForHumans() }}</div>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    <x-badge :status="$incident->severity" :map="['low'=>'neutral','medium'=>'info','high'=>'warning','critical'=>'danger']" />
                                    <x-badge :status="$incident->status" />
                                </div>
                            </div>
                        </button>
                    @empty
                        <x-empty-state
                            compact
                            :title="$hasActiveFilters ? 'No matching incidents' : 'No incidents'"
                            :description="$hasActiveFilters ? 'Try adjusting your filters.' : 'Report an incident to start the review workflow.'"
                        >
                            <x-slot:actions>
                                @if (! $hasActiveFilters)
                                    <x-button size="sm" wire:click="openCreate">Report incident</x-button>
                                @endif
                            </x-slot:actions>
                        </x-empty-state>
                    @endforelse
                </div>
                <div class="border-t border-zinc-100 px-2 dark:border-zinc-800">
                    <x-pagination :paginator="$incidents" />
                </div>
                </div>
            </x-section-card>

            <x-section-card
                :title="$viewingIncident ? $viewingIncident->title : 'Incident detail'"
                class="flex flex-col"
            >
                @if ($viewingIncident)
                    <div class="flex-1 space-y-4 overflow-y-auto">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :status="$viewingIncident->severity" :map="['low'=>'neutral','medium'=>'info','high'=>'warning','critical'=>'danger']" />
                            <x-badge :status="$viewingIncident->status" />
                            @if ($viewingIncident->site)
                                <span class="text-xs text-zinc-500">{{ $viewingIncident->site->name }}</span>
                            @endif
                        </div>

                        <div class="rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2.5 text-sm dark:border-zinc-800 dark:bg-zinc-900/60">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $incidentTypes[$viewingIncident->type ?? $viewingIncident->incident_type] ?? ($viewingIncident->type ?? 'Incident') }}
                            </div>
                            <div class="text-xs text-zinc-500">
                                Reported by {{ $viewingIncident->reportedBy?->name ?? '—' }}
                                · {{ $viewingIncident->reported_at?->format('M j, Y H:i') ?? $viewingIncident->created_at?->format('M j, Y H:i') }}
                            </div>
                        </div>

                        @if ($viewingIncident->latitude && $viewingIncident->longitude)
                            @php
                                $incidentMapMarkers = [[
                                    'lat' => (float) $viewingIncident->latitude,
                                    'lng' => (float) $viewingIncident->longitude,
                                    'label' => $viewingIncident->title,
                                ]];
                            @endphp
                            <x-map
                                id="incident-detail-map-{{ $viewingIncident->id }}"
                                :lat="$viewingIncident->latitude"
                                :lng="$viewingIncident->longitude"
                                :markers="$incidentMapMarkers"
                                :fit-bounds="false"
                                height="160px"
                            />
                        @endif

                        <dl class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                            <div class="flex justify-between gap-4 py-2 first:pt-0">
                                <dt class="text-zinc-500">Site</dt>
                                <dd class="text-right font-medium text-zinc-900 dark:text-zinc-100">{{ $viewingIncident->site?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2">
                                <dt class="text-zinc-500">Type</dt>
                                <dd class="text-right font-medium text-zinc-900 dark:text-zinc-100">{{ $incidentTypes[$viewingIncident->type ?? $viewingIncident->incident_type] ?? ($viewingIncident->type ?? '—') }}</dd>
                            </div>
                            @if ($viewingIncident->approved_at)
                                <div class="flex justify-between gap-4 py-2">
                                    <dt class="text-zinc-500">Approved</dt>
                                    <dd class="text-right text-zinc-900 dark:text-zinc-100">{{ $viewingIncident->approvedBy?->name }} · {{ $viewingIncident->approved_at->format('M j, H:i') }}</dd>
                                </div>
                            @endif
                            @if ($viewingIncident->latitude && $viewingIncident->longitude)
                                <div class="flex justify-between gap-4 py-2">
                                    <dt class="text-zinc-500">GPS</dt>
                                    <dd class="text-right font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $viewingIncident->latitude }}, {{ $viewingIncident->longitude }}</dd>
                                </div>
                            @endif
                            <div class="py-2">
                                <dt class="mb-1 text-zinc-500">Description</dt>
                                <dd class="whitespace-pre-wrap text-zinc-700 dark:text-zinc-300">{{ $viewingIncident->description }}</dd>
                            </div>
                            @if ($viewingIncident->resolution)
                                <div class="py-2 last:pb-0">
                                    <dt class="mb-1 text-zinc-500">Resolution</dt>
                                    <dd class="whitespace-pre-wrap text-zinc-700 dark:text-zinc-300">{{ $viewingIncident->resolution }}</dd>
                                </div>
                            @endif
                        </dl>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Media</h3>
                                @if ($viewingIncident->isOpen())
                                    <button type="button" class="table-action" wire:click="openMediaForSelected">Attach</button>
                                @endif
                            </div>
                            @forelse ($viewingIncident->media as $media)
                                <div class="flex items-center justify-between gap-3 border-t border-zinc-100 py-2 text-sm first:border-0 dark:border-zinc-800" wire:key="media-{{ $media->id }}">
                                    <div class="min-w-0">
                                        <div class="font-medium text-zinc-800 dark:text-zinc-200">{{ ucfirst($media->media_type) }}</div>
                                        @if ($media->caption)
                                            <div class="truncate text-xs text-zinc-500">{{ $media->caption }}</div>
                                        @endif
                                    </div>
                                    <button type="button" class="table-action shrink-0" wire:click="downloadMedia({{ $media->id }})">Download</button>
                                </div>
                            @empty
                                <x-empty-state compact title="No media" description="Attach photos or files to support this report.">
                                    @if ($viewingIncident->isOpen())
                                        <x-slot:actions>
                                            <x-button size="sm" variant="secondary" wire:click="openMediaForSelected">Attach media</x-button>
                                        </x-slot:actions>
                                    @endif
                                </x-empty-state>
                            @endforelse
                        </div>

                        <div class="space-y-2 rounded-md border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-900/50">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Actions</p>
                            <div class="flex flex-wrap gap-2">
                                @if ($viewingIncident->status === 'submitted')
                                    <x-button size="sm" wire:click="approve({{ $viewingIncident->id }})">Approve</x-button>
                                @endif
                                @if ($viewingIncident->isOpen())
                                    <x-button size="sm" variant="secondary" wire:click="edit({{ $viewingIncident->id }})">Edit</x-button>
                                    <x-button size="sm" variant="secondary" wire:click="openClose({{ $viewingIncident->id }})">Close</x-button>
                                    <x-button size="sm" variant="danger" wire:click="openReject({{ $viewingIncident->id }})">Reject</x-button>
                                    <x-button size="sm" variant="danger" wire:click="delete({{ $viewingIncident->id }})" wire:confirm="Delete this incident?">Delete</x-button>
                                @endif
                                <x-button size="sm" variant="secondary" wire:click="exportPdf({{ $viewingIncident->id }})">Export PDF</x-button>
                                <x-button size="sm" variant="secondary" wire:click="closeDetail">Deselect</x-button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex flex-1 items-center justify-center py-8">
                        <x-empty-state compact title="Select an incident" description="Choose a report from the queue to review details and take action." />
                    </div>
                @endif
            </x-section-card>
        </div>
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingId ? 'Edit incident' : 'Report incident'" description="Capture what happened so ops can review and resolve." width="lg">
            <x-drawer-form wire:submit="save" :submit-label="$editingId ? 'Save changes' : 'Submit report'">
                <x-form-section title="Where">
                    <x-select wire:model="form.site_id" label="Site" class="sm:col-span-2">
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                </x-form-section>
                <x-form-section title="Incident">
                    <x-input wire:model="form.title" label="Title" class="sm:col-span-2" />
                    <x-select wire:model="form.type" label="Type">
                        <option value="">Select type</option>
                        @foreach($incidentTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="form.severity" label="Severity">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </x-select>
                    <x-textarea wire:model="form.description" label="Description" rows="4" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($showMediaForm)
        <x-drawer title="Attach media" width="md" close-method="closeMediaDrawer">
            <x-drawer-form wire:submit="uploadMedia" submit-label="Upload" close-method="closeMediaDrawer" target="uploadMedia">
                <x-select wire:model="uploadIncidentId" label="Incident" class="sm:col-span-2">
                    <option value="">Select incident</option>
                    @foreach($allIncidentsForMedia as $incident)
                        <option value="{{ $incident->id }}">#{{ $incident->id }} — {{ $incident->title }}</option>
                    @endforeach
                </x-select>
                <x-file-input wire:model="mediaFile" label="Media file" class="sm:col-span-2" />
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($resolvingIncident)
        <x-drawer
            :title="$resolutionAction === 'reject' ? 'Reject incident' : 'Close incident'"
            :description="$resolvingIncident->title"
            width="md"
            close-method="closeResolution"
        >
            <x-drawer-form wire:submit="submitResolution" :submit-label="$resolutionAction === 'reject' ? 'Reject' : 'Close incident'" close-method="closeResolution">
                <x-form-section :title="$resolutionAction === 'reject' ? 'Rejection' : 'Resolution'">
                    <x-textarea
                        wire:model="resolutionNotes"
                        :label="$resolutionAction === 'reject' ? 'Rejection reason *' : 'Resolution notes *'"
                        rows="5"
                        class="sm:col-span-2"
                    />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
