<div>
    <x-page-shell title="Guards & Officers" description="Roster, profiles, and Know Your Guard verification.">
        <x-slot:actions>
            <a href="{{ route('guards.kyg') }}" class="btn-secondary">KYG queue</a>
            <x-button wire:click="openCreate">Add guard</x-button>
        </x-slot:actions>

        <div class="grid grid-cols-4 gap-2">
            <x-stat-card compact label="Total" :value="$guardStats['total']" icon="guards" wire:click="applyStatFilter('total')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'all' && $verificationFilter === 'all' && $search === ''" />
            <x-stat-card compact label="Active" :value="$guardStats['active']" icon="check" tone="success" wire:click="applyStatFilter('active')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'active'" />
            <x-stat-card compact label="Pending KYG" :value="$guardStats['pending']" icon="incidents" :tone="$guardStats['pending'] > 0 ? 'warning' : 'default'" wire:click="applyStatFilter('pending')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$verificationFilter === 'pending'" />
            <x-stat-card compact label="Inactive" :value="$guardStats['inactive']" icon="pause" wire:click="applyStatFilter('inactive')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'inactive'" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search by name, email, or ID…">
            <x-slot:tabs>
                <x-segment-control model="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']" />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="text-xs font-medium text-zinc-500 hover:text-zinc-800">Clear filters</button>
                @endif
                <select wire:model.live="verificationFilter" class="form-input w-auto min-w-[8.5rem] text-sm">
                    <option value="all">All KYG</option>
                    <option value="verified">Verified</option>
                    <option value="pending">Pending</option>
                </select>
            </x-slot:controls>
        </x-page-toolbar>

        <x-data-table>
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Guard</th>
                    <th class="hidden px-3 py-2 md:table-cell">ID</th>
                    <th class="hidden px-3 py-2 lg:table-cell">Branch</th>
                    <th class="px-3 py-2">KYG</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right w-12"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($guards as $guard)
                    <tr class="table-row-hover" wire:key="guard-{{ $guard->id }}">
                        <td class="px-3 py-2">
                            <a href="{{ route('guards.show', $guard) }}" class="flex items-center gap-2 hover:underline">
                                @if ($guard->photo_path)
                                    <img src="{{ route('files.guard-photo', $guard) }}" alt="" class="h-8 w-8 rounded-full object-cover">
                                @else
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-100 text-[10px] font-semibold text-zinc-600">
                                        {{ strtoupper(substr($guard->first_name, 0, 1).substr($guard->last_name, 0, 1)) }}
                                    </div>
                                @endif
                                <span class="font-medium text-zinc-900">{{ $guard->full_name }}</span>
                            </a>
                        </td>
                        <td class="hidden px-3 py-2 font-mono text-xs text-zinc-600 md:table-cell">{{ $guard->employee_number ?: '—' }}</td>
                        <td class="hidden px-3 py-2 text-zinc-600 lg:table-cell">{{ $guard->branch?->name ?? '—' }}</td>
                        <td class="px-3 py-2"><x-badge :status="$guard->verification_status" /></td>
                        <td class="px-3 py-2"><x-badge :status="$guard->status" /></td>
                        <td class="px-3 py-2 text-right">
                            <div x-data="{ open: false }" class="relative inline-block text-left">
                                <button type="button" @click="open = !open" class="rounded-md p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800" aria-label="Actions">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4z"/></svg>
                                </button>
                                <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-10 mt-1 w-36 origin-top-right rounded-lg border border-zinc-200 bg-white py-1 shadow-lg">
                                    <a href="{{ route('guards.show', $guard) }}" @click="open = false" class="block px-3 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50">View profile</a>
                                    <button type="button" wire:click="edit({{ $guard->id }})" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-50">Edit</button>
                                    <button type="button" wire:click="delete({{ $guard->id }})" wire:confirm="Remove this guard?" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-red-600 hover:bg-red-50">Delete</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-8">
                            <x-empty-state
                                :title="$hasActiveFilters ? 'No matching guards' : 'No guards'"
                                :description="$hasActiveFilters ? 'Try adjusting your filters.' : 'Add guards to enable scheduling and field operations.'"
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$guards" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingId ? 'Edit guard' : 'Add guard'" width="lg">
            <form wire:submit="save" class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-input wire:model="form.employee_number" label="Employee #" placeholder="G-001" />
                    <x-select wire:model="form.status" label="Status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </x-select>
                    <x-input wire:model="form.first_name" label="First name" />
                    <x-input wire:model="form.last_name" label="Last name" />
                    <x-input wire:model="form.phone" label="Phone" />
                    <x-input wire:model="form.email" label="Email" type="email" />
                    <x-input wire:model="form.license_number" label="License #" />
                    <x-input wire:model="form.license_expires_at" label="License expires" type="date" />
                    <x-input wire:model="form.rank" label="Rank / position" />
                    <x-select wire:model="form.branch_id" label="Branch">
                        <option value="">None</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="form.hourly_rate" label="Hourly rate" type="number" step="0.01" class="sm:col-span-2" />
                </div>
                <div class="flex gap-2 pt-2">
                    <x-button type="submit">Save</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeDrawer">Cancel</x-button>
                </div>
            </form>
        </x-drawer>
    @endif
</div>
