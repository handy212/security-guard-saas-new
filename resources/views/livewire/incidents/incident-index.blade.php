<div>
    <x-page-shell title="Incident Reports" description="Log, review, and export security incidents.">
        <x-slot:actions>
            <x-button wire:click="openForm">Report incident</x-button>
            <x-button variant="secondary" wire:click="$set('showMediaForm', true)">Attach media</x-button>
        </x-slot:actions>

        <x-stat-grid>
            <x-stat-card compact label="Total" :value="$incidentStats['total']" icon="incidents" wire:click="applyStatFilter('total')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'all' && $severityFilter === 'all' && $search === ''" />
            <x-stat-card compact label="Open" :value="$incidentStats['open']" icon="pause" :tone="$incidentStats['open'] > 0 ? 'warning' : 'default'" wire:click="applyStatFilter('open')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'open'" />
            <x-stat-card compact label="High risk" :value="$incidentStats['critical']" icon="incidents" :tone="$incidentStats['critical'] > 0 ? 'danger' : 'default'" wire:click="applyStatFilter('critical')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$severityFilter === 'critical'" />
            <x-stat-card compact label="Closed" :value="$incidentStats['closed']" icon="check" tone="success" wire:click="applyStatFilter('closed')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'closed'" />
        </x-stat-grid>

        <x-page-toolbar search="search" searchPlaceholder="Search incidents…">
            <x-slot:tabs>
                <x-segment-control model="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'open' => 'Open', 'closed' => 'Closed']" />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="text-xs font-medium text-zinc-500 hover:text-zinc-800">Clear filters</button>
                @endif
                <select wire:model.live="severityFilter" class="form-input w-auto min-w-[8.5rem] text-sm">
                    <option value="all">All severity</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </x-slot:controls>
        </x-page-toolbar>

        <x-data-table>
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Incident</th>
                    <th class="hidden px-3 py-2 md:table-cell">Site</th>
                    <th class="px-3 py-2">Severity</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right w-12"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($incidents as $incident)
                    <tr class="table-row-hover" wire:key="incident-{{ $incident->id }}">
                        <td class="px-3 py-2">
                            <div class="font-medium">{{ $incident->title }}</div>
                            <div class="text-xs text-zinc-500">{{ $incident->type ?? $incident->incident_type }}</div>
                        </td>
                        <td class="hidden px-3 py-2 text-zinc-600 md:table-cell">{{ $incident->site?->name }}</td>
                        <td class="px-3 py-2"><x-badge :status="$incident->severity" :map="['low'=>'neutral','medium'=>'info','high'=>'warning','critical'=>'danger']" /></td>
                        <td class="px-3 py-2"><x-badge :status="$incident->status" /></td>
                        <td class="px-3 py-2 text-right">
                            <div x-data="{ open: false }" class="relative inline-block text-left">
                                <button type="button" @click="open = !open" class="rounded-md p-1.5 text-zinc-500 hover:bg-zinc-100" aria-label="Actions">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4z"/></svg>
                                </button>
                                <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-10 mt-1 w-36 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg">
                                    <button type="button" wire:click="approve({{ $incident->id }})" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-50">Approve</button>
                                    <button type="button" wire:click="close({{ $incident->id }})" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-50">Close</button>
                                    <button type="button" wire:click="exportPdf({{ $incident->id }})" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-50">Export PDF</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-8"><x-empty-state :title="$hasActiveFilters ? 'No matching incidents' : 'No incidents'" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$incidents" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="Report incident" width="lg">
            <form wire:submit="save" class="space-y-3">
                <x-select wire:model="form.site_id" label="Site">
                    <option value="">Select site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.title" label="Title" />
                <x-input wire:model="form.type" label="Type" />
                <x-select wire:model="form.severity" label="Severity">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </x-select>
                <x-textarea wire:model="form.description" label="Description" rows="4" />
                <div class="flex gap-2">
                    <x-button type="submit" loading-text="Submitting…">Submit</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeDrawer">Cancel</x-button>
                </div>
            </form>
        </x-drawer>
    @endif

    @if ($showMediaForm)
        <x-drawer title="Attach media" width="md" close-method="closeMediaDrawer">
            <form wire:submit="uploadMedia" class="space-y-3">
                <x-select wire:model="uploadIncidentId" label="Incident">
                    <option value="">Select incident</option>
                    @foreach($incidents as $incident)
                        <option value="{{ $incident->id }}">#{{ $incident->id }} — {{ $incident->title }}</option>
                    @endforeach
                </x-select>
                <input wire:model="mediaFile" type="file" class="form-input text-sm">
                <div class="flex gap-2">
                    <x-button type="submit" variant="secondary" loading-text="Uploading…">Upload</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeMediaDrawer">Cancel</x-button>
                </div>
            </form>
        </x-drawer>
    @endif
</div>
