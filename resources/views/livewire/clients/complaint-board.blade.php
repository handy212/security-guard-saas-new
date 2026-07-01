<div>
    <x-page-shell title="Client Complaints" description="Log, track, and resolve client service issues.">
        <x-stat-grid>
            <x-stat-card compact label="Total" :value="$stats['total']" icon="users" />
            <x-stat-card compact label="Open" :value="$stats['open']" icon="incidents" :tone="$stats['open'] ? 'warning' : 'success'" />
            <x-stat-card compact label="High priority" :value="$stats['high']" icon="dispatch" :tone="$stats['high'] ? 'danger' : 'default'" />
            <x-stat-card compact label="Resolved" :value="$stats['resolved']" icon="check" tone="success" />
        </x-stat-grid>

        <x-form-card title="Log complaint" description="Record a new client complaint or service issue." collapsible>
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
                <div class="flex items-end">
                    <x-button type="submit">Log complaint</x-button>
                </div>
            </form>
        </x-form-card>

        <x-page-toolbar search="search" searchPlaceholder="Search complaints…">
            <x-slot:tabs>
                <x-segment-control model="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'open' => 'Open', 'resolved' => 'Resolved']" />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="text-xs font-medium text-zinc-500 hover:text-zinc-800">Clear filters</button>
                @endif
            </x-slot:controls>
        </x-page-toolbar>

        <x-data-table>
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Subject</th>
                    <th class="hidden px-3 py-2 md:table-cell">Client</th>
                    <th class="px-3 py-2">Priority</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($complaints as $complaint)
                    <tr class="table-row-hover" wire:key="complaint-{{ $complaint->id }}">
                        <td class="px-3 py-2">
                            <div class="font-medium text-zinc-900">{{ $complaint->subject }}</div>
                            <div class="mt-0.5 line-clamp-1 text-xs text-zinc-500">{{ $complaint->description }}</div>
                        </td>
                        <td class="hidden px-3 py-2 text-zinc-600 md:table-cell">{{ $complaint->clientAccount?->name ?? '—' }}</td>
                        <td class="px-3 py-2"><x-badge :status="$complaint->priority" /></td>
                        <td class="px-3 py-2"><x-badge :status="$complaint->status" /></td>
                        <td class="px-3 py-2 text-right">
                            @if($complaint->status !== 'resolved')
                                <x-button size="sm" wire:click="resolve({{ $complaint->id }})">Resolve</x-button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-8"><x-empty-state :title="$hasActiveFilters ? 'No matching complaints' : 'No complaints'" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$complaints" />
    </x-page-shell>
</div>
