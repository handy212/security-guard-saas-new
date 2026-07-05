<div>
    <x-page-shell title="Incident Reports" description="Log, review, and export security incidents.">
        <x-slot:actions>
            <x-button wire:click="openForm">Report incident</x-button>
            <x-button variant="secondary" wire:click="$set('showMediaForm', true)">Attach media</x-button>
        </x-slot:actions>

        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$incidentStats['total']" icon="incidents" wire:click="applyStatFilter('total')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'all' && $severityFilter === 'all' && $search === ''" />
            <x-stat-card compact label="Open" :value="$incidentStats['open']" icon="pause" :tone="$incidentStats['open'] > 0 ? 'warning' : 'default'" wire:click="applyStatFilter('open')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'open'" />
            <x-stat-card compact label="High risk" :value="$incidentStats['critical']" icon="incidents" :tone="$incidentStats['critical'] > 0 ? 'danger' : 'default'" wire:click="applyStatFilter('critical')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$severityFilter === 'critical'" />
            <x-stat-card compact label="Closed" :value="$incidentStats['closed']" icon="check" tone="success" wire:click="applyStatFilter('closed')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'closed'" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search incidents…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'open' => 'Open', 'closed' => 'Closed']" />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="table-action">Clear filters</button>
                @endif
                <x-filter-select wire:model.live="severityFilter">
                    <option value="all">All severity</option>
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
                    <x-table.th align="right" class="w-12"></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($incidents as $incident)
                    <tr class="table-row-hover" wire:key="incident-{{ $incident->id }}">
                        <x-table.td>
                            <div class="font-medium">{{ $incident->title }}</div>
                            <div class="text-xs text-zinc-500">{{ $incident->type ?? $incident->incident_type }}</div>
                        </x-table.td>
                        <x-table.td responsive="md" muted>{{ $incident->site?->name }}</x-table.td>
                        <x-table.td><x-badge :status="$incident->severity" :map="['low'=>'neutral','medium'=>'info','high'=>'warning','critical'=>'danger']" /></x-table.td>
                        <x-table.td><x-badge :status="$incident->status" /></x-table.td>
                        <x-table.td align="right">
                            <x-row-menu>
                                <x-row-menu-item wire:click="approve({{ $incident->id }})">Approve</x-row-menu-item>
                                <x-row-menu-item wire:click="close({{ $incident->id }})">Close</x-row-menu-item>
                                <x-row-menu-item wire:click="exportPdf({{ $incident->id }})">Export PDF</x-row-menu-item>
                            </x-row-menu>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="5">
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
                <x-input wire:model="form.type" label="Type" />
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

    @if ($showMediaForm)
        <x-drawer title="Attach media" width="md" close-method="closeMediaDrawer">
            <x-drawer-form wire:submit="uploadMedia" submit-label="Upload" close-method="closeMediaDrawer" target="uploadMedia">
                <x-select wire:model="uploadIncidentId" label="Incident" class="sm:col-span-2">
                    <option value="">Select incident</option>
                    @foreach($incidents as $incident)
                        <option value="{{ $incident->id }}">#{{ $incident->id }} — {{ $incident->title }}</option>
                    @endforeach
                </x-select>
                <x-file-input wire:model="mediaFile" label="Media file" class="sm:col-span-2" />
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
