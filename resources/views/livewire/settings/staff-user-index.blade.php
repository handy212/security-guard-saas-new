<div>
    <x-page-shell title="Team members" description="Invite and manage staff accounts for your security company.">
        <x-slot:actions>
            <x-button wire:click="openCreate">Invite staff</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <x-flash-status />

            <div class="stat-grid">
                <x-stat-card compact label="Staff users" :value="$users->total()" icon="users" />
                <x-stat-card compact label="Active" :value="$users->getCollection()->where('status', 'active')->count()" icon="check" tone="success" />
                <x-stat-card compact label="Inactive" :value="$users->getCollection()->where('status', 'inactive')->count()" icon="plan" />
            </div>

            <x-page-toolbar search="search" searchPlaceholder="Search by name or email…" />

            <x-data-table>
                <x-table.head>
                    <tr>
                        <x-table.th>Name</x-table.th>
                        <x-table.th responsive="md">Email</x-table.th>
                        <x-table.th responsive="lg">Role</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th align="right" class="w-12"></x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse ($users as $user)
                        @php
                            setPermissionsTeamId($user->tenant_id);
                            $roleName = $user->getRoleNames()->reject(fn ($name) => in_array($name, ['super-admin', 'client'], true))->first();
                        @endphp
                        <tr class="table-row-hover" wire:key="staff-user-{{ $user->id }}">
                            <x-table.td>
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent-50 text-[10px] font-semibold text-accent-700 ring-1 ring-accent-100 dark:bg-accent-950 dark:text-accent-300 dark:ring-accent-800/50">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</div>
                                        @if ((int) $user->id === (int) auth()->id())
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">You</div>
                                        @endif
                                    </div>
                                </div>
                            </x-table.td>
                            <x-table.td responsive="md" muted>{{ $user->email }}</x-table.td>
                            <x-table.td responsive="lg" muted>{{ $roleLabels[$roleName] ?? ($roleName ? str($roleName)->headline() : '—') }}</x-table.td>
                            <x-table.td><x-badge :status="$user->status" /></x-table.td>
                            <x-table.td align="right">
                                <x-row-menu>
                                    <x-row-menu-item wire:click="edit({{ $user->id }})">Edit</x-row-menu-item>
                                    @if ($user->status === 'active' && (int) $user->id !== (int) auth()->id())
                                        <x-row-menu-item wire:click="deactivate({{ $user->id }})" wire:confirm="Deactivate this staff user?">Deactivate</x-row-menu-item>
                                    @elseif ($user->status === 'inactive')
                                        <x-row-menu-item wire:click="reactivate({{ $user->id }})">Reactivate</x-row-menu-item>
                                        @if (! $user->guardProfile && (int) $user->id !== (int) auth()->id())
                                            <x-row-menu-item wire:click="delete({{ $user->id }})" wire:confirm="Permanently delete this user?" danger>Delete</x-row-menu-item>
                                        @endif
                                    @endif
                                </x-row-menu>
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="5">
                            <x-empty-state compact title="No staff users" description="Invite team members who need access to your tenant dashboard.">
                                <x-slot:actions>
                                    <x-button size="sm" wire:click="openCreate">Invite staff</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-pagination :paginator="$users" />
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingId ? 'Edit staff user' : 'Invite staff user'"
            :description="$editingId ? 'Update account details and access.' : 'Create a staff login for your operations team.'"
            width="md"
        >
            <x-drawer-form wire:submit="save" :submit-label="$editingId ? 'Update user' : 'Create user'">
                <x-form-section title="Account">
                    <x-input wire:model="form.name" label="Name" class="sm:col-span-2" />
                    <x-input wire:model="form.email" label="Email" type="email" class="sm:col-span-2" />
                    <x-input
                        wire:model="form.password"
                        label="{{ $editingId ? 'New password (optional)' : 'Temporary password' }}"
                        type="password"
                        class="sm:col-span-2"
                        hint="Min. 12 characters."
                    />
                </x-form-section>
                <x-form-section title="Access">
                    <x-select wire:model="form.role" label="Role" class="sm:col-span-2">
                        <option value="">Select role…</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ $roleLabels[$role->name] ?? str($role->name)->headline() }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="form.status" label="Status" class="sm:col-span-2">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </x-select>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
