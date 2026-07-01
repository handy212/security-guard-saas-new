<div>
    <x-page-shell title="Deployment Sheet" description="Daily guard deployment roster.">
        <x-slot:actions>
            <input type="date" wire:model.live="date" class="form-input text-sm">
            <button type="button" onclick="window.print()" class="btn-secondary text-sm">Print</button>
        </x-slot:actions>

        <x-stat-grid>
            <x-stat-card compact label="Assignments" :value="$stats['assignments']" icon="schedules" />
            <x-stat-card compact label="Sites covered" :value="$stats['sites']" icon="sites" tone="info" />
            <x-stat-card compact label="Guards deployed" :value="$stats['guards']" icon="guards" tone="success" />
            <x-stat-card compact label="Date" :value="\Carbon\Carbon::parse($date)->format('M j')" icon="plan" />
        </x-stat-grid>

        <x-data-table title="Roster for {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}">
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Time</th>
                    <th class="px-3 py-2">Site</th>
                    <th class="px-3 py-2">Guard</th>
                    <th class="px-3 py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $assignment)
                    <tr class="table-row-hover">
                        <td class="px-3 py-2">{{ $assignment->shift?->starts_at?->format('H:i') }}–{{ $assignment->shift?->ends_at?->format('H:i') }}</td>
                        <td class="px-3 py-2">{{ $assignment->shift?->site?->name }}</td>
                        <td class="px-3 py-2">{{ $assignment->assignedGuard?->full_name ?? 'Unassigned' }}</td>
                        <td class="px-3 py-2"><x-badge :status="$assignment->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-8"><x-empty-state title="No deployments for this date" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </x-page-shell>
</div>
