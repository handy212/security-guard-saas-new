<div>
    <x-page-shell title="Incident Reports" description="Log, review, and export security incidents.">
        <x-slot:actions>
            <x-button wire:click="openForm">Report incident</x-button>
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

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Incident</x-table.th>
                    <x-table.th responsive="md">Site</x-table.th>
                    <x-table.th>Severity</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th responsive="lg">Media</x-table.th>
                    <x-table.th align="right" class="w-12"></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($incidents as $incident)
                    <tr class="table-row-hover cursor-pointer" wire:key="incident-{{ $incident->id }}" wire:click="viewIncident({{ $incident->id }})">
                        <x-table.td>
                            <div class="font-medium">{{ $incident->title }}</div>
                            <div class="text-xs text-zinc-500">{{ $incidentTypes[$incident->type ?? $incident->incident_type] ?? ($incident->type ?? $incident->incident_type) }}</div>
                        </x-table.td>
                        <x-table.td responsive="md" muted>{{ $incident->site?->name }}</x-table.td>
                        <x-table.td><x-badge :status="$incident->severity" :map="['low'=>'neutral','medium'=>'info','high'=>'warning','critical'=>'danger']" /></x-table.td>
                        <x-table.td><x-badge :status="$incident->status" /></x-table.td>
                        <x-table.td responsive="lg" muted>{{ $incident->media_count ?: '—' }}</x-table.td>
                        <x-table.td align="right" wire:click.stop>
                            <x-row-menu>
                                <x-row-menu-item wire:click="viewIncident({{ $incident->id }})">View</x-row-menu-item>
                                @if ($incident->status === 'submitted')
                                    <x-row-menu-item wire:click="approve({{ $incident->id }})">Approve</x-row-menu-item>
                                @endif
                                @if ($incident->isOpen())
                                    <x-row-menu-item wire:click="openClose({{ $incident->id }})">Close</x-row-menu-item>
                                    <x-row-menu-item wire:click="openReject({{ $incident->id }})">Reject</x-row-menu-item>
                                @endif
                                <x-row-menu-item wire:click="exportPdf({{ $incident->id }})">Export PDF</x-row-menu-item>
                            </x-row-menu>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6">
                        <x-empty-state compact :title="$hasActiveFilters ? 'No matching incidents' : 'No incidents'" />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$incidents" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="Report incident" width="lg">
            <x-drawer-form wire:submit="save" submit-label="Submit report">
                <x-select wire:model="form.site_id" label="Site" class="sm:col-span-2">
                    <option value="">Select site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
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
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($showDetail && $viewingIncident)
        <x-drawer title="Incident detail" :description="$viewingIncident->title" width="lg" close-method="closeDetail">
            <div class="space-y-4 p-1">
                <div class="flex flex-wrap gap-2">
                    <x-badge :status="$viewingIncident->severity" :map="['low'=>'neutral','medium'=>'info','high'=>'warning','critical'=>'danger']" />
                    <x-badge :status="$viewingIncident->status" />
                </div>

                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-zinc-500">Site</dt>
                        <dd class="font-medium">{{ $viewingIncident->site?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-zinc-500">Type</dt>
                        <dd class="font-medium">{{ $incidentTypes[$viewingIncident->type ?? $viewingIncident->incident_type] ?? ($viewingIncident->type ?? '—') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-zinc-500">Reported by</dt>
                        <dd class="font-medium">{{ $viewingIncident->reportedBy?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-zinc-500">Reported</dt>
                        <dd class="font-medium">{{ $viewingIncident->reported_at?->format('M j, Y H:i') ?? $viewingIncident->created_at?->format('M j, Y H:i') }}</dd>
                    </div>
                    @if ($viewingIncident->approved_at)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-zinc-500">Approved</dt>
                            <dd class="font-medium">{{ $viewingIncident->approvedBy?->name }} · {{ $viewingIncident->approved_at->format('M j, H:i') }}</dd>
                        </div>
                    @endif
                    @if ($viewingIncident->latitude && $viewingIncident->longitude)
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-zinc-500">GPS</dt>
                            <dd class="font-mono text-xs">{{ $viewingIncident->latitude }}, {{ $viewingIncident->longitude }}</dd>
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-zinc-500">Description</dt>
                        <dd class="mt-1 whitespace-pre-wrap text-zinc-700">{{ $viewingIncident->description }}</dd>
                    </div>
                    @if ($viewingIncident->resolution)
                        <div class="sm:col-span-2">
                            <dt class="text-xs uppercase tracking-wide text-zinc-500">Resolution</dt>
                            <dd class="mt-1 whitespace-pre-wrap text-zinc-700">{{ $viewingIncident->resolution }}</dd>
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
                        <div class="flex items-center justify-between border-t border-zinc-100 py-2 text-sm first:border-0" wire:key="media-{{ $media->id }}">
                            <span>{{ ucfirst($media->media_type) }}{{ $media->caption ? ' — '.$media->caption : '' }}</span>
                            <button type="button" class="table-action" wire:click="downloadMedia({{ $media->id }})">Download</button>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">No media attached.</p>
                    @endforelse
                </div>

                <div class="flex flex-wrap gap-2 border-t border-zinc-100 pt-3">
                    @if ($viewingIncident->status === 'submitted')
                        <x-button size="sm" wire:click="approve({{ $viewingIncident->id }})">Approve</x-button>
                    @endif
                    @if ($viewingIncident->isOpen())
                        <x-button size="sm" variant="secondary" wire:click="openClose({{ $viewingIncident->id }})">Close</x-button>
                        <x-button size="sm" variant="danger" wire:click="openReject({{ $viewingIncident->id }})">Reject</x-button>
                    @endif
                    <x-button size="sm" variant="secondary" wire:click="exportPdf({{ $viewingIncident->id }})">Export PDF</x-button>
                    <x-button size="sm" variant="secondary" wire:click="closeDetail">Done</x-button>
                </div>
            </div>
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
        <x-modal
            :title="$resolutionAction === 'reject' ? 'Reject incident' : 'Close incident'"
            :description="$resolvingIncident->title"
            closeMethod="closeResolution"
        >
            <form wire:submit="submitResolution" class="space-y-3 p-1">
                <x-textarea wire:model="resolutionNotes" :label="$resolutionAction === 'reject' ? 'Rejection reason' : 'Resolution notes'" rows="4" />
                <div class="flex justify-end gap-2">
                    <x-button type="button" variant="secondary" wire:click="closeResolution">Cancel</x-button>
                    <x-button type="submit" :variant="$resolutionAction === 'reject' ? 'danger' : 'primary'">
                        {{ $resolutionAction === 'reject' ? 'Reject' : 'Close' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
