<div>
    <x-page-shell
        title="Client Complaints"
        description="Log, track, and resolve client service issues."
        :breadcrumbs="[
            ['label' => 'Clients', 'href' => route('clients.index')],
            ['label' => 'Complaints'],
        ]"
    >
        <x-slot:actions>
            <x-button wire:click="openForm">Log complaint</x-button>
        </x-slot:actions>

        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$stats['total']" icon="users" />
            <x-stat-card compact label="Open" :value="$stats['open']" icon="incidents" :tone="$stats['open'] ? 'warning' : 'success'" />
            <x-stat-card compact label="High priority" :value="$stats['high']" icon="dispatch" :tone="$stats['high'] ? 'danger' : 'default'" />
            <x-stat-card compact label="Resolved" :value="$stats['resolved']" icon="check" tone="success" />
        </div>

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

        <x-section-card title="Complaints" :description="$stats['open'].' open'" flush>
            <x-data-table>
                <x-table.head>
                    <tr>
                        <x-table.th>Subject</x-table.th>
                        <x-table.th responsive="md">Client / site</x-table.th>
                        <x-table.th>Priority</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th align="right">Actions</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($complaints as $complaint)
                        <tr class="table-row-hover" wire:key="complaint-{{ $complaint->id }}">
                            <x-table.td>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $complaint->subject }}</div>
                                <div class="mt-0.5 line-clamp-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $complaint->description }}</div>
                            </x-table.td>
                            <x-table.td responsive="md">
                                <div class="text-sm text-zinc-800 dark:text-zinc-200">{{ $complaint->clientAccount?->name ?? '—' }}</div>
                                @if ($complaint->site)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $complaint->site->name }}</div>
                                @endif
                            </x-table.td>
                            <x-table.td><x-badge :status="$complaint->priority" /></x-table.td>
                            <x-table.td><x-badge :status="$complaint->status" /></x-table.td>
                            <x-table.td align="right">
                                @if($complaint->status === 'open')
                                    <div class="table-inline-actions justify-end">
                                        <button type="button" wire:click="edit({{ $complaint->id }})" class="table-action">Edit</button>
                                        <x-button size="sm" wire:click="resolve({{ $complaint->id }})" wire:loading.attr="disabled" wire:target="resolve({{ $complaint->id }})">
                                            <span wire:loading.remove wire:target="resolve({{ $complaint->id }})">Resolve</span>
                                            <span wire:loading wire:target="resolve({{ $complaint->id }})">…</span>
                                        </x-button>
                                        <button type="button" wire:click="delete({{ $complaint->id }})" wire:confirm="Delete this complaint?" class="table-action-danger">Delete</button>
                                    </div>
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="5">
                            <x-empty-state
                                compact
                                :title="$hasActiveFilters ? 'No matching complaints' : 'No complaints'"
                                :description="$hasActiveFilters ? 'Try clearing filters.' : 'Log a client service issue to start tracking.'"
                            >
                                @unless ($hasActiveFilters)
                                    <x-slot:actions>
                                        <x-button size="sm" wire:click="openForm">Log complaint</x-button>
                                    </x-slot:actions>
                                @endunless
                            </x-empty-state>
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
        </x-section-card>

        <x-pagination :paginator="$complaints" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingId ? 'Edit complaint' : 'Log complaint'"
            :description="$editingId ? 'Update an open complaint while it is still unresolved.' : 'Record a new client complaint or service issue.'"
            width="lg"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit.prevent="save" :submit-label="$editingId ? 'Save changes' : 'Log complaint'" close-method="closeDrawer" target="save">
                <x-form-section title="Client">
                    <x-select wire:model="form.client_account_id" label="Client *" class="sm:col-span-2">
                        <option value="">Select client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="form.site_id" label="Site (optional)" class="sm:col-span-2">
                        <option value="">Any site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                </x-form-section>
                <x-form-section title="Issue">
                    <x-input wire:model="form.subject" label="Subject *" class="sm:col-span-2" />
                    <x-textarea wire:model="form.description" label="Description *" class="sm:col-span-2" rows="4" />
                    <x-select wire:model="form.priority" label="Priority" class="sm:col-span-2">
                        <option value="low">Low</option>
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                    </x-select>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
