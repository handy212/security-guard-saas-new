<div>
    <x-page-shell title="Deployment Sheet" description="Daily guard deployment roster.">
        <x-slot:actions>
            <x-button variant="secondary" size="sm" wire:click="previousDay">Previous</x-button>
            <x-button variant="secondary" size="sm" wire:click="goToday" :disabled="$date === today()->toDateString()">Today</x-button>
            <x-button variant="secondary" size="sm" wire:click="nextDay">Next</x-button>
            <x-input wire:model.live="date" type="date" class="w-auto text-sm" />
            <x-button variant="secondary" href="{{ route('schedules.index', ['date' => $date]) }}">Day roster</x-button>
            <x-button variant="secondary" type="button" onclick="window.print()">Print</x-button>
        </x-slot:actions>

        
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>


        <div class="stat-grid">
            <x-stat-card compact label="Assignments" :value="$stats['assignments']" icon="schedules" />
            <x-stat-card compact label="Sites covered" :value="$stats['sites']" icon="sites" tone="info" />
            <x-stat-card compact label="Guards deployed" :value="$stats['guards']" icon="guards" tone="success" />
            <x-stat-card compact label="Date" :value="\Carbon\Carbon::parse($date)->format('M j')" icon="plan" />
        </div>

        <x-data-table title="Roster for {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}">
            <x-table.head>
                <tr>
                    <x-table.th>Shift</x-table.th>
                    <x-table.th>Time</x-table.th>
                    <x-table.th>Site</x-table.th>
                    <x-table.th>Guard</x-table.th>
                    <x-table.th>Status</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($assignments as $assignment)
                    <tr class="table-row-hover" wire:key="deploy-{{ $assignment->id }}">
                        <x-table.td>{{ $assignment->shift?->title ?? '—' }}</x-table.td>
                        <x-table.td mono>{{ $assignment->shift?->starts_at?->format('H:i') }}–{{ $assignment->shift?->ends_at?->format('H:i') }}</x-table.td>
                        <x-table.td>{{ $assignment->shift?->site?->name }}</x-table.td>
                        <x-table.td>{{ $assignment->assignedGuard?->full_name ?? 'Unassigned' }}</x-table.td>
                        <x-table.td><x-badge :status="$assignment->status" /></x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="5"><x-empty-state title="No deployments for this date" description="Assign guards from the day roster for this day." /></x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>
            </x-sub-sidebar-layout>
    </x-page-shell>
</div>
