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

                <div class="date-nav print:hidden">
                    <x-select wire:model.live="siteFilter" label="Site filter" class="w-56">
                        <option value="all">All sites</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                </div>

                @if ($understaffed->isNotEmpty())
                    <x-section-card
                        title="Fill staffing gaps"
                        :description="$understaffed->count().' understaffed shift'.($understaffed->count() === 1 ? '' : 's').' on this date'"
                        class="print:hidden"
                        flush
                    >
                        @foreach ($understaffed as $shift)
                            @php
                                $staffed = $shift->assignments->filter(fn ($a) => ! in_array(\App\Support\EnumHelper::value($a->status), ['cancelled', 'no_show'], true))->count();
                            @endphp
                            <div class="list-row-start flex-col gap-3 sm:flex-row sm:items-end" wire:key="gap-{{ $shift->id }}">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $shift->title }}</div>
                                        <span class="staffing-pill staffing-pill-low">{{ $staffed }}/{{ $shift->required_guards }}</span>
                                    </div>
                                    <div class="mt-0.5 text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                        {{ $shift->site?->name }}
                                        @if ($shift->sitePost) · {{ $shift->sitePost->name }} @endif
                                        · {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}
                                    </div>
                                    @error('pendingGuard.'.$shift->id) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="assign-panel-actions w-full sm:w-auto">
                                    <x-select wire:model="pendingGuard.{{ $shift->id }}" label="Assign guard" class="sm:w-56">
                                        <option value="">Select guard</option>
                                        @foreach ($guards as $guard)
                                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                                        @endforeach
                                    </x-select>
                                    <x-button size="sm" wire:click="assignToShift({{ $shift->id }})" wire:loading.attr="disabled" wire:target="assignToShift({{ $shift->id }})" :disabled="empty($pendingGuard[$shift->id] ?? null)">
                                        <span wire:loading.remove wire:target="assignToShift({{ $shift->id }})">Assign</span>
                                        <span wire:loading wire:target="assignToShift({{ $shift->id }})">Assigning…</span>
                                    </x-button>
                                </div>
                            </div>
                        @endforeach
                    </x-section-card>
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
                                <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $assignment->shift?->title ?? '—' }}</x-table.td>
                                <x-table.td mono class="tabular-nums">{{ $assignment->shift?->starts_at?->format('H:i') }}–{{ $assignment->shift?->ends_at?->format('H:i') }}</x-table.td>
                                <x-table.td>
                                    <div class="text-zinc-900 dark:text-zinc-100">{{ $assignment->shift?->site?->name }}</div>
                                    @if ($assignment->shift?->sitePost)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $assignment->shift->sitePost->name }}</div>
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
                                    <div class="table-inline-actions">
                                        @if (\App\Support\EnumHelper::value($assignment->status) === 'assigned')
                                            <button type="button" class="table-action" wire:click="confirmAssignment({{ $assignment->id }})">Confirm</button>
                                        @endif
                                        <button type="button" class="table-action" wire:click="openReassign({{ $assignment->id }})">Reassign</button>
                                        <button type="button" class="table-action text-red-600" wire:click="unassign({{ $assignment->id }})" wire:confirm="Unassign this guard and return kit?">Unassign</button>
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

    @if ($reassignAssignmentId)
        <x-drawer title="Reassign guard" description="Move this assignment to another verified guard." width="md" close-method="closeReassign">
            <x-drawer-form wire:submit.prevent="reassign" submit-label="Reassign" close-method="closeReassign" target="reassign">
                <x-form-section title="New assignment">
                    <x-select wire:model="reassignGuardId" label="New guard *" class="sm:col-span-2">
                        <option value="">Select guard</option>
                        @foreach ($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
