<div>
    <x-page-header title="Guards & Officers" description="Manage guard profiles, licenses, and employment details." />

    <div class="space-y-5 p-6">
        <x-form-card title="Add or edit guard" description="Register security officers for scheduling and field operations." collapsible>
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-input wire:model="form.employee_number" label="Employee #" placeholder="G-001" />
                <x-input wire:model="form.first_name" label="First name" />
                <x-input wire:model="form.last_name" label="Last name" />
                <x-input wire:model="form.phone" label="Phone" />
                <x-input wire:model="form.email" label="Email" type="email" />
                <x-input wire:model="form.license_number" label="License #" />
                <x-input wire:model="form.hourly_rate" label="Hourly rate" type="number" step="0.01" />
                <div class="flex items-end md:col-span-2 xl:col-span-4">
                    <x-button type="submit">Save guard</x-button>
                </div>
            </form>
        </x-form-card>

        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search guards by name, email, or employee #…" />

        <x-data-table title="Guard roster">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Guard</th>
                    <th class="px-4 py-3">Employee #</th>
                    <th class="px-4 py-3">Contact</th>
                    <th class="px-4 py-3">License</th>
                    <th class="px-4 py-3">Rate</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($guards as $guard)
                    <tr class="table-row-hover">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">
                                    {{ strtoupper(substr($guard->first_name, 0, 1).substr($guard->last_name, 0, 1)) }}
                                </div>
                                <span class="font-medium">{{ $guard->first_name }} {{ $guard->last_name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-sm text-slate-600">{{ $guard->employee_number }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            <div>{{ $guard->phone ?: '—' }}</div>
                            <div class="text-slate-400">{{ $guard->email ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $guard->license_number ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $guard->hourly_rate ? number_format($guard->hourly_rate, 2) : '—' }}</td>
                        <td class="px-4 py-3"><x-badge :status="$guard->status" /></td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="edit({{ $guard->id }})" class="btn-link">Edit</button>
                            <button wire:click="delete({{ $guard->id }})" wire:confirm="Remove this guard?" class="ml-3 text-sm font-medium text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10"><x-empty-state title="No guards registered" description="Add guards to assign shifts and enable the field app." action="/guards" actionLabel="Add guard" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        {{ $guards->links('components.pagination') }}
    </div>
</div>
