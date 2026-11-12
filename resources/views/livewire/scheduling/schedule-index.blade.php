<div>
    <x-page-shell :embedded="$embedded"
        title="Day roster"
        description="Create shifts, assign guards, and staff the selected day."
        :breadcrumbs="[
            ['label' => 'Scheduler', 'href' => route('schedules.index')],
            ['label' => 'Day roster'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('schedules.calendar') }}">Calendar</x-button>
            <x-button variant="secondary" href="{{ route('schedules.deployment-sheet', ['date' => $date]) }}">Deployment</x-button>
            <x-button wire:click="openForm">Create shift</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            @if (! $embedded)
                <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>
            @endif

            <x-flash-status />

            <div class="stat-grid">
                <x-stat-card compact label="Shifts" :value="$stats['shifts_today']" icon="schedules" />
                <x-stat-card compact label="Underfilled" :value="$stats['open_shifts']" icon="pause" :tone="$stats['open_shifts'] ? 'warning' : 'success'" :href="route('schedules.open-shifts')" />
                <x-stat-card compact label="Staffed" :value="$stats['staffed']" icon="check" tone="success" />
                <x-stat-card compact label="On duty" :value="$stats['on_duty']" icon="guards" tone="info" :href="route('schedules.attendance')" />
            </div>

            @if ($stats['pending_confirmations'] || $stats['pending_bids'] || $stats['pending_swaps'] || $stats['pending_leave'])
                <div class="flex flex-wrap gap-1.5">
                    @if ($stats['pending_confirmations'])
                        <a href="{{ route('schedules.shift-status') }}" class="status-chip status-chip-warning">
                            <span class="tabular-nums font-semibold">{{ $stats['pending_confirmations'] }}</span> {{ $stats['pending_confirmations'] === 1 ? 'confirmation' : 'confirmations' }}
                        </a>
                    @endif
                    @if ($stats['pending_bids'])
                        <a href="{{ route('schedules.open-shifts') }}" class="status-chip status-chip-info">
                            <span class="tabular-nums font-semibold">{{ $stats['pending_bids'] }}</span> {{ $stats['pending_bids'] === 1 ? 'open-shift bid' : 'open-shift bids' }}
                        </a>
                    @endif
                    @if ($stats['pending_swaps'])
                        <a href="{{ route('schedules.shift-exchange') }}" class="status-chip status-chip-info">
                            <span class="tabular-nums font-semibold">{{ $stats['pending_swaps'] }}</span> {{ $stats['pending_swaps'] === 1 ? 'swap request' : 'swap requests' }}
                        </a>
                    @endif
                    @if ($stats['pending_leave'])
                        <a href="{{ route('schedules.time-off') }}" class="status-chip status-chip-neutral">
                            <span class="tabular-nums font-semibold">{{ $stats['pending_leave'] }}</span> {{ $stats['pending_leave'] === 1 ? 'leave request' : 'leave requests' }}
                        </a>
                    @endif
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3" aria-label="Roster date navigation">
                <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ \Carbon\Carbon::parse($date)->format('l, M j, Y') }}</h2>
                <div class="date-nav">
                    <x-button type="button" variant="secondary" size="sm" wire:click="previousDay" aria-label="Previous day">Previous</x-button>
                    <x-button type="button" variant="secondary" size="sm" wire:click="goToday" :disabled="$date === today()->toDateString()">Today</x-button>
                    <x-button type="button" variant="secondary" size="sm" wire:click="nextDay" aria-label="Next day">Next</x-button>
                    <x-input wire:model.live="date" type="date" aria-label="Roster date" class="w-auto text-sm" />
                </div>
            </div>

            <div class="grid gap-3">
                @forelse($shifts as $shift)
                    @php
                        $activeAssignments = $shift->activeAssignments()->unique('guard_id');
                        $staffed = $activeAssignments->count() >= $shift->required_guards;
                    @endphp
                    <article @class([
                        'card-surface overflow-hidden',
                        'ring-1 ring-amber-200/80 dark:ring-amber-900/40' => ! $staffed,
                    ]) wire:key="shift-{{ $shift->id }}">
                        <div class="card-header">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="card-header-title">{{ $shift->title }}</h3>
                                    <x-badge :status="$shift->status" />
                                </div>
                                <p class="card-header-meta tabular-nums">
                                    {{ $shift->site?->name }}
                                    @if ($shift->sitePost)
                                        · {{ $shift->sitePost->name }}
                                    @endif
                                    · {{ $shift->starts_at?->format('M j, H:i') }} → {{ $shift->ends_at?->format($shift->starts_at?->isSameDay($shift->ends_at) ? 'H:i' : 'M j, H:i') }}
                                </p>
                                @if ($shift->notes)
                                    <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">{{ $shift->notes }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-start gap-2">
                                <div @class(['staffing-pill', $staffed ? 'staffing-pill-ok' : 'staffing-pill-low'])>
                                    {{ $activeAssignments->count() }}/{{ $shift->required_guards }} assigned
                                </div>
                                <x-row-menu>
                                    <x-row-menu-item wire:click="editShift({{ $shift->id }})">Edit</x-row-menu-item>
                                    <x-row-menu-item :href="route('schedules.open-shifts')">View open shifts</x-row-menu-item>
                                    <x-row-menu-item wire:click="cancelShift({{ $shift->id }})" wire:confirm="Cancel this shift?" danger>Cancel shift</x-row-menu-item>
                                </x-row-menu>
                            </div>
                        </div>

                        <div class="space-y-3 px-4 py-3">
                            @if ($activeAssignments->isNotEmpty())
                                <div>
                                    <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Assigned</p>
                                    <div class="grid gap-2">
                                        @foreach ($activeAssignments as $assignment)
                                            <div class="flex min-w-0 flex-col gap-3 rounded-md border border-zinc-200/90 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-800/60 sm:flex-row sm:items-center sm:justify-between">
                                                <div class="flex min-w-0 flex-wrap items-center gap-2 text-xs">
                                                    <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $assignment->assignedGuard?->full_name ?? 'Guard' }}</span>
                                                    @php
                                                        $confirmation = $assignment->confirmations->sortByDesc('created_at')->first();
                                                        $attendance = $assignment->attendanceLogs->sortByDesc('recorded_at')->first();
                                                        $confirmationStatus = $confirmation?->status?->value ?? $confirmation?->status;
                                                        $attendanceStatus = (string) ($attendance?->status?->value ?? $attendance?->status ?? '');
                                                        $attendanceLabel = match ($attendanceStatus) {
                                                            'late' => 'Late arrival',
                                                            'no_show' => 'Absent',
                                                            'early_leave' => 'Early leave',
                                                            'on_time' => 'On time',
                                                            'scheduled' => 'Scheduled',
                                                            'completed' => 'Completed',
                                                            '' => 'No attendance recorded',
                                                            default => str_replace('_', ' ', ucfirst($attendanceStatus)),
                                                        };
                                                    @endphp
                                                    @if ($confirmationStatus !== ($assignment->status?->value ?? $assignment->status))
                                                        <x-badge :status="$assignment->status" />
                                                    @endif
                                                    <span @class(['status-chip', 'status-chip-warning' => $confirmationStatus === 'pending', 'status-chip-info' => $confirmationStatus === 'confirmed', 'status-chip-neutral' => ! in_array($confirmationStatus, ['pending', 'confirmed'], true)])>
                                                        {{ $confirmationStatus === 'pending' ? 'Confirmation pending' : ($confirmationStatus ? ucfirst($confirmationStatus) : 'Confirmation not recorded') }}
                                                    </span>
                                                    <span @class(['status-chip', 'status-chip-warning' => in_array($attendanceStatus, ['late', 'early_leave'], true), 'status-chip-warning' => $attendanceStatus === 'no_show', 'status-chip-info' => in_array($attendanceStatus, ['on_time', 'completed'], true), 'status-chip-neutral' => ! in_array($attendanceStatus, ['late', 'early_leave', 'no_show', 'on_time', 'completed'], true)])>
                                                        Attendance: {{ $attendanceLabel }}
                                                    </span>
                                                </div>
                                                <div class="flex shrink-0 flex-wrap items-center gap-3">
                                                    @can('guards.manage')
                                                        @if ($assignment->assignedGuard)
                                                            <a href="{{ route('guards.show', ['guard' => $assignment->assignedGuard, 'tab' => 'qualifications']) }}" class="page-link text-xs" wire:navigate aria-label="View qualifications for {{ $assignment->assignedGuard->full_name }}">Qualifications</a>
                                                            <a href="{{ route('guards.show', ['guard' => $assignment->assignedGuard, 'tab' => 'availability']) }}" class="page-link text-xs" wire:navigate aria-label="View availability for {{ $assignment->assignedGuard->full_name }}">Availability</a>
                                                        @endif
                                                    @endcan
                                                    <button type="button" wire:click="unassignGuard({{ $assignment->id }})" wire:confirm="Remove this guard from the shift? Issued kit stays with this guard until its physical return is recorded in Assets." class="text-xs text-zinc-500 transition hover:text-red-600 dark:text-zinc-400" title="Unassign" aria-label="Unassign {{ $assignment->assignedGuard?->full_name ?? 'guard' }}">Remove</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">No guards assigned yet.</p>
                            @endif

                            @if (! $staffed)
                                <div class="assign-panel">
                                    <p class="text-xs font-medium text-amber-900 dark:text-amber-200">
                                        Needs {{ $shift->required_guards - $activeAssignments->count() }} more guard{{ ($shift->required_guards - $activeAssignments->count()) === 1 ? '' : 's' }}
                                    </p>
                                    <div class="assign-panel-actions">
                                        <x-select wire:model.live="pendingGuard.{{ $shift->id }}" label="Assign guard" class="sm:max-w-xs sm:flex-1">
                                            <option value="">Select guard</option>
                                            @foreach ($guards as $guard)
                                                <option value="{{ $guard->id }}">
                                                    {{ $guard->full_name }}{{ in_array($guard->id, $guardsOnLeaveIds, true) ? ' — on approved leave' : '' }}
                                                </option>
                                            @endforeach
                                        </x-select>
                                        <x-button type="button" size="sm" wire:click="assignGuard({{ $shift->id }})" wire:loading.attr="disabled" wire:target="assignGuard({{ $shift->id }})" :disabled="empty($pendingGuard[$shift->id] ?? null)">
                                            <span wire:loading.remove wire:target="assignGuard({{ $shift->id }})">Assign</span>
                                            <span wire:loading wire:target="assignGuard({{ $shift->id }})">Assigning…</span>
                                        </x-button>
                                    </div>
                                    @error("pendingGuard.{$shift->id}") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    @if(filled($pendingGuard[$shift->id] ?? null) && in_array((int) $pendingGuard[$shift->id], $guardsOnLeaveIds, true))
                                        <p class="text-xs text-amber-800 dark:text-amber-300">This guard has approved leave on {{ \Carbon\Carbon::parse($date)->format('M j') }}. Assignment will be blocked.</p>
                                    @endif
                                    @if ($guards->isEmpty())
                                        <p class="text-xs text-amber-800 dark:text-amber-300">
                                            No verified guards available.
                                            @if ($unverifiedGuardCount > 0)
                                                <a href="{{ route('guards.kyg') }}" class="page-link">Complete Know Your Guard vetting</a> for {{ $unverifiedGuardCount }} pending officer{{ $unverifiedGuardCount === 1 ? '' : 's' }}.
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </article>
                @empty
                    <x-empty-state class="scheduler-empty-state" title="No shifts" description="Create a shift for this day after you have clients, sites, and guards.">
                        <x-slot:actions>
                            <x-button size="sm" wire:click="openForm">Create shift</x-button>
                            <x-button size="sm" variant="secondary" :href="route('schedules.templates')">Templates</x-button>
                            <x-button size="sm" variant="secondary" :href="route('sites.index')">View sites</x-button>
                        </x-slot:actions>
                    </x-empty-state>
                @endforelse
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showForm)
        @php
            $shiftDrawerDescription = $editingShiftId
                ? 'Update location, window, and billing for this shift.'
                : 'For '.\Carbon\Carbon::parse($date)->format('D, M j').' — pick the site and window, then assign guards on the roster.';
        @endphp
        <x-drawer
            :title="$editingShiftId ? 'Edit shift' : 'Create shift'"
            :description="$shiftDrawerDescription"
            width="lg"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit="save" :submit-label="$editingShiftId ? 'Save changes' : 'Create shift'" close-method="closeDrawer">
                <x-form-section title="Location">
                    <x-select wire:model.live="form.client_account_id" label="Client *" class="sm:col-span-2">
                        <option value="">Select client</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model.live="form.site_id" label="Site *" :disabled="! $form['client_account_id']">
                        <option value="">Select site</option>
                        @foreach ($sitesForClient as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model.live="form.site_post_id" label="Post" :disabled="! $form['site_id']">
                        <option value="">None</option>
                        @foreach ($postsForSite as $post)
                            <option value="{{ $post->id }}">{{ $post->name }}</option>
                        @endforeach
                    </x-select>
                </x-form-section>

                <x-form-section title="Shift window">
                    <x-input wire:model.live="form.title" label="Title *" class="sm:col-span-2" />
                    <x-input wire:model.live="form.starts_at" label="Starts *" type="datetime-local" />
                    <x-input wire:model.live="form.ends_at" label="Ends *" type="datetime-local" />
                    <x-input wire:model.live="form.required_guards" label="Required guards" type="number" min="1" class="sm:col-span-2" />
                </x-form-section>

                <x-form-section title="Billing" description="Optional — used for client charging and payroll export.">
                    <x-input wire:model.live="form.billing_rate" label="Shift charge" type="number" step="0.01" hint="Fixed amount billed for this shift" />
                    <x-input wire:model.live="form.billable_hours" label="Billable hours" type="number" step="0.25" min="0" hint="Optional override for payroll export" />
                    <x-textarea wire:model.live="form.notes" label="Notes" rows="2" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
