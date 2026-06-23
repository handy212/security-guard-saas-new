<div>
    <x-page-header title="Attendance & Timekeeping" description="Clock events, geofence validation, and break tracking." />

    @php
        $exceptions = $logs->filter(fn ($log) => in_array($log->status, ['late', 'no_show', 'early_leave'], true))->count();
    @endphp

    <div class="grid gap-4 px-6 pb-4 md:grid-cols-3">
        <x-stat-card label="Attendance logs" :value="$logs->count()" />
        <x-stat-card label="Break logs" :value="$breaks->count()" tone="info" />
        <x-stat-card label="Exceptions" :value="$exceptions" :tone="$exceptions ? 'warning' : 'success'" />
    </div>

    <div class="space-y-6 p-6 pt-0">
        <x-form-card title="Log break" description="Record meal or rest breaks against an attendance log.">
            <form wire:submit="saveBreak" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-input wire:model="breakForm.attendance_log_id" label="Attendance log ID" placeholder="123" required />
                <x-select wire:model="breakForm.type" label="Break type">
                    <option value="meal">Meal</option>
                    <option value="rest">Rest</option>
                </x-select>
                <x-input wire:model="breakForm.started_at" label="Started at" type="datetime-local" required />
                <x-input wire:model="breakForm.ended_at" label="Ended at" type="datetime-local" />
                <div class="md:col-span-2 xl:col-span-4">
                    <x-button type="submit">Save break</x-button>
                </div>
            </form>
        </x-form-card>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-data-table title="Recent attendance">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Guard</th>
                        <th class="px-4 py-3">Site</th>
                        <th class="px-4 py-3">Clock in</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="table-row-hover">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $log->assignedGuard?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $log->site?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $log->clock_in_at?->format('M j, H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-badge :status="$log->status ?? 'on_time'" />
                                @if($log->geofence_validated === false)
                                    <span class="ml-1 text-xs text-amber-600">Geofence</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10"><x-empty-state title="No attendance logs" description="Guard clock events appear here." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-data-table title="Recent breaks">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Log ID</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Started</th>
                        <th class="px-4 py-3">Ended</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($breaks as $break)
                        <tr class="table-row-hover">
                            <td class="px-4 py-3 text-slate-600">#{{ $break->attendance_log_id }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $break->type }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $break->started_at }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $break->ended_at ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10"><x-empty-state title="No breaks logged" description="Record breaks above." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </div>
    </div>
</div>
