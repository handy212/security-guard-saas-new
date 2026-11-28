<div class="space-y-2">
    <x-flash-status type="success" />

    @if ($section === 'regions')
        <div class="space-y-2">
            <x-page-toolbar search="search" searchPlaceholder="Search regions…">
                <x-slot:controls><x-button size="sm" wire:click="openCreate">Add region</x-button></x-slot:controls>
            </x-page-toolbar>

            <x-data-table>
                <x-table.head>
                    <tr>
                        <x-table.th>Region</x-table.th>
                        <x-table.th responsive="md">Code</x-table.th>
                        <x-table.th responsive="lg">Location</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th align="right" class="w-12"></x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($branches as $branch)
                        <tr class="table-row-hover" wire:key="branch-{{ $branch->id }}">
                            <x-table.td>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $branch->name }}</div>
                                @if ($branch->phone || $branch->email)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ collect([$branch->phone, $branch->email])->filter()->implode(' · ') }}</div>
                                @endif
                            </x-table.td>
                            <x-table.td responsive="md" mono>{{ $branch->code ?: '—' }}</x-table.td>
                            <x-table.td responsive="lg" muted>{{ collect([$branch->city, $branch->country])->filter()->implode(', ') ?: '—' }}</x-table.td>
                            <x-table.td><x-badge :status="$branch->is_active ? 'active' : 'inactive'" /></x-table.td>
                            <x-table.td align="right">
                                <x-row-menu>
                                    <x-row-menu-item wire:click="edit({{ $branch->id }})">Edit</x-row-menu-item>
                                    <x-row-menu-item wire:click="delete({{ $branch->id }})" wire:confirm="Delete this region?" danger>Delete</x-row-menu-item>
                                </x-row-menu>
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="5">
                            <x-empty-state inline title="No regions found" />
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-pagination :paginator="$branches" />
            </div>

    @elseif ($section === 'standby')
            @error('standbyPoint') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
            <x-data-table>
                <x-table.head><tr>
                    <x-table.th>Standby point</x-table.th><x-table.th>Region</x-table.th>
                    <x-table.th responsive="md">Location</x-table.th><x-table.th>Radius</x-table.th>
                    <x-table.th align="right">Actions</x-table.th>
                </tr></x-table.head>
                <tbody>
                    @forelse ($standbyPoints as $point)
                        <tr class="table-row-hover" wire:key="standby-point-{{ $point->id }}">
                            <x-table.td class="font-medium">{{ $point->name }}</x-table.td>
                            <x-table.td>{{ $point->region?->name ?? 'No region' }}</x-table.td>
                            <x-table.td responsive="md" muted>{{ $point->address ?? ($point->latitude.', '.$point->longitude) }}</x-table.td>
                            <x-table.td class="tabular-nums">{{ $point->radius_meters ? number_format($point->radius_meters).' m' : '—' }}</x-table.td>
                            <x-table.td align="right"><x-row-menu>
                                <x-row-menu-item wire:click="editStandbyPoint({{ $point->id }})">Edit</x-row-menu-item>
                                <x-row-menu-item wire:click="removeStandbyPoint({{ $point->id }})" wire:confirm="Remove this standby point? Any assigned teams will need a new point before they can be dispatched." danger>Remove</x-row-menu-item>
                            </x-row-menu></x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="5"><x-empty-state inline title="No standby points yet" /></x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
            <x-section-card :title="$editingStandbyPointId ? 'Edit standby point' : 'Add standby point'" compact>
                <form wire:submit="createStandbyPoint" class="grid items-end gap-2 sm:grid-cols-2 xl:grid-cols-3">
                    <x-input wire:model="pointName" label="Point name" />
                    <x-select wire:model="pointRegionId" label="Region">
                        <option value="">Choose an active region</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="pointLatitude" label="Latitude" type="number" step="any" min="-90" max="90" />
                    <x-input wire:model="pointLongitude" label="Longitude" type="number" step="any" min="-180" max="180" />
                    <x-input wire:model="pointRadius" label="Radius (metres)" type="number" step="1" min="1" max="100000" />
                    <x-input wire:model="pointAddress" label="Address" maxlength="500" class="xl:col-span-2" />
                    <div>
                        <x-button type="button" size="sm" variant="secondary" wire:click="lookupStandbyAddress" wire:loading.attr="disabled" wire:target="lookupStandbyAddress">Look up address</x-button>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:col-span-2 xl:col-span-3">
                        <x-button type="submit" size="sm" :disabled="$regions->isEmpty()">{{ $editingStandbyPointId ? 'Update standby point' : 'Create standby point' }}</x-button>
                        @if ($editingStandbyPointId)
                            <x-button type="button" size="sm" variant="secondary" wire:click="cancelStandbyPointEdit">Cancel</x-button>
                        @endif
                    </div>
                </form>
            </x-section-card>
    @else
        @error('responseTeam') <p role="alert" class="text-sm text-red-600">{{ $message }}</p> @enderror
        <x-data-table>
            <x-table.head><tr>
                <x-table.th>Team</x-table.th><x-table.th>Standby point</x-table.th><x-table.th>Officers</x-table.th>
                <x-table.th>Status</x-table.th><x-table.th align="right">Actions</x-table.th>
            </tr></x-table.head>
            <tbody>
                @forelse ($dispatchTeams as $team)
                    @php
                        $outstanding = $team->outstandingDispatches->first();
                        $teamStatus = $outstanding?->awaitingStationReturn() ? 'Returning' : ($outstanding ? 'Dispatched' : ($team->activeDeployment ? 'On duty' : 'Off duty'));
                    @endphp
                    <tr class="table-row-hover" wire:key="dispatch-team-{{ $team->id }}">
                        <x-table.td class="font-medium">{{ $team->name }}</x-table.td>
                        <x-table.td>{{ $team->standbyPoint?->name ?? '—' }}</x-table.td>
                        <x-table.td class="tabular-nums">{{ $team->active_officers_count }}</x-table.td>
                        <x-table.td><x-badge :status="$teamStatus" :map="['On duty' => 'success', 'Returning' => 'warning', 'Dispatched' => 'info']" /></x-table.td>
                        <x-table.td align="right">
                            @unless ($outstanding || $team->activeDeployment)
                                <div class="table-inline-actions justify-end">
                                    <button type="button" class="table-action" wire:click="editDispatchTeam({{ $team->id }})"
                                        wire:loading.attr="disabled" wire:target="editDispatchTeam">Edit</button>
                                    <button type="button" class="table-action-danger" wire:click="removeDispatchTeam({{ $team->id }})" wire:confirm="Remove this team and release its officers?">Remove</button>
                                </div>
                            @else
                                @can('dispatch.manage')
                                    @if ($team->activeDeployment)
                                        <a class="page-link whitespace-nowrap" href="{{ route('dispatch.deployments') }}#duty-team-{{ $team->id }}">Manage duty</a>
                                    @elseif ($outstanding)
                                        <a class="page-link whitespace-nowrap" href="{{ route('dispatch.control-room', ['dispatch' => $outstanding->id]) }}" wire:navigate>View call</a>
                                    @endif
                                @endcan
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $team->activeDeployment ? 'Close duty to edit or remove' : 'Finish the call to edit or remove' }}</span>
                            @endunless
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="5"><x-empty-state inline title="No teams yet" /></x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>
        <x-section-card :title="$editingTeamId ? 'Edit team' : 'Add team'" compact>
            <form wire:key="dispatch-team-form-{{ $editingTeamId ?? 'new' }}" wire:submit="createDispatchTeam" class="space-y-2"
                @if ($editingTeamId) x-data x-init="$nextTick(() => $el.scrollIntoView({ block: 'nearest' }))" @endif>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-input wire:model="teamName" label="Team name" />
                    <x-select wire:model="standbyPointId" label="Standby point">
                        <option value="">Choose a standby point</option>
                        @foreach ($availablePoints as $point)
                            <option value="{{ $point->id }}">{{ $point->name }} · {{ $point->region->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <fieldset class="space-y-1 text-sm">
                    <legend class="font-medium">Active dispatch officers</legend>
                    @forelse ($unrosteredOfficers as $officer)
                        <label class="mr-3 inline-flex items-center gap-2"><input type="checkbox" wire:model="teamMembers" value="{{ $officer->id }}"> {{ $officer->full_name }}</label>
                    @empty
                        <p>No unrostered dispatch officers.</p>
                    @endforelse
                </fieldset>
                @foreach ($errors->get('teamMembers.*') as $messages)
                    <p class="text-sm text-red-600">{{ $messages[0] }}</p>
                @endforeach
                @error('teamMembers') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="flex gap-2">
                    <x-button type="submit" size="sm" :disabled="$unrosteredOfficers->isEmpty() || (! $editingTeamId && $availablePoints->isEmpty())">{{ $editingTeamId ? 'Save team' : 'Create team' }}</x-button>
                    @if ($editingTeamId)
                        <x-button type="button" size="sm" variant="secondary" wire:click="cancelTeamEdit">Cancel</x-button>
                    @endif
                </div>
            </form>
        </x-section-card>
    @endif

    @if ($showForm)
        <x-drawer
            :title="$editingId ? 'Edit region' : 'Add region'"
            :description="$editingId ? 'Update region details used on guard assignments.' : 'Create a region for rostering and dispatch.'"
            width="md"
        >
            <x-drawer-form wire:submit="save" :submit-label="$editingId ? 'Update region' : 'Create region'">
                <x-form-section title="Region">
                    <x-input wire:model.live="form.name" label="Name" class="sm:col-span-2" />
                    <x-input wire:model.live="form.code" label="Code" />
                    <x-input wire:model.live="form.phone" label="Phone" />
                    <x-input wire:model.live="form.email" label="Email" type="email" class="sm:col-span-2" />
                </x-form-section>
                <x-form-section title="Location">
                    <x-input wire:model.live="form.address" label="Address" class="sm:col-span-2" />
                    <x-input wire:model.live="form.city" label="City" />
                    <x-input wire:model.live="form.country" label="Country" />
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">
                        <input type="checkbox" wire:model.live="form.is_active" class="rounded border-zinc-300 text-accent-600 focus:ring-accent-600/20">
                        Active
                    </label>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
