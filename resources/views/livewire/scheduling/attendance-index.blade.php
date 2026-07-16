<div>
    @php
        $exceptions = $logs->filter(fn ($log) => in_array((string) ($log->status?->value ?? $log->status), ['late', 'no_show', 'early_leave'], true))->count();
        $onDuty = $logs->where('clock_out_at', null)->count();
    @endphp

    <x-page-shell
        title="Attendance"
        description="Clock events, geofence validation, and breaks."
        :breadcrumbs="[
            ['label' => 'Scheduler', 'href' => route('schedules.index')],
            ['label' => 'Attendance'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('schedules.reconciliation')">Reconciliation</x-button>
            <x-button wire:click="openBreakForm">Log break</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

            <x-flash-status />

            <div class="stat-grid">
                <x-stat-card compact label="Attendance logs" :value="$logs->count()" icon="schedules" />
                <x-stat-card compact label="Break logs" :value="$breaks->count()" icon="plan" tone="info" />
                <x-stat-card compact label="Exceptions" :value="$exceptions" icon="incidents" :tone="$exceptions ? 'warning' : 'success'" :href="route('schedules.reconciliation')" />
                <x-stat-card compact label="On duty" :value="$onDuty" icon="guards" tone="success" />
            </div>

            <x-page-toolbar>
                <x-slot:controls>
                    <x-button type="button" size="sm" variant="secondary" wire:click="previousDay">Previous</x-button>
                    <x-button type="button" size="sm" variant="secondary" wire:click="goToday" :disabled="$date === today()->toDateString()">Today</x-button>
                    <x-button type="button" size="sm" variant="secondary" wire:click="nextDay">Next</x-button>
                    <x-input wire:model.live="date" type="date" label="Date" class="w-auto text-sm" />
                    <x-filter-select wire:model.live="statusFilter">
                        <option value="all">All statuses</option>
                        @foreach($statusOptions as $status)
                            <option value="{{ $status->value }}">{{ str_replace('_', ' ', ucfirst($status->value)) }}</option>
                        @endforeach
                    </x-filter-select>
                </x-slot:controls>
            </x-page-toolbar>

            <div class="page-grid-2">
                <x-section-card title="Clock events" description="Geofence shows whether the guard was inside the site boundary at clock-in.">
                    <x-data-table>
                        <x-table.head>
                            <tr>
                                <x-table.th>Guard</x-table.th>
                                <x-table.th>Site</x-table.th>
                                <x-table.th>Clock in</x-table.th>
                                <x-table.th>Clock out</x-table.th>
                                <x-table.th>Geofence</x-table.th>
                                <x-table.th>Status</x-table.th>
                            </tr>
                        </x-table.head>
                        <tbody>
                            @forelse($logs as $log)
                                <tr wire:key="att-{{ $log->id }}">
                                    <x-table.td>{{ $log->assignedGuard?->full_name ?? '—' }}</x-table.td>
                                    <x-table.td muted>{{ $log->site?->name ?? '—' }}</x-table.td>
                                    <x-table.td muted>{{ $log->clock_in_at?->format('M j, H:i') }}</x-table.td>
                                    <x-table.td muted>{{ $log->clock_out_at?->format('M j, H:i') ?? '—' }}</x-table.td>
                                    <x-table.td>
                                        @if($log->geofence_validated)
                                            <span class="text-xs {{ $log->is_geofence_valid ? 'text-emerald-700' : 'text-red-600' }}">
                                                {{ $log->is_geofence_valid ? 'Valid' : 'Outside fence' }}
                                            </span>
                                        @else
                                            <span class="text-xs text-zinc-400">—</span>
                                        @endif
                                    </x-table.td>
                                    <x-table.td><x-badge :status="$log->status ?? 'on_time'" /></x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="6">
                                    <x-empty-state
                                        compact
                                        title="No clock events"
                                        description="Attendance for this day appears when guards clock in."
                                    >
                                        <x-slot:actions>
                                            <x-button size="sm" variant="secondary" :href="route('schedules.index', ['date' => $date])">Day roster</x-button>
                                            <x-button size="sm" variant="secondary" :href="route('schedules.reconciliation')">Reconciliation</x-button>
                                        </x-slot:actions>
                                    </x-empty-state>
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>

                <x-section-card title="Break logs">
                    <x-data-table>
                        <x-table.head>
                            <tr>
                                <x-table.th>Guard / log</x-table.th>
                                <x-table.th>Type</x-table.th>
                                <x-table.th>Started</x-table.th>
                                <x-table.th align="right"></x-table.th>
                            </tr>
                        </x-table.head>
                        <tbody>
                            @forelse($breaks as $break)
                                <tr wire:key="br-{{ $break->id }}">
                                    <x-table.td>{{ $break->attendanceLog?->assignedGuard?->full_name ?? 'Log #'.$break->attendance_log_id }}</x-table.td>
                                    <x-table.td>{{ $break->type }}</x-table.td>
                                    <x-table.td muted>{{ $break->started_at?->format('M j, H:i') }}</x-table.td>
                                    <x-table.td align="right">
                                        <button type="button" wire:click="deleteBreak({{ $break->id }})" wire:confirm="Remove this break?" class="text-xs text-red-600 hover:underline">Remove</button>
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="4">
                                    <x-empty-state compact title="No breaks" description="Log a meal or rest break against a clock-in.">
                                        <x-slot:actions>
                                            <x-button size="sm" wire:click="openBreakForm" :disabled="$logs->isEmpty()">Log break</x-button>
                                        </x-slot:actions>
                                    </x-empty-state>
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showBreakForm)
        <x-drawer title="Log break" description="Attach a meal or rest break to an active clock-in." width="md" close-method="closeBreakForm">
            <x-drawer-form wire:submit.prevent="saveBreak" submit-label="Save break" close-method="closeBreakForm">
                <x-form-section title="Break">
                    <x-select wire:model="breakForm.attendance_log_id" label="Attendance record *" class="sm:col-span-2">
                        <option value="">Select clock-in</option>
                        @foreach($logs as $log)
                            <option value="{{ $log->id }}">#{{ $log->id }} — {{ $log->assignedGuard?->full_name }} · {{ $log->clock_in_at?->format('M j, H:i') }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="breakForm.type" label="Break type">
                        <option value="meal">Meal</option>
                        <option value="rest">Rest</option>
                    </x-select>
                    <x-input wire:model="breakForm.started_at" label="Started at" type="datetime-local" required />
                    <x-input wire:model="breakForm.ended_at" label="Ended at" type="datetime-local" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
