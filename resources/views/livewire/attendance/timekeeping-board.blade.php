<div>
    @php
        $exceptions = $logs->filter(fn ($log) => in_array($log->status, ['late', 'no_show', 'early_leave'], true))->count();
    @endphp

    <x-page-shell title="Attendance" description="Clock events, geofence validation, and breaks.">
        <div class="grid grid-cols-4 gap-2">
            <x-stat-card compact label="Attendance logs" :value="$logs->count()" icon="schedules" />
            <x-stat-card compact label="Break logs" :value="$breaks->count()" icon="plan" tone="info" />
            <x-stat-card compact label="Exceptions" :value="$exceptions" icon="incidents" :tone="$exceptions ? 'warning' : 'success'" />
            <x-stat-card compact label="On duty" :value="$logs->where('clock_out_at', null)->count()" icon="guards" tone="success" />
        </div>

        <x-form-card title="Log break">
            <form wire:submit="saveBreak" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <x-input wire:model="breakForm.attendance_log_id" label="Attendance log ID" required />
                <x-select wire:model="breakForm.type" label="Break type">
                    <option value="meal">Meal</option>
                    <option value="rest">Rest</option>
                </x-select>
                <x-input wire:model="breakForm.started_at" label="Started at" type="datetime-local" required />
                <x-input wire:model="breakForm.ended_at" label="Ended at" type="datetime-local" />
                <div class="xl:col-span-4"><x-button type="submit">Save break</x-button></div>
            </form>
        </x-form-card>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-data-table title="Recent attendance">
                <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                    <tr>
                        <th class="px-3 py-2">Guard</th>
                        <th class="px-3 py-2">Site</th>
                        <th class="px-3 py-2">Clock in</th>
                        <th class="px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="table-row-hover">
                            <td class="px-3 py-2 font-medium">{{ $log->assignedGuard?->full_name ?? '—' }}</td>
                            <td class="px-3 py-2 text-zinc-600">{{ $log->site?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-zinc-600">{{ $log->clock_in_at?->format('M j, H:i') ?? '—' }}</td>
                            <td class="px-3 py-2"><x-badge :status="$log->status ?? 'on_time'" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-8"><x-empty-state title="No attendance logs" /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-data-table title="Recent breaks">
                <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                    <tr>
                        <th class="px-3 py-2">Log ID</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Started</th>
                        <th class="px-3 py-2">Ended</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($breaks as $break)
                        <tr class="table-row-hover">
                            <td class="px-3 py-2">#{{ $break->attendance_log_id }}</td>
                            <td class="px-3 py-2">{{ $break->type }}</td>
                            <td class="px-3 py-2">{{ $break->started_at }}</td>
                            <td class="px-3 py-2">{{ $break->ended_at ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-8"><x-empty-state title="No breaks" /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </div>
    </x-page-shell>
</div>
