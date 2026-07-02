<div>
    <x-page-shell title="Team passwords" description="Reset passwords when a team member forgets their login.">
        <x-settings-nav />

        <div class="stat-grid">
            <x-stat-card compact label="Team members" :value="$users->count()" icon="users" />
            <x-stat-card compact label="Admins" :value="$users->filter(fn ($u) => $u->hasRole('admin'))->count()" icon="plan" tone="info" />
            <x-stat-card compact label="Selected" :value="$selectedUserId ? '1' : '0'" icon="check" />
            <x-stat-card compact label="Tenant" :value="auth()->user()->tenant?->name ?? '—'" icon="billing" />
        </div>

        <div class="card-surface overflow-hidden">
            <ul class="divide-y divide-zinc-100">
                @forelse ($users as $user)
                    <li class="px-4 py-3" wire:key="team-user-{{ $user->id }}">
                        @if ($selectedUserId === $user->id)
                            <form wire:submit="resetPassword" class="space-y-3">
                                <p class="text-sm font-medium text-zinc-900">Reset password for {{ $user->name }}</p>
                                <p class="text-xs text-zinc-500">{{ $user->email }}</p>
                                <x-input wire:model="newPassword" label="New password" type="password" hint="Min. 12 characters." />
                                <div class="flex gap-2">
                                    <x-button type="submit" size="sm">Save password</x-button>
                                    <x-button type="button" size="sm" variant="secondary" wire:click="cancel">Cancel</x-button>
                                </div>
                            </form>
                        @else
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-zinc-900">{{ $user->name }}</p>
                                    <p class="text-xs text-zinc-500">{{ $user->email }}</p>
                                </div>
                                <x-button type="button" size="sm" variant="secondary" wire:click="selectUser({{ $user->id }})">Reset password</x-button>
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="px-4 py-8"><x-empty-state title="No team members" /></li>
                @endforelse
            </ul>
        </div>
    </x-page-shell>
</div>
