<div>
    <x-page-shell title="Visitor Log" description="Check visitors in and out at client sites.">
        <x-slot:actions>
            <x-button wire:click="openCheckIn">Check in visitor</x-button>
        </x-slot:actions>

        <x-stat-grid>
            <x-stat-card compact label="Total visits" :value="$stats['total']" icon="users" />
            <x-stat-card compact label="On site now" :value="$stats['on_site']" icon="guards" :tone="$stats['on_site'] ? 'warning' : 'success'" />
            <x-stat-card compact label="Today" :value="$stats['today']" icon="schedules" tone="info" />
            <x-stat-card compact label="Sites" :value="$stats['sites']" icon="sites" />
        </x-stat-grid>

        <x-page-toolbar search="search" searchPlaceholder="Search visitors…">
            <x-slot:tabs>
                <x-segment-control model="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'checked_in' => 'On site', 'checked_out' => 'Checked out']" />
            </x-slot:tabs>
        </x-page-toolbar>

        <x-data-table>
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Visitor</th>
                    <th class="px-3 py-2">Site</th>
                    <th class="hidden px-3 py-2 md:table-cell">Checked in</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="table-row-hover" wire:key="visitor-{{ $item->id }}">
                        <td class="px-3 py-2">
                            <div class="font-medium">{{ $item->visitor_name }}</div>
                            <div class="text-xs text-zinc-500">{{ $item->company ?: $item->purpose ?: '—' }}</div>
                        </td>
                        <td class="px-3 py-2 text-zinc-600">{{ $item->site?->name ?? '—' }}</td>
                        <td class="hidden px-3 py-2 text-zinc-600 md:table-cell">{{ $item->checked_in_at?->format('M j, H:i') ?? '—' }}</td>
                        <td class="px-3 py-2"><x-badge :status="$item->status" /></td>
                        <td class="px-3 py-2 text-right">
                            @if($item->status === 'checked_in')
                                <x-button size="sm" wire:click="checkOut({{ $item->id }})">Check out</x-button>
                            @else
                                <span class="text-xs text-zinc-500">{{ $item->checked_out_at?->format('H:i') ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-8"><x-empty-state title="No visitors logged" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$items" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="Check in visitor" width="lg">
            <form wire:submit="checkIn" class="grid gap-3 sm:grid-cols-2">
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
                <div class="flex gap-2 sm:col-span-2">
                    <x-button type="submit">Check in</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeDrawer">Cancel</x-button>
                </div>
            </form>
        </x-drawer>
    @endif
</div>
