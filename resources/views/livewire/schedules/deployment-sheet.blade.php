<div>
    <x-page-shell :embedded="$embedded" title="Deployment Sheet" description="Daily roster — assign, confirm, kit, and reassign from here.">
        <x-slot:actions>
            <x-button class="print:hidden" href="{{ route('schedules.deploy', ['date' => $date]) }}">Deploy wizard</x-button>
            <x-button class="print:hidden" variant="secondary" href="{{ route('schedules.index', ['date' => $date]) }}">Day roster</x-button>
        </x-slot:actions>

        <x-flash-status />

        <x-sub-sidebar-layout>
            @if (! $embedded)
                <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>
            @endif

            <div class="space-y-4">
                <div class="deployment-toolbar print:hidden">
                    <div class="deployment-date-nav" role="group" aria-label="Roster date navigation">
                        <x-button variant="secondary" wire:click="previousDay" aria-label="Previous day">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" /></svg>
                        </x-button>
                        <x-input wire:model.live="date" type="date" aria-label="Roster date" />
                        <x-button variant="secondary" wire:click="nextDay" aria-label="Next day">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6" /></svg>
                        </x-button>
                    </div>
                    <div class="deployment-filters">
                        <x-button variant="secondary" wire:click="goToday" :disabled="$date === today()->toDateString()">Today</x-button>
                        <x-select wire:model.live="siteFilter" aria-label="Site filter">
                            <option value="all">All sites</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </x-select>
                        <x-button variant="secondary" type="button" onclick="window.print()" aria-label="Print roster" title="Print roster">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8V3h10v5M7 17H4V8h16v9h-3M7 14h10v7H7z" /></svg>
                        </x-button>
                    </div>
                </div>

                <dl class="deployment-summary" aria-label="Deployment totals">
                    <div><dt>Assignments</dt><dd>{{ $stats['assignments'] }}</dd></div>
                    <div><dt>Confirmed</dt><dd>{{ $stats['confirmed'] }}</dd></div>
                    <div><dt>Pending confirm</dt><dd>{{ $stats['pending'] }}</dd></div>
                    <div><dt>Kit issued</dt><dd>{{ $stats['kit'] }}</dd></div>
                    <div><dt>Staffing gaps</dt><dd>{{ $stats['gaps'] }}</dd></div>
                </dl>

                @if ($understaffed->isNotEmpty())
                    <x-section-card
                        title="Fill staffing gaps"
                        :description="$understaffed->count().' understaffed shift'.($understaffed->count() === 1 ? '' : 's').' on this date'"
                        class="print:hidden"
                        flush
                    >
                        @foreach ($understaffed as $shift)
                            @php
                                $staffed = $shift->activeAssignmentsCount();
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
                                        @if (! $shift->starts_at->isSameDay($shift->ends_at))
                                            <span class="block">Ends {{ $shift->ends_at->format('M j, Y') }}</span>
                                        @endif
                                    </div>
                                    @error('pendingGuard.'.$shift->id) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="assign-panel-actions w-full sm:w-auto">
                                    <x-select wire:model.live="pendingGuard.{{ $shift->id }}" label="Assign guard" class="sm:w-56">
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

                <x-data-table class="deployment-roster" title="Roster for {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}">
                    <x-table.head role="rowgroup">
                        <tr role="row">
                            <x-table.th role="columnheader" scope="col">Shift</x-table.th>
                            <x-table.th role="columnheader" scope="col">Time</x-table.th>
                            <x-table.th role="columnheader" scope="col">Site / Post</x-table.th>
                            <x-table.th role="columnheader" scope="col">Guard</x-table.th>
                            <x-table.th role="columnheader" scope="col">Kit</x-table.th>
                            <x-table.th role="columnheader" scope="col">Status</x-table.th>
                            <x-table.th role="columnheader" scope="col" class="print:hidden">Actions</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody role="rowgroup">
                        @forelse($assignments as $assignment)
                            <tr role="row" class="table-row-hover" wire:key="deploy-{{ $assignment->id }}">
                                <x-table.td role="cell" data-label="Shift" class="font-medium text-zinc-900 dark:text-zinc-100">{{ $assignment->shift?->title ?? '—' }}</x-table.td>
                                <x-table.td role="cell" data-label="Time" class="tabular-nums">
                                    <span class="deployment-cell-label" aria-hidden="true">Time</span>
                                    <span class="block whitespace-nowrap">{{ $assignment->shift?->starts_at?->format('H:i') }}–{{ $assignment->shift?->ends_at?->format('H:i') }}</span>
                                    @if ($assignment->shift && ! $assignment->shift->starts_at->isSameDay($assignment->shift->ends_at))
                                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">Ends {{ $assignment->shift->ends_at->format('M j, Y') }}</span>
                                    @endif
                                </x-table.td>
                                <x-table.td role="cell" data-label="Site / Post">
                                    <span class="deployment-cell-label" aria-hidden="true">Site / Post</span>
                                    <div class="text-zinc-900 dark:text-zinc-100">{{ $assignment->shift?->site?->name }}</div>
                                    @if ($assignment->shift?->sitePost)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $assignment->shift->sitePost->name }}</div>
                                    @endif
                                    @if ($assignment->shift?->clientAccount)
                                        <div class="text-xs text-zinc-400">{{ $assignment->shift->clientAccount->name }}</div>
                                    @endif
                                </x-table.td>
                                <x-table.td role="cell" data-label="Guard">
                                    <span class="deployment-cell-label" aria-hidden="true">Guard</span>
                                    {{ $assignment->assignedGuard?->full_name ?? 'Unassigned' }}
                                </x-table.td>
                                <x-table.td role="cell" data-label="Kit">
                                    <span class="deployment-cell-label" aria-hidden="true">Kit</span>
                                    @forelse ($assignment->equipmentAssignments as $issued)
                                        <div class="text-xs text-zinc-700 dark:text-zinc-300">{{ $issued->asset?->name ?? 'Asset' }}</div>
                                    @empty
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endforelse
                                </x-table.td>
                                <x-table.td role="cell" data-label="Status">
                                    <span class="deployment-cell-label" aria-hidden="true">Status</span>
                                    <x-badge :status="$assignment->status" />
                                </x-table.td>
                                <x-table.td role="cell" data-label="Actions" class="print:hidden">
                                    <div class="table-inline-actions">
                                        @if (\App\Support\EnumHelper::value($assignment->status) === 'assigned')
                                            <button type="button" class="deployment-action table-action" aria-label="Confirm assignment for {{ $assignment->assignedGuard?->full_name ?? 'Unassigned' }}" wire:click="confirmAssignment({{ $assignment->id }})" wire:loading.attr="disabled" wire:target="confirmAssignment({{ $assignment->id }})">Confirm</button>
                                        @endif
                                        <button type="button" class="deployment-action table-action" aria-label="Reassign {{ $assignment->assignedGuard?->full_name ?? 'Unassigned' }}" wire:click="openReassign({{ $assignment->id }})">Reassign</button>
                                        <button type="button" class="deployment-action table-action text-red-600" aria-label="Unassign {{ $assignment->assignedGuard?->full_name ?? 'Unassigned' }}" wire:click="unassign({{ $assignment->id }})" wire:loading.attr="disabled" wire:target="unassign({{ $assignment->id }})" wire:confirm="Unassign this guard? Issued kit stays with this guard until its physical return is recorded in Assets.">Unassign</button>
                                    </div>
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="7">
                                <x-empty-state class="scheduler-empty-state" title="No deployments for this date" description="Use the deploy wizard or fill gaps above.">
                                    <x-slot:actions>
                                        <x-button href="{{ route('schedules.deploy', ['date' => $date]) }}">Deploy wizard</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Issued kit stays with the original guard after unassignment or reassignment.
                    Record physical returns in <a class="table-action" href="{{ route('assets.index') }}">Assets</a>.
                </p>
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($reassignAssignmentId)
        <div x-data x-trap.inert.noscroll="true" x-on:keydown.escape.stop="$wire.closeReassign()">
            <x-drawer title="Reassign guard" description="Assign a replacement guard. Issued kit stays with the original guard until its physical return is recorded in Assets; it is not transferred to the replacement." width="md" close-method="closeReassign">
                <x-drawer-form wire:submit="reassign" submit-label="Reassign" close-method="closeReassign" target="reassign">
                    <x-form-section title="New assignment">
                        <x-select wire:model.live="reassignGuardId" label="New guard *" class="sm:col-span-2">
                            <option value="">Select guard</option>
                            @foreach ($guards as $guard)
                                <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                            @endforeach
                        </x-select>
                    </x-form-section>
                </x-drawer-form>
            </x-drawer>
        </div>
    @endif
</div>
