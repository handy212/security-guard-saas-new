<div>
    <x-page-shell title="Day roster" description="Create shifts, assign guards, and staff the selected day.">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('schedules.calendar') }}">Calendar</x-button>
            <x-button variant="secondary" href="{{ route('schedules.deployment-sheet', ['date' => $date]) }}">Deployment</x-button>
            <x-button wire:click="openForm">Create shift</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

            <x-flash-status />

            <div class="stat-grid">
                <x-stat-card compact label="Shifts" :value="$stats['shifts_today']" icon="schedules" />
                <x-stat-card compact label="Underfilled" :value="$stats['open_shifts']" icon="pause" :tone="$stats['open_shifts'] ? 'warning' : 'success'" :href="route('schedules.open-shifts')" />
                <x-stat-card compact label="Staffed" :value="$stats['staffed']" icon="check" tone="success" />
                <x-stat-card compact label="On duty" :value="$stats['on_duty']" icon="guards" tone="info" :href="route('schedules.attendance')" />
            </div>

            @if ($stats['pending_confirmations'] || $stats['pending_bids'] || $stats['pending_swaps'] || $stats['pending_leave'])
                <div class="mb-4 flex flex-wrap gap-2 text-xs">
                    @if ($stats['pending_confirmations'])
                        <a href="{{ route('schedules.shift-status') }}" class="rounded-full bg-amber-50 px-3 py-1 font-medium text-amber-800 hover:bg-amber-100">{{ $stats['pending_confirmations'] }} pending confirmations</a>
                    @endif
                    @if ($stats['pending_bids'])
                        <a href="{{ route('schedules.open-shifts') }}" class="rounded-full bg-blue-50 px-3 py-1 font-medium text-blue-800 hover:bg-blue-100">{{ $stats['pending_bids'] }} open-shift bids</a>
                    @endif
                    @if ($stats['pending_swaps'])
                        <a href="{{ route('schedules.shift-exchange') }}" class="rounded-full bg-violet-50 px-3 py-1 font-medium text-violet-800 hover:bg-violet-100">{{ $stats['pending_swaps'] }} swap requests</a>
                    @endif
                    @if ($stats['pending_leave'])
                        <a href="{{ route('schedules.time-off') }}" class="rounded-full bg-zinc-100 px-3 py-1 font-medium text-zinc-700 hover:bg-zinc-200">{{ $stats['pending_leave'] }} leave requests</a>
                    @endif
                </div>
            @endif

            <x-page-toolbar class="mb-4">
                <x-slot:controls>
                    <x-button type="button" variant="secondary" size="sm" wire:click="previousDay">Previous</x-button>
                    <x-button type="button" variant="secondary" size="sm" wire:click="goToday" :disabled="$date === today()->toDateString()">Today</x-button>
                    <x-button type="button" variant="secondary" size="sm" wire:click="nextDay">Next</x-button>
                    <x-input wire:model.live="date" type="date" label="Date" class="w-auto text-sm" />
                </x-slot:controls>
            </x-page-toolbar>

            <div class="grid gap-3">
                @forelse($shifts as $shift)
                    <div class="card-surface p-3" wire:key="shift-{{ $shift->id }}">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold">{{ $shift->title }}</h3>
                                    <x-badge :status="$shift->status" />
                                </div>
                                <p class="text-xs text-zinc-500">
                                    {{ $shift->site?->name }}
                                    @if ($shift->sitePost)
                                        · {{ $shift->sitePost->name }}
                                    @endif
                                    · {{ $shift->starts_at?->format('M j, H:i') }} – {{ $shift->ends_at?->format('H:i') }}
                                </p>
                                @if ($shift->notes)
                                    <p class="mt-1 text-xs text-zinc-600">{{ $shift->notes }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 items-start gap-2">
                                <div @class([
                                    'rounded-md px-2 py-1 text-xs font-medium',
                                    'bg-emerald-50 text-emerald-800' => $shift->activeAssignmentsCount() >= $shift->required_guards,
                                    'bg-amber-50 text-amber-800' => $shift->activeAssignmentsCount() < $shift->required_guards,
                                ])>
                                    {{ $shift->activeAssignmentsCount() }}/{{ $shift->required_guards }} assigned
                                </div>
                                <x-row-menu>
                                    <x-row-menu-item wire:click="editShift({{ $shift->id }})">Edit</x-row-menu-item>
                                    <x-row-menu-item :href="route('schedules.open-shifts')">View open shifts</x-row-menu-item>
                                    <x-row-menu-item wire:click="cancelShift({{ $shift->id }})" wire:confirm="Cancel this shift?" danger>Cancel shift</x-row-menu-item>
                                </x-row-menu>
                            </div>
                        </div>

                        @php $activeAssignments = $shift->activeAssignments(); @endphp
                        @if ($activeAssignments->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach ($activeAssignments as $assignment)
                                    <span class="inline-flex items-center gap-1 rounded bg-zinc-100 px-2 py-0.5 text-xs">
                                        {{ $assignment->assignedGuard?->full_name ?? 'Guard' }}
                                        <x-badge :status="$assignment->status" />
                                        <button type="button" wire:click="unassignGuard({{ $assignment->id }})" wire:confirm="Remove this guard from the shift?" class="text-zinc-400 hover:text-red-600" title="Unassign">×</button>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if($activeAssignments->count() < $shift->required_guards)
                            <div class="mt-3 flex flex-col gap-2 border-t border-zinc-100 pt-3 sm:flex-row sm:items-end">
                                <x-select wire:model="pendingGuard.{{ $shift->id }}" label="Assign guard" class="sm:max-w-xs">
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
                            @error("pendingGuard.{$shift->id}") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            @if(filled($pendingGuard[$shift->id] ?? null) && in_array((int) $pendingGuard[$shift->id], $guardsOnLeaveIds, true))
                                <p class="mt-1 text-xs text-amber-700">This guard has approved leave on {{ \Carbon\Carbon::parse($date)->format('M j') }}. Assignment will be blocked.</p>
                            @endif
                            @if ($guards->isEmpty())
                                <p class="mt-2 text-xs text-amber-700">
                                    No verified guards available.
                                    @if ($unverifiedGuardCount > 0)
                                        <a href="{{ route('guards.kyg') }}" class="font-medium underline">Complete Know Your Guard vetting</a> for {{ $unverifiedGuardCount }} pending officer{{ $unverifiedGuardCount === 1 ? '' : 's' }}.
                                    @endif
                                </p>
                            @endif
                        @endif
                    </div>
                @empty
                    <x-empty-state title="No shifts" description="Create a shift for this day, or apply a template from Templates.">
                        <x-slot:actions>
                            <x-button size="sm" wire:click="openForm">Create shift</x-button>
                            <x-button size="sm" variant="secondary" :href="route('schedules.templates')">Templates</x-button>
                        </x-slot:actions>
                    </x-empty-state>
                @endforelse
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingShiftId ? 'Edit shift' : 'Create shift'" width="lg">
            <x-drawer-form wire:submit.prevent="save" :submit-label="$editingShiftId ? 'Save changes' : 'Create shift'">
                <x-select wire:model.live="form.client_account_id" label="Client *">
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
                <x-select wire:model="form.site_post_id" label="Post" :disabled="! $form['site_id']">
                    <option value="">None</option>
                    @foreach ($postsForSite as $post)
                        <option value="{{ $post->id }}">{{ $post->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.title" label="Title *" class="sm:col-span-2" />
                <x-input wire:model="form.starts_at" label="Starts *" type="datetime-local" />
                <x-input wire:model="form.ends_at" label="Ends *" type="datetime-local" />
                <x-input wire:model="form.required_guards" label="Required guards" type="number" min="1" />
                <x-input wire:model="form.billing_rate" label="Shift charge" type="number" step="0.01" hint="Fixed amount billed for this shift" />
                <x-textarea wire:model="form.notes" label="Notes" rows="2" class="sm:col-span-2" />
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
