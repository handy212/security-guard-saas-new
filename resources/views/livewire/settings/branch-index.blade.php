<div>
    <x-page-shell title="Regions" description="Manage regions, dispatch standby points, and response teams.">
        <x-slot:actions>
            <x-button wire:click="openCreate">Add region</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout class="settings-workspace">
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <x-flash-status />

            <x-page-toolbar search="search" searchPlaceholder="Search regions…" />

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
                            <x-empty-state compact title="No regions" description="Add a region to organize guards and standby points.">
                                <x-slot:actions>
                                    <x-button size="sm" wire:click="openCreate">Add region</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-pagination :paginator="$branches" />
            <x-section-card title="Dispatch standby points" description="Set the coordinates where response teams wait for dispatch." class="mt-4">
                <ul class="mb-3 divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    @forelse ($standbyPoints as $point)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="standby-point-{{ $point->id }}">
                            <span><strong>{{ $point->name }}</strong> · {{ $point->region?->name ?? 'No region' }} · {{ $point->latitude }}, {{ $point->longitude }}</span>
                            <div class="flex items-center gap-2">
                                <button type="button" class="table-action" wire:click="editStandbyPoint({{ $point->id }})">Edit</button>
                                <button type="button" class="table-action" wire:click="removeStandbyPoint({{ $point->id }})" wire:confirm="Remove this standby point?">Remove</button>
                            </div>
                        </li>
                    @empty
                        <li class="py-2 text-zinc-500">No standby points yet.</li>
                    @endforelse
                </ul>
                @error('standbyPoint') <p role="alert" class="mb-3 text-sm text-red-600">{{ $message }}</p> @enderror
                <form wire:submit="createStandbyPoint" class="grid items-end gap-3 sm:grid-cols-2">
                    <x-input wire:model="pointName" label="Point name" />
                    <x-select wire:model="pointRegionId" label="Region">
                        <option value="">Choose an active region</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="pointLatitude" label="Latitude" type="number" step="any" min="-90" max="90" />
                    <x-input wire:model="pointLongitude" label="Longitude" type="number" step="any" min="-180" max="180" />
                    <div class="flex flex-wrap gap-2">
                        <x-button type="submit" size="sm" :disabled="$regions->isEmpty()">{{ $editingStandbyPointId ? 'Update standby point' : 'Create standby point' }}</x-button>
                        @if ($editingStandbyPointId)
                            <x-button type="button" size="sm" variant="secondary" wire:click="cancelStandbyPointEdit">Cancel</x-button>
                        @endif
                    </div>
                </form>
            </x-section-card>

        <x-section-card title="Response teams" class="mt-4">
            <div class="my-3 space-y-2">
                @foreach ($dispatchTeams as $team)
                    @php $outstanding = $team->outstandingDispatches->first(); @endphp
                    <div wire:key="response-team-{{ $team->id }}" class="space-y-2 border-b border-zinc-200 pb-3 dark:border-zinc-800">
                        <p class="text-sm">
                            <strong>{{ $team->name }}</strong> · {{ $team->standbyPoint?->name ?? 'No standby point' }}
                            · {{ $team->active_officers_count }} active officers
                            · {{ $outstanding?->awaitingStationReturn() ? 'Returning' : ($outstanding ? 'Dispatched' : ($team->standbyPoint?->region?->is_active && $team->active_officers_count ? 'Available' : 'Unavailable')) }}
                        </p>
                        @unless ($outstanding)
                            <form wire:submit="assignTeamStandbyPoint({{ $team->id }})" class="flex flex-wrap items-end gap-2">
                                <x-select wire:model="teamStandbyPoints.{{ $team->id }}" label="Change standby point">
                                    <option value="">Choose a standby point</option>
                                    @foreach ($availablePoints as $point)
                                        <option value="{{ $point->id }}">{{ $point->name }} · {{ $point->region->name }}</option>
                                    @endforeach
                                </x-select>
                                <x-button type="submit" size="sm" variant="secondary" :disabled="$availablePoints->isEmpty()">Assign point</x-button>
                            </form>
                        @endunless
                    </div>
                @endforeach
            </div>
            <form wire:submit="createDispatchTeam" class="space-y-3">
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
                <x-button type="submit" size="sm" :disabled="$unrosteredOfficers->isEmpty() || $availablePoints->isEmpty()">Create response team</x-button>
            </form>
        </x-section-card>

        </x-sub-sidebar-layout>
    </x-page-shell>

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
