<div>
    <x-page-header title="Incident Reports" description="Log, review, and export security incidents across all sites." />

    <div class="space-y-5 p-6">
        <x-form-card title="Report new incident" description="Submitted incidents notify supervisors and appear in the client portal." collapsible :open="true">
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
                <x-select wire:model="form.site_id" label="Site">
                    <option value="">Select site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.title" label="Title" placeholder="Brief incident title" />
                <x-input wire:model="form.type" label="Type" placeholder="Theft, trespass, medical…" />
                <x-select wire:model="form.severity" label="Severity">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </x-select>
                <x-textarea wire:model="form.description" label="Description" class="md:col-span-2" rows="4" placeholder="Describe what happened…" />
                <div class="md:col-span-2">
                    <x-button type="submit">Submit incident</x-button>
                </div>
            </form>
        </x-form-card>

        <x-form-card title="Attach media" description="Upload photos or documents to an existing incident." collapsible :open="false">
            <form wire:submit="uploadMedia" class="grid gap-4 md:grid-cols-3">
                <x-select wire:model="uploadIncidentId" label="Incident">
                    <option value="">Select incident</option>
                    @foreach($incidents as $incident)
                        <option value="{{ $incident->id }}">#{{ $incident->id }} — {{ $incident->title }}</option>
                    @endforeach
                </x-select>
                <x-form-field label="File">
                    <input wire:model="mediaFile" type="file" class="form-input file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-sm" />
                </x-form-field>
                <div class="flex items-end">
                    <x-button type="submit" variant="secondary">Upload media</x-button>
                </div>
            </form>
        </x-form-card>

        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search incidents…" />

        <x-data-table title="All incidents">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Incident</th>
                    <th class="px-4 py-3">Site</th>
                    <th class="px-4 py-3">Severity</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($incidents as $incident)
                    <tr class="table-row-hover">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $incident->title }}</div>
                            <div class="text-xs text-slate-500">{{ $incident->type ?? $incident->incident_type }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $incident->site?->name }}</td>
                        <td class="px-4 py-3"><x-badge :status="$incident->severity" :map="['low'=>'neutral','medium'=>'info','high'=>'warning','critical'=>'danger']" /></td>
                        <td class="px-4 py-3"><x-badge :status="$incident->status" /></td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button wire:click="approve({{ $incident->id }})" class="btn-link">Approve</button>
                            <button wire:click="close({{ $incident->id }})" class="btn-link">Close</button>
                            <button wire:click="exportPdf({{ $incident->id }})" class="btn-link">PDF</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10"><x-empty-state title="No incidents reported" description="Incident reports from guards and supervisors will appear here." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        {{ $incidents->links('components.pagination') }}
    </div>
</div>
