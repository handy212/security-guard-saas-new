<div>
    <x-page-shell title="Team passwords" description="Reset passwords when a team member forgets their login.">
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <div class="stat-grid">
                <x-stat-card compact label="Team members" :value="$users->count()" icon="users" />
                <x-stat-card compact label="Admins" :value="$users->filter(fn ($u) => $u->hasRole('admin'))->count()" icon="plan" tone="info" />
                <x-stat-card compact label="Selected" :value="$selectedUserId ? '1' : '0'" icon="check" />
                <x-stat-card compact label="Tenant" :value="auth()->user()->tenant?->name ?? '—'" icon="billing" />
            </div>

            <x-section-card title="Team members" description="Select a user to set a new password" flush>
                @forelse ($users as $user)
                    <div class="list-row-start" wire:key="team-user-{{ $user->id }}">
                        @if ($selectedUserId === $user->id)
                            <form wire:submit="resetPassword" class="w-full space-y-3">
                                <div>
                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Reset password for {{ $user->name }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                                </div>
                                <x-input wire:model="newPassword" label="New password" type="password" hint="Min. 12 characters." />
                                <div class="flex gap-2">
                                    <x-button type="submit" size="sm">Save password</x-button>
                                    <x-button type="button" size="sm" variant="secondary" wire:click="cancel">Cancel</x-button>
                                </div>
                            </form>
                        @else
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                            </div>
                            <x-button type="button" size="sm" variant="secondary" wire:click="selectUser({{ $user->id }})">Reset password</x-button>
                        @endif
                    </div>
                @empty
                    <div class="p-3">
                        <x-empty-state title="No team members" />
                    </div>
                @endforelse
            </x-section-card>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
