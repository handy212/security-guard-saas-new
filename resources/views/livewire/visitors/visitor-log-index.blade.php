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
                    <x-table.th align="right" class="w-12"></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($items as $item)
                    <tr class="table-row-hover" wire:key="visitor-{{ $item->id }}">
                        <x-table.td>
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-[10px] font-semibold text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700">
                                    {{ strtoupper(substr($item->visitor_name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->visitor_name }}</div>
                                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $item->company ?: $item->purpose ?: '—' }}</div>
                                </div>
                            </div>
                        </x-table.td>
                        <x-table.td muted>{{ $item->site?->name ?? '—' }}</x-table.td>
                        <x-table.td responsive="md" muted class="tabular-nums">{{ $item->checked_in_at?->format('M j, H:i') ?? '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$item->status" /></x-table.td>
                        <x-table.td align="right">
                            @if ($item->status === 'checked_in' || $item->checked_out_at === null)
                                <x-row-menu>
                                    <x-row-menu-item wire:click="edit({{ $item->id }})">Edit</x-row-menu-item>
                                    <x-row-menu-item wire:click="checkOut({{ $item->id }})">Check out</x-row-menu-item>
                                    <x-row-menu-item wire:click="delete({{ $item->id }})" wire:confirm="Delete this visitor log?" danger>Delete</x-row-menu-item>
                                </x-row-menu>
                            @else
                                <span class="text-xs tabular-nums text-zinc-500">Out {{ $item->checked_out_at?->format('H:i') ?? '—' }}</span>
                            @endif
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="5">
                        <x-empty-state compact title="No visitors logged" description="Check in a visitor when they arrive on site.">
                            <x-slot:actions>
                                <x-button size="sm" wire:click="openCheckIn">Check in visitor</x-button>
                            </x-slot:actions>
                        </x-empty-state>
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$items" per-page="perPage" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingId ? 'Edit visitor' : 'Check in visitor'" width="lg">
            <x-drawer-form wire:submit="checkIn" :submit-label="$editingId ? 'Save changes' : 'Check in'" target="checkIn">
                <x-form-section title="Visit">
                    <x-select wire:model="form.site_id" label="Site" class="sm:col-span-2">
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="form.visitor_name" label="Visitor name" class="sm:col-span-2" />
                    <x-input wire:model="form.visitor_phone" label="Phone" />
                    <x-input wire:model="form.company" label="Company" />
                </x-form-section>

                <x-form-section title="Identification">
                    <x-select wire:model="form.id_type" label="ID type">
                        <option value="">Select…</option>
                        <option value="national_id">National ID</option>
                        <option value="passport">Passport</option>
                        <option value="drivers_license">Driver's license</option>
                        <option value="company_id">Company ID</option>
                        <option value="other">Other</option>
                    </x-select>
                    <x-input wire:model="form.id_number" label="ID number" />
                    <x-select wire:model="form.guard_id" label="Host guard" class="sm:col-span-2">
                        <option value="">None</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="form.purpose" label="Purpose" class="sm:col-span-2" />
                    <x-input wire:model="form.vehicle_plate" label="Vehicle plate" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
