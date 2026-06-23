<div>
    <x-page-header title="Visitor Log" description="Check visitors in and out at client sites." />

    <div class="space-y-5 p-6">
        <x-form-card title="Check in visitor" description="Record visitor details at the gate or reception." collapsible open>
            <form wire:submit="checkIn" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <x-select wire:model="form.site_id" label="Site" required>
                    <option value="">Select site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.visitor_name" label="Visitor name" placeholder="Jane Doe" required />
                <x-input wire:model="form.visitor_phone" label="Phone" placeholder="+234…" />
                <x-input wire:model="form.company" label="Company" placeholder="Acme Ltd" />
                <x-input wire:model="form.purpose" label="Purpose" placeholder="Meeting, delivery…" />
                <x-input wire:model="form.vehicle_plate" label="Vehicle plate" placeholder="ABC-123" />
                <div class="flex items-end md:col-span-2 xl:col-span-3">
                    <x-button type="submit">Check in visitor</x-button>
                </div>
            </form>
        </x-form-card>

        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search by name, company, or plate…" />

        <x-data-table title="Today's visitors">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Visitor</th>
                    <th class="px-4 py-3">Site</th>
                    <th class="px-4 py-3">Checked in</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="table-row-hover">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $item->visitor_name }}</div>
                            <div class="text-xs text-slate-500">{{ $item->company ?: $item->purpose ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->site?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->checked_in_at?->format('M j, H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-badge :status="$item->checked_out_at ? 'closed' : 'in_progress'" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if(!$item->checked_out_at)
                                <x-button size="sm" wire:click="checkOut({{ $item->id }})">Check out</x-button>
                            @else
                                <span class="text-xs text-slate-500">Out {{ $item->checked_out_at->format('H:i') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10"><x-empty-state title="No visitors logged" description="Check in the first visitor to start the log." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>
