<div>
    <x-page-shell
        title="Patrol Routes"
        description="Guard tour routes with QR/NFC checkpoints and live sessions."
        :breadcrumbs="[['label' => 'Patrols']]"
    >
        <x-slot:actions>
            <x-button variant="secondary" size="sm" href="{{ route('patrols.fleet') }}">Manage fleet</x-button>
            <x-button variant="secondary" size="sm" href="{{ route('assets.index') }}">Assets kit</x-button>
            <x-button variant="secondary" size="sm" wire:click="openAssignForm">Assign patrol</x-button>
            <x-button variant="secondary" size="sm" wire:click="openCheckpointForm">Add checkpoint</x-button>
            <x-button size="sm" wire:click="openRouteForm">Create route</x-button>
        </x-slot:actions>

        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Routes" :value="$stats['routes']" icon="patrols" />
            <x-stat-card compact label="Checkpoints" :value="$stats['checkpoints']" icon="gps" tone="info" />
            <x-stat-card compact label="Active sessions" :value="$stats['active_sessions']" icon="schedules" :tone="$stats['active_sessions'] ? 'warning' : 'default'" />
            <x-stat-card compact label="Fleet available" :value="$stats['fleet_available']" icon="sites" tone="info" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search routes or sites…" />

        <div class="grid gap-3 md:grid-cols-2">
            @forelse($routes as $route)
                <section class="card-surface flex h-full flex-col overflow-hidden" wire:key="route-{{ $route->id }}">
                    <div class="card-header">
                        <div class="min-w-0">
                            <h2 class="card-header-title truncate">{{ $route->name }}</h2>
                            <p class="card-header-meta">
                                {{ $route->site?->name ?? 'No site' }}
                                · <span class="tabular-nums">{{ $route->checkpoints->count() }}</span> checkpoints
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button type="button" wire:click="openCheckpointForm({{ $route->id }})" class="table-action">+ CP</button>
                            <button type="button" wire:click="editRoute({{ $route->id }})" class="table-action">Edit</button>
                            <button type="button" wire:click="deleteRoute({{ $route->id }})" wire:confirm="Delete this route and its checkpoints?" class="table-action-danger">Delete</button>
                        </div>
                    </div>
                    <ol class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($route->checkpoints->sortBy('sequence') as $cp)
                            <li class="flex items-center gap-2 px-4 py-2 text-sm" wire:key="cp-{{ $cp->id }}">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-[11px] font-bold tabular-nums text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $cp->sequence }}</span>
                                <span class="min-w-0 flex-1 truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $cp->name }}</span>
                                <span class="hidden font-mono text-[11px] text-zinc-400 sm:inline">{{ $cp->code }}</span>
                                <button type="button" wire:click="editCheckpoint({{ $cp->id }})" class="table-action">Edit</button>
                                <button type="button" wire:click="deleteCheckpoint({{ $cp->id }})" wire:confirm="Delete this checkpoint?" class="table-action-danger">Del</button>
                            </li>
                        @empty
                            <li class="px-4 py-6">
                                <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                                    No checkpoints yet —
                                    <button type="button" wire:click="openCheckpointForm({{ $route->id }})" class="table-action">add one</button>
                                </p>
                            </li>
                        @endforelse
                    </ol>
                </section>
            @empty
                <x-empty-state title="No patrol routes" description="Create a route for a site, then add checkpoints." class="md:col-span-2">
                    <x-slot:actions>
                        <x-button size="sm" wire:click="openRouteForm">Create route</x-button>
                    </x-slot:actions>
                </x-empty-state>
            @endforelse
        </div>

        @if ($sessions->isNotEmpty())
            <x-section-card title="Recent patrol sessions" class="mt-4" flush>
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Route</x-table.th>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Vehicle</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th>Progress</x-table.th>
                            <x-table.th>Scans</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @foreach($sessions as $session)
                            <tr class="table-row-hover" wire:key="session-{{ $session->id }}">
                                <x-table.td>
                                    <div>{{ $session->route?->name ?? '—' }}</div>
                                    @if ($session->route?->site)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $session->route->site->name }}</div>
                                    @endif
                                </x-table.td>
                                <x-table.td>{{ $session->assignedGuard?->full_name ?? '—' }}</x-table.td>
                                <x-table.td muted>
                                    @if ($session->vehiclePatrol?->vehicle)
                                        {{ $session->vehiclePatrol->vehicle->displayName() }}
                                    @else
                                        —
                                    @endif
                                </x-table.td>
                                <x-table.td><x-badge :status="$session->status" /></x-table.td>
                                <x-table.td muted class="tabular-nums">{{ $session->completion_percent ?? 0 }}%</x-table.td>
                                <x-table.td muted class="tabular-nums">{{ $session->scans->count() }}</x-table.td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            </x-section-card>
        @endif

        @if ($submissions->isNotEmpty())
            <x-section-card title="Recent task submissions" class="mt-4" flush>
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Task</x-table.th>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Checkpoint</x-table.th>
                            <x-table.th>Response</x-table.th>
                            <x-table.th responsive="md">Notes</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @foreach($submissions as $submission)
                            <tr class="table-row-hover" wire:key="submission-{{ $submission->id }}">
                                <x-table.td class="font-medium">{{ $submission->task?->title ?? '—' }}</x-table.td>
                                <x-table.td muted>{{ $submission->scan?->assignedGuard?->full_name ?? '—' }}</x-table.td>
                                <x-table.td muted>{{ $submission->scan?->checkpoint?->name ?? '—' }}</x-table.td>
                                <x-table.td>{{ is_array($submission->response) ? json_encode($submission->response) : ($submission->response ?: '—') }}</x-table.td>
                                <x-table.td responsive="md" muted>{{ $submission->notes ?: '—' }}</x-table.td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-data-table>
            </x-section-card>
        @endif
    </x-page-shell>

    @if ($activeDrawer === 'route')
        <x-drawer
            :title="$editingRouteId ? 'Edit route' : 'Create route'"
            :description="$editingRouteId ? 'Update site, name, and expected duration.' : 'Define a tour route for a site, then add checkpoints.'"
            width="md"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit.prevent="saveRoute" :submit-label="$editingRouteId ? 'Update route' : 'Save route'" close-method="closeDrawer" target="saveRoute">
                <x-form-section title="Route">
                    <x-select wire:model="routeForm.site_id" label="Site *" class="sm:col-span-2">
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="routeForm.name" label="Route name *" class="sm:col-span-2" />
                    <x-textarea wire:model="routeForm.description" label="Description" rows="2" class="sm:col-span-2" />
                    <x-input wire:model="routeForm.expected_duration_minutes" label="Duration (min)" type="number" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($activeDrawer === 'checkpoint')
        <x-drawer
            :title="$editingCheckpointId ? 'Edit checkpoint' : 'Add checkpoint'"
            description="QR/NFC code, sequence, and optional GPS for this stop."
            width="md"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit.prevent="saveCheckpoint" :submit-label="$editingCheckpointId ? 'Update checkpoint' : 'Save checkpoint'" close-method="closeDrawer" target="saveCheckpoint">
                <x-form-section title="Checkpoint">
                    <x-select wire:model="checkpointForm.patrol_route_id" label="Route *" class="sm:col-span-2">
                        <option value="">Select route</option>
                        @foreach($routes as $route)
                            <option value="{{ $route->id }}">{{ $route->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="checkpointForm.name" label="Checkpoint name *" class="sm:col-span-2" />
                    <x-input wire:model="checkpointForm.code" label="QR / NFC code *" />
                    <x-input wire:model="checkpointForm.sequence" label="Sequence" type="number" min="1" />
                    <x-input wire:model="checkpointForm.latitude" label="Latitude" type="number" step="any" />
                    <x-input wire:model="checkpointForm.longitude" label="Longitude" type="number" step="any" />
                    <x-textarea wire:model="checkpointForm.instructions" label="Instructions" rows="2" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($activeDrawer === 'assign')
        <x-drawer
            title="Assign patrol"
            description="Guard needs an active shift on the route’s site. Vehicles sync into Assets for deploy kits."
            width="md"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit.prevent="assignPatrol" submit-label="Start patrol" close-method="closeDrawer" target="assignPatrol">
                <x-form-section title="Deployment">
                    <x-select wire:model="assignForm.patrol_route_id" label="Route *" class="sm:col-span-2">
                        <option value="">Select route</option>
                        @foreach($routes as $route)
                            <option value="{{ $route->id }}">{{ $route->name }} · {{ $route->site?->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="assignForm.guard_id" label="Guard *" class="sm:col-span-2">
                        <option value="">Select guard</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="assignForm.vehicle_id" label="Vehicle / motor" class="sm:col-span-2">
                        <option value="">None</option>
                        @foreach($availableFleet as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->displayName() }}</option>
                        @endforeach
                    </x-select>
                    <p class="sm:col-span-2 text-xs text-zinc-500 dark:text-zinc-400">
                        Guard must already be deployed to this route’s site. Use the Deploy wizard first if needed.
                    </p>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
