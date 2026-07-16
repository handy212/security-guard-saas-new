<div>
    <x-page-shell title="Client Complaints" description="Log, track, and resolve client service issues.">
        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$stats['total']" icon="users" />
            <x-stat-card compact label="Open" :value="$stats['open']" icon="incidents" :tone="$stats['open'] ? 'warning' : 'success'" />
            <x-stat-card compact label="High priority" :value="$stats['high']" icon="dispatch" :tone="$stats['high'] ? 'danger' : 'default'" />
            <x-stat-card compact label="Resolved" :value="$stats['resolved']" icon="check" tone="success" />
        </div>

        <x-form-card :title="$editingId ? 'Edit complaint' : 'Log complaint'" description="Record a new client complaint or service issue." collapsible>
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
                <x-select wire:model="form.client_account_id" label="Client" required>
                    <option value="">Select client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model="form.site_id" label="Site (optional)">
                    <option value="">Any site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.subject" label="Subject" class="md:col-span-2" required />
                <x-textarea wire:model="form.description" label="Description" class="md:col-span-2" rows="3" required />
                <x-select wire:model="form.priority" label="Priority">
                    <option value="low">Low</option>
                    <option value="normal">Normal</option>
                    <option value="high">High</option>
                </x-select>
                <div class="flex items-end gap-2">
                    <x-button type="submit">{{ $editingId ? 'Save changes' : 'Log complaint' }}</x-button>
                    @if ($editingId)
                        <x-button type="button" variant="secondary" wire:click="cancelEdit">Cancel</x-button>
                    @endif
                </div>
            </form>
        </x-form-card>

        <x-page-toolbar search="search" searchPlaceholder="Search complaints…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'open' => 'Open', 'resolved' => 'Resolved']" />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="table-action">Clear filters</button>
                @endif
            </x-slot:controls>
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Subject</x-table.th>
                    <x-table.th responsive="md">Client</x-table.th>
                    <x-table.th>Priority</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right">Actions</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($complaints as $complaint)
                    <tr class="table-row-hover" wire:key="complaint-{{ $complaint->id }}">
                        <x-table.td>
                            <div class="font-medium text-zinc-900">{{ $complaint->subject }}</div>
                            <div class="mt-0.5 line-clamp-1 text-xs text-zinc-500">{{ $complaint->description }}</div>
                        </x-table.td>
                        <x-table.td responsive="md" muted>{{ $complaint->clientAccount?->name ?? '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$complaint->priority" /></x-table.td>
                        <x-table.td><x-badge :status="$complaint->status" /></x-table.td>
                        <x-table.td align="right">
                            <div class="table-inline-actions">
                                @if($complaint->status === 'open')
                                    <button type="button" wire:click="edit({{ $complaint->id }})" class="table-action">Edit</button>
                                    <x-button size="sm" wire:click="resolve({{ $complaint->id }})">Resolve</x-button>
                                    <button type="button" wire:click="delete({{ $complaint->id }})" wire:confirm="Delete this complaint?" class="table-action text-red-600">Delete</button>
                                @endif
                            </div>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="5">
                        <x-empty-state compact :title="$hasActiveFilters ? 'No matching complaints' : 'No complaints'" />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$complaints" />
    </x-page-shell>
</div>
