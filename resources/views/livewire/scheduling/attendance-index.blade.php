<div>
    @php
        $exceptions = $logs->filter(fn ($log) => in_array((string) ($log->status?->value ?? $log->status), ['late', 'no_show', 'early_leave'], true))->count();
    @endphp

    <x-page-shell title="Attendance" description="Clock events, geofence validation, and breaks.">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('attendance.reconciliation') }}">Reconciliation</x-button>
        </x-slot:actions>
        <x-schedules-nav />
        <x-flash-status />

        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <x-button type="button" size="sm" variant="secondary" wire:click="previousDay">← Prev</x-button>
                <x-button type="button" size="sm" variant="secondary" wire:click="goToday">Today</x-button>
                <x-button type="button" size="sm" variant="secondary" wire:click="nextDay">Next →</x-button>
                <x-input wire:model.live="date" type="date" label="Date" class="w-auto" />
            </div>
            <x-select wire:model.live="statusFilter" label="Status" class="sm:max-w-xs">
                <option value="all">All statuses</option>
                @foreach($statusOptions as $status)
                    <option value="{{ $status->value }}">{{ str_replace('_', ' ', ucfirst($status->value)) }}</option>
                @endforeach
            </x-select>
        </div>

        <div class="stat-grid">
            <x-stat-card compact label="Attendance logs" :value="$logs->count()" icon="schedules" />
            <x-stat-card compact label="Break logs" :value="$breaks->count()" icon="plan" tone="info" />
            <x-stat-card compact label="Exceptions" :value="$exceptions" icon="incidents" :tone="$exceptions ? 'warning' : 'success'" />
            <x-stat-card compact label="On duty" :value="$logs->where('clock_out_at', null)->count()" icon="guards" tone="success" />
        </div>

        <x-form-card title="Log break">
            <form wire:submit.prevent="saveBreak" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <x-select wire:model="breakForm.attendance_log_id" label="Attendance record *">
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
                <x-input wire:model="breakForm.ended_at" label="Ended at" type="datetime-local" />
                <div class="xl:col-span-4"><x-button type="submit">Save break</x-button></div>
            </form>
        </x-form-card>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <x-section-card title="Clock events" description="Geofence shows whether the guard was inside the site boundary at clock-in.">
                <x-data-table>
                <x-table.head><tr><x-table.th>Guard</x-table.th><x-table.th>Site</x-table.th><x-table.th>Clock in</x-table.th><x-table.th>Clock out</x-table.th><x-table.th>Geofence</x-table.th><x-table.th>Status</x-table.th></tr></x-table.head>
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
                        <x-table.empty colspan="6"><x-empty-state compact title="No logs" /></x-table.empty>
                    @endforelse
                </tbody>
                </x-data-table>
            </x-section-card>
            <x-section-card title="Break logs">
            <x-data-table>
                <x-table.head><tr><x-table.th>Guard / log</x-table.th><x-table.th>Type</x-table.th><x-table.th>Started</x-table.th><x-table.th align="right"></x-table.th></tr></x-table.head>
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
                        <x-table.empty colspan="4"><x-empty-state compact title="No breaks" /></x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
            </x-section-card>
        </div>
    </x-page-shell>
</div>
