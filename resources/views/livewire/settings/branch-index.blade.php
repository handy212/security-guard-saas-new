<div>
    <x-page-shell title="Branches" description="Manage regional or office branches used when assigning guards.">
        <x-slot:actions>
            <x-button wire:click="openCreate">Add branch</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <x-flash-status />

            <x-page-toolbar search="search" searchPlaceholder="Search branches…" />

            <x-data-table>
                <x-table.head>
                    <tr>
                        <x-table.th>Branch</x-table.th>
                        <x-table.th responsive="md">Code</x-table.th>
                        <x-table.th responsive="lg">Location</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th align="right" class="w-12"></x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($branches as $branch)
                        <tr class="table-row-hover" wire:key="branch-{{ $branch->id }}">
                            <x-table.td>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $branch->name }}</div>
                                @if ($branch->phone || $branch->email)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ collect([$branch->phone, $branch->email])->filter()->implode(' · ') }}</div>
                                @endif
                            </x-table.td>
                            <x-table.td responsive="md" mono>{{ $branch->code ?: '—' }}</x-table.td>
                            <x-table.td responsive="lg" muted>{{ collect([$branch->city, $branch->country])->filter()->implode(', ') ?: '—' }}</x-table.td>
                            <x-table.td><x-badge :status="$branch->is_active ? 'active' : 'inactive'" /></x-table.td>
                            <x-table.td align="right">
                                <x-row-menu>
                                    <x-row-menu-item wire:click="edit({{ $branch->id }})">Edit</x-row-menu-item>
                                    <x-row-menu-item wire:click="delete({{ $branch->id }})" wire:confirm="Delete this branch?" danger>Delete</x-row-menu-item>
                                </x-row-menu>
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="5">
                            <x-empty-state compact title="No branches" description="Add a branch so you can assign guards to it.">
                                <x-slot:actions>
                                    <x-button size="sm" wire:click="openCreate">Add branch</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-pagination :paginator="$branches" />
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingId ? 'Edit branch' : 'Add branch'"
            :description="$editingId ? 'Update branch details used on guard assignments.' : 'Add a regional or office branch for rostering.'"
            width="md"
        >
            <x-drawer-form wire:submit="save" :submit-label="$editingId ? 'Update branch' : 'Create branch'">
                <x-form-section title="Branch">
                    <x-input wire:model="form.name" label="Name" class="sm:col-span-2" />
                    <x-input wire:model="form.code" label="Code" />
                    <x-input wire:model="form.phone" label="Phone" />
                    <x-input wire:model="form.email" label="Email" type="email" class="sm:col-span-2" />
                </x-form-section>
                <x-form-section title="Location">
                    <x-input wire:model="form.address" label="Address" class="sm:col-span-2" />
                    <x-input wire:model="form.city" label="City" />
                    <x-input wire:model="form.country" label="Country" />
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 sm:col-span-2">
                        <input type="checkbox" wire:model="form.is_active" class="rounded border-zinc-300 text-accent-600 focus:ring-accent-600/20">
                        Active
                    </label>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
