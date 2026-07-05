<div>
    <x-page-shell title="Visitor Log" description="Check visitors in and out at client sites.">
        <x-slot:actions>
            <x-button wire:click="openCheckIn">Check in visitor</x-button>
        </x-slot:actions>

        <div class="stat-grid">
            <x-stat-card compact label="Total visits" :value="$stats['total']" icon="users" />
            <x-stat-card compact label="On site now" :value="$stats['on_site']" icon="guards" :tone="$stats['on_site'] ? 'warning' : 'success'" />
            <x-stat-card compact label="Today" :value="$stats['today']" icon="schedules" tone="info" />
            <x-stat-card compact label="Sites" :value="$stats['sites']" icon="sites" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search visitors…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'checked_in' => 'On site', 'checked_out' => 'Checked out']" />
            </x-slot:tabs>
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Visitor</x-table.th>
                    <x-table.th>Site</x-table.th>
                    <x-table.th responsive="md">Checked in</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right">Actions</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($items as $item)
                    <tr class="table-row-hover" wire:key="visitor-{{ $item->id }}">
                        <x-table.td>
                            <div class="font-medium">{{ $item->visitor_name }}</div>
                            <div class="text-xs text-zinc-500">{{ $item->company ?: $item->purpose ?: '—' }}</div>
                        </x-table.td>
                        <x-table.td muted>{{ $item->site?->name ?? '—' }}</x-table.td>
                        <x-table.td responsive="md" muted>{{ $item->checked_in_at?->format('M j, H:i') ?? '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$item->status" /></x-table.td>
                        <x-table.td align="right">
                            @if($item->status === 'checked_in')
                                <x-button size="sm" wire:click="checkOut({{ $item->id }})">Check out</x-button>
                            @else
                                <span class="text-xs text-zinc-500">{{ $item->checked_out_at?->format('H:i') ?? '—' }}</span>
                            @endif
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="5">
                        <x-empty-state compact title="No visitors logged" />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$items" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="Check in visitor" width="lg">
            <x-drawer-form wire:submit="checkIn" submit-label="Check in" target="checkIn">
                <x-select wire:model="form.site_id" label="Site" class="sm:col-span-2">
                    <option value="">Select site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.visitor_name" label="Visitor name" class="sm:col-span-2" />
                <x-input wire:model="form.visitor_phone" label="Phone" />
                <x-input wire:model="form.company" label="Company" />
                <x-input wire:model="form.purpose" label="Purpose" class="sm:col-span-2" />
                <x-input wire:model="form.vehicle_plate" label="Vehicle plate" class="sm:col-span-2" />
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
