<div>
    <x-page-header title="Roles & Permissions" description="Define roles and assign granular access controls." />

    <div class="space-y-5 p-6">
        <x-form-card title="Create role" description="Add a new role, then assign permissions below.">
            <form wire:submit="createRole" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <x-input wire:model="roleName" label="Role name" placeholder="supervisor" class="flex-1" />
                <x-button type="submit">Create role</x-button>
            </form>
        </x-form-card>

        <div class="space-y-4">
            @foreach($roles as $role)
                <x-section-card :title="$role->name">
                    <div class="mb-4 flex justify-end">
                        <x-button size="sm" wire:click="sync({{ $role->id }})">Save permissions</x-button>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($allPermissions as $permission)
                            <label class="flex items-start gap-2 rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                <input type="checkbox" wire:model="permissions.{{ $role->id }}" value="{{ $permission->name }}" class="mt-0.5 rounded border-slate-300 text-brand-600">
                                <span>{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </x-section-card>
            @endforeach
        </div>
    </div>
</div>
