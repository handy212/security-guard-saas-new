<div>
    <x-page-shell title="Roles & Permissions" description="Define roles and assign granular access controls.">
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <div class="stat-grid">
                <x-stat-card compact label="Roles" :value="$roles->count()" icon="users" />
                <x-stat-card compact label="Permissions" :value="$allPermissions->count()" icon="plan" tone="info" />
                <x-stat-card compact label="Assignable" :value="$allPermissions->count()" icon="check" tone="success" />
                <x-stat-card compact label="Tenant" :value="auth()->user()->tenant?->name ?? '—'" icon="billing" />
            </div>

            <x-form-card title="Create role" description="Add a new role, then assign permissions below.">
                <form wire:submit="createRole" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <x-input wire:model="roleName" label="Role name" placeholder="supervisor" class="flex-1" />
                    <x-button type="submit">Create role</x-button>
                </form>
            </x-form-card>

            <div class="space-y-3">
                @forelse($roles as $role)
                    <x-section-card :title="str($role->name)->headline()" :description="$role->name">
                        <x-slot:actions>
                            <x-button size="sm" wire:click="sync({{ $role->id }})">Save permissions</x-button>
                        </x-slot:actions>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            @foreach($allPermissions as $permission)
                                <label class="permission-check">
                                    <input type="checkbox" wire:model="permissions.{{ $role->id }}" value="{{ $permission->name }}" class="mt-0.5 rounded border-zinc-300 text-accent-600 focus:ring-accent-600/20">
                                    <span class="leading-snug">{{ str_replace('.', ' › ', $permission->name) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </x-section-card>
                @empty
                    <x-empty-state title="No roles yet" description="Create a role to start assigning permissions." />
                @endforelse
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
