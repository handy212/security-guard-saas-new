<div>
    <x-page-shell title="Patrol Routes" description="Guard tour routes with QR/NFC checkpoints and live sessions.">
        <div class="grid grid-cols-4 gap-2">
            <x-stat-card compact label="Routes" :value="$stats['routes']" icon="patrols" />
            <x-stat-card compact label="Checkpoints" :value="$stats['checkpoints']" icon="gps" tone="info" />
            <x-stat-card compact label="Active sessions" :value="$stats['active_sessions']" icon="schedules" :tone="$stats['active_sessions'] ? 'warning' : 'default'" />
            <x-stat-card compact label="Completed today" :value="$stats['completed_today']" icon="check" tone="success" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-form-card title="Create route">
                <form wire:submit="saveRoute" class="space-y-3">
                    <x-select wire:model="routeForm.site_id" label="Site">
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="routeForm.name" label="Route name" />
                    <x-input wire:model="routeForm.expected_duration_minutes" label="Duration (min)" type="number" />
                    <x-button type="submit">Save route</x-button>
                </form>
            </x-form-card>

            <x-form-card title="Add checkpoint">
                <form wire:submit="saveCheckpoint" class="space-y-3">
                    <x-select wire:model="checkpointForm.patrol_route_id" label="Route">
                        <option value="">Select route</option>
                        @foreach($routes as $route)
                            <option value="{{ $route->id }}">{{ $route->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="checkpointForm.name" label="Checkpoint name" />
                    <x-input wire:model="checkpointForm.code" label="QR / NFC code" />
                    <x-input wire:model="checkpointForm.sequence" label="Sequence" type="number" min="1" />
                    <x-button type="submit">Save checkpoint</x-button>
                </form>
            </x-form-card>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            @forelse($routes as $route)
                <x-section-card :title="$route->name" :description="$route->site?->name">
                    <ol class="space-y-1">
                        @foreach($route->checkpoints->sortBy('sequence') as $cp)
                            <li class="flex items-center gap-2 rounded border border-zinc-100 bg-zinc-50 px-2 py-1.5 text-sm">
                                <span class="text-xs font-bold text-zinc-500">{{ $cp->sequence }}</span>
                                <span class="flex-1">{{ $cp->name }}</span>
                                <span class="font-mono text-xs text-zinc-500">{{ $cp->code }}</span>
                            </li>
                        @endforeach
                    </ol>
                </x-section-card>
            @empty
                <x-empty-state title="No patrol routes" class="md:col-span-2" />
            @endforelse
        </div>

        @if ($sessions->isNotEmpty())
            <x-data-table title="Recent patrol sessions" class="mt-4">
                <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                    <tr>
                        <th class="px-3 py-2">Route</th>
                        <th class="px-3 py-2">Guard</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Scans</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sessions as $session)
                        <tr class="table-row-hover">
                            <td class="px-3 py-2">{{ $session->route?->name ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $session->assignedGuard?->full_name ?? '—' }}</td>
                            <td class="px-3 py-2"><x-badge :status="$session->status" /></td>
                            <td class="px-3 py-2 text-zinc-600">{{ $session->scans->count() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-data-table>
        @endif
    </x-page-shell>
</div>
