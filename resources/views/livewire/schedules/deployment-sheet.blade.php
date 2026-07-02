<div>
    <x-page-shell title="Deployment Sheet" description="Daily guard deployment roster.">
        <x-slot:actions>
            <x-input wire:model.live="date" type="date" class="w-auto text-sm" />
            <x-button variant="secondary" type="button" onclick="window.print()">Print</x-button>
        </x-slot:actions>

        <div class="stat-grid">
            <x-stat-card compact label="Assignments" :value="$stats['assignments']" icon="schedules" />
            <x-stat-card compact label="Sites covered" :value="$stats['sites']" icon="sites" tone="info" />
            <x-stat-card compact label="Guards deployed" :value="$stats['guards']" icon="guards" tone="success" />
            <x-stat-card compact label="Date" :value="\Carbon\Carbon::parse($date)->format('M j')" icon="plan" />
        </div>

        <x-data-table title="Roster for {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}">
            <x-table.head>
                <tr>
                    <x-table.th>Time</x-table.th>
                    <x-table.th>Site</x-table.th>
                    <x-table.th>Guard</x-table.th>
                    <x-table.th>Status</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($assignments as $assignment)
                    <tr class="table-row-hover" wire:key="deploy-{{ $assignment->id }}">
                        <x-table.td mono>{{ $assignment->shift?->starts_at?->format('H:i') }}–{{ $assignment->shift?->ends_at?->format('H:i') }}</x-table.td>
                        <x-table.td>{{ $assignment->shift?->site?->name }}</x-table.td>
                        <x-table.td>{{ $assignment->assignedGuard?->full_name ?? 'Unassigned' }}</x-table.td>
                        <x-table.td><x-badge :status="$assignment->status" /></x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="4"><x-empty-state title="No deployments for this date" /></x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>
    </x-page-shell>
</div>
