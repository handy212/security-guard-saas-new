<div class="relative" x-data="{ open: @entangle('open') }" @click.outside="open = false">
    <button
        type="button"
        @click="open = !open"
        class="relative flex h-9 w-9 items-center justify-center rounded-lg text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
        aria-label="Notifications"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
    >
        <div class="flex items-center justify-between border-b border-zinc-100 px-3 py-2 dark:border-zinc-800">
            <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Notifications</span>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllRead" class="text-xs font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">Mark all read</button>
            @endif
        </div>
        <ul class="max-h-80 overflow-y-auto">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = $notification->read_at === null;
                @endphp
                <li wire:key="notif-{{ $notification->id }}" class="border-b border-zinc-50 last:border-0 dark:border-zinc-800">
                    <a
                        href="{{ $data['action_url'] ?? '#' }}"
                        wire:click="markRead('{{ $notification->id }}')"
                        class="block px-3 py-2.5 transition hover:bg-zinc-50 dark:hover:bg-zinc-800 {{ $isUnread ? 'bg-accent-50/50 dark:bg-accent-600/10' : '' }}"
                    >
                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $data['title'] ?? 'Notification' }}</p>
                        @if (! empty($data['body']))
                            <p class="mt-0.5 line-clamp-2 text-xs text-zinc-600 dark:text-zinc-400">{{ $data['body'] }}</p>
                        @endif
                        <p class="mt-1 text-[10px] text-zinc-400">{{ $notification->created_at?->diffForHumans() }}</p>
                    </a>
                </li>
            @empty
                <li class="px-3 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No notifications yet</li>
            @endforelse
        </ul>
    </div>
</div>
