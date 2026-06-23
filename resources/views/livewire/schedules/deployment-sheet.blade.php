<div>
    <x-page-header title="Deployment Sheet" description="Daily guard deployment roster.">
        <x-slot:actions>
            <input type="date" wire:model.live="date" class="rounded-lg border px-3 py-2 text-sm">
            <button onclick="window.print()" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">Print</button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 p-6 md:grid-cols-3">
        <x-stat-card label="Assignments" :value="$stats['assignments']" />
        <x-stat-card label="Sites covered" :value="$stats['sites']" tone="info" />
        <x-stat-card label="Guards deployed" :value="$stats['guards']" tone="success" />
    </div>

    <div class="px-6 pb-6">
        <x-data-table title="Roster for {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">Time</th>
                    <th class="px-4 py-3">Site</th>
                    <th class="px-4 py-3">Guard</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($assignments as $assignment)
                    <tr>
                        <td class="px-4 py-3">{{ $assignment->shift?->starts_at?->format('H:i') }}–{{ $assignment->shift?->ends_at?->format('H:i') }}</td>
                        <td class="px-4 py-3">{{ $assignment->shift?->site?->name }}</td>
                        <td class="px-4 py-3">{{ $assignment->assignedGuard?->full_name ?? 'Unassigned' }}</td>
                        <td class="px-4 py-3">{{ $assignment->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No deployments for this date.</td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>
