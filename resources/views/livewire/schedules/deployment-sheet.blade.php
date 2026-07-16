<div>
    <x-page-shell title="Deployment Sheet" description="Daily roster — assign, confirm, kit, and reassign from here.">
        <x-slot:actions>
            <x-button variant="secondary" size="sm" wire:click="previousDay">Previous</x-button>
            <x-button variant="secondary" size="sm" wire:click="goToday" :disabled="$date === today()->toDateString()">Today</x-button>
            <x-button variant="secondary" size="sm" wire:click="nextDay">Next</x-button>
            <x-input wire:model.live="date" type="date" class="w-auto text-sm" />
            <x-button href="{{ route('schedules.deploy', ['date' => $date]) }}">Deploy wizard</x-button>
            <x-button variant="secondary" href="{{ route('schedules.index', ['date' => $date]) }}">Day roster</x-button>
            <x-button variant="secondary" type="button" onclick="window.print()">Print</x-button>
        </x-slot:actions>

        <x-flash-status />

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

            <div class="space-y-4">
                <div class="stat-grid">
                    <x-stat-card compact label="Assignments" :value="$stats['assignments']" icon="schedules" />
                    <x-stat-card compact label="Confirmed" :value="$stats['confirmed']" icon="check" tone="success" />
                    <x-stat-card compact label="Pending confirm" :value="$stats['pending']" icon="guards" :tone="$stats['pending'] ? 'warning' : 'default'" />
                    <x-stat-card compact label="Kit issued" :value="$stats['kit']" icon="sites" :tone="$stats['kit'] ? 'info' : 'default'" />
                    <x-stat-card compact label="Staffing gaps" :value="$stats['gaps']" icon="dispatch" :tone="$stats['gaps'] ? 'danger' : 'success'" />
                </div>

                <div class="flex flex-wrap items-end gap-3 print:hidden">
                    <x-select wire:model.live="siteFilter" label="Site filter" class="w-56">
                        <option value="all">All sites</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                </div>

                @if ($understaffed->isNotEmpty())
                    <section class="card-surface overflow-hidden print:hidden">
                        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Fill staffing gaps</h2>
                            <p class="text-xs text-zinc-500">{{ $understaffed->count() }} understaffed shift{{ $understaffed->count() === 1 ? '' : 's' }} on this date</p>
                        </div>
                        @foreach ($understaffed as $shift)
                            @php
                                $staffed = $shift->assignments->filter(fn ($a) => ! in_array(\App\Support\EnumHelper::value($a->status), ['cancelled', 'no_show'], true))->count();
                            @endphp
                            <div class="flex flex-col gap-3 border-t border-zinc-100 px-4 py-3 sm:flex-row sm:items-center dark:border-zinc-800" wire:key="gap-{{ $shift->id }}">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $shift->title }}</div>
                                    <div class="text-xs text-zinc-500">
                                        {{ $shift->site?->name }}
                                        @if ($shift->sitePost) · {{ $shift->sitePost->name }} @endif
                                        · {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}
                                        · {{ $staffed }}/{{ $shift->required_guards }} staffed
                                    </div>
                                    @error('pendingGuard.'.$shift->id) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <select wire:model="pendingGuard.{{ $shift->id }}" class="rounded-lg border border-zinc-200 bg-white px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-900">
                                        <option value="">Select guard</option>
                                        @foreach ($guards as $guard)
                                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                                        @endforeach
                                    </select>
                                    <x-button size="sm" wire:click="assignToShift({{ $shift->id }})">Assign</x-button>
                                </div>
                            </div>
                        @endforeach
                    </section>
                @endif

                @if ($reassignAssignmentId)
                    <section class="card-surface space-y-3 p-4 print:hidden">
                        <h2 class="text-sm font-semibold">Reassign guard</h2>
                        <x-select wire:model="reassignGuardId" label="New guard">
                            <option value="">Select guard</option>
                            @foreach ($guards as $guard)
                                <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                            @endforeach
                        </x-select>
                        @error('reassignGuardId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="flex gap-2">
                            <x-button wire:click="reassign">Reassign</x-button>
                            <x-button variant="secondary" wire:click="$set('reassignAssignmentId', null)">Cancel</x-button>
                        </div>
                    </section>
                @endif

                <x-data-table title="Roster for {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}">
                    <x-table.head>
                        <tr>
                            <x-table.th>Shift</x-table.th>
                            <x-table.th>Time</x-table.th>
                            <x-table.th>Site / Post</x-table.th>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Kit</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th class="print:hidden">Actions</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($assignments as $assignment)
                            <tr class="table-row-hover" wire:key="deploy-{{ $assignment->id }}">
                                <x-table.td>{{ $assignment->shift?->title ?? '—' }}</x-table.td>
                                <x-table.td mono>{{ $assignment->shift?->starts_at?->format('H:i') }}–{{ $assignment->shift?->ends_at?->format('H:i') }}</x-table.td>
                                <x-table.td>
                                    <div>{{ $assignment->shift?->site?->name }}</div>
                                    @if ($assignment->shift?->sitePost)
                                        <div class="text-xs text-zinc-500">{{ $assignment->shift->sitePost->name }}</div>
                                    @endif
                                    @if ($assignment->shift?->clientAccount)
                                        <div class="text-xs text-zinc-400">{{ $assignment->shift->clientAccount->name }}</div>
                                    @endif
                                </x-table.td>
                                <x-table.td>{{ $assignment->assignedGuard?->full_name ?? 'Unassigned' }}</x-table.td>
                                <x-table.td>
                                    @forelse ($assignment->equipmentAssignments as $issued)
                                        <div class="text-xs text-zinc-700 dark:text-zinc-300">{{ $issued->asset?->name ?? 'Asset' }}</div>
                                    @empty
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endforelse
                                </x-table.td>
                                <x-table.td><x-badge :status="$assignment->status" /></x-table.td>
                                <x-table.td class="print:hidden">
                                    <div class="flex flex-wrap gap-2">
                                        @if (\App\Support\EnumHelper::value($assignment->status) === 'assigned')
                                            <button type="button" class="text-xs font-medium text-accent-600 hover:underline" wire:click="confirmAssignment({{ $assignment->id }})">Confirm</button>
                                        @endif
                                        <button type="button" class="text-xs font-medium text-zinc-600 hover:underline" wire:click="openReassign({{ $assignment->id }})">Reassign</button>
                                        <button type="button" class="text-xs font-medium text-red-600 hover:underline" wire:click="unassign({{ $assignment->id }})" wire:confirm="Unassign this guard and return kit?">Unassign</button>
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="7">
                                <x-empty-state title="No deployments for this date" description="Use the deploy wizard or fill gaps above.">
                                    <x-slot:actions>
                                        <x-button href="{{ route('schedules.deploy', ['date' => $date]) }}">Deploy wizard</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
