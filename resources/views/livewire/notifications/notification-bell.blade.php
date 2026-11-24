<div class="relative" data-routine-notice="{{ json_encode($routineNotice) }}" data-critical-count="{{ $criticalCount }}" data-alarm-ids="{{ $alarms->pluck('id')->implode(',') }}" x-data="operationalAlarms(@entangle('open').live, @js($canOperateAlarms ? auth()->user()->tenant_id : null), @js(auth()->id()))" @click.outside="open = false" @keydown.escape="open = false">
    <button
        type="button"
        @click="open = !open"
        class="relative flex h-9 w-9 items-center justify-center rounded-lg text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
        aria-label="Notifications" :aria-expanded="open"
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

    @if ($criticalCount)
        <button type="button" x-ref="criticalIndicator" @click="restorePanel()" class="absolute left-full top-0 rounded-md bg-red-700 px-1.5 text-xs font-bold text-white" aria-label="{{ $criticalCount }} critical alarms">{{ $criticalCount }}</button>
    @endif

    <div
        x-show="open"
        x-cloak
        x-transition
        class="fixed inset-x-3 top-[var(--app-header-height)] z-50 mt-1 sm:absolute sm:inset-x-auto sm:top-full sm:right-0 sm:mt-2 sm:w-80 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
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
                        @if (in_array($data['severity'] ?? 'info', ['warning', 'high'], true))
                            <span class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-300">{{ $data['severity'] }}</span>
                        @endif
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
        <label class="flex items-center gap-2 border-t border-zinc-200 px-3 py-2 text-xs dark:border-zinc-700">
            <input type="checkbox" x-model="routineToasts"> Show warning and high-priority toasts
        </label>
        @if ($canOperateAlarms)
            <div class="flex flex-wrap items-center gap-2 border-t border-zinc-200 px-3 py-2 text-xs dark:border-zinc-700">
                <button type="button" class="underline" x-show="audioState !== 'Alarm audio ready'" @click="enableAudio()">Enable alarm audio</button>
                <button type="button" class="underline" x-show="audioState === 'Alarm audio ready'" @click="previewAudio()">Preview alarm</button>
                <label x-show="audioState === 'Alarm audio ready'">Volume <input type="range" min="5" max="100" x-model.number="volume"></label>
                <span x-text="audioState" role="status"></span>
            </div>
        @endif
        <a href="{{ route('notifications.index') }}" class="block border-t border-zinc-200 px-3 py-2 text-sm font-semibold dark:border-zinc-700">Open Notification Center</a>
    </div>

    <template x-teleport="body">
        <aside x-show="notice && !({{ $criticalCount }} > 0 && !minimized)" x-cloak class="fixed inset-x-3 bottom-3 z-50 ml-auto flex max-w-sm items-center gap-3 rounded-xl border border-amber-300 bg-white p-3 text-sm shadow-lg sm:left-auto dark:border-amber-800 dark:bg-zinc-950">
            <div class="min-w-0 flex-1" role="status">
                <p class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-300" x-text="notice?.severity"></p>
                <a :href="notice?.url" class="font-semibold underline" x-text="notice?.title"></a>
            </div>
            <button type="button" class="text-sm underline" @click="notice = null">Dismiss</button>
        </aside>
    </template>

    @if ($canOperateAlarms)
        <template x-teleport="body">
            <aside class="fixed inset-x-3 bottom-3 z-[70] max-h-[65vh] overflow-y-auto rounded-xl border border-red-300 bg-white shadow-xl sm:left-auto sm:w-96 dark:border-red-800 dark:bg-zinc-950"
                x-show="! minimized && {{ $criticalCount }} > 0" x-cloak aria-labelledby="critical-alarm-title"
                @keydown.escape="minimizePanel()">
                <div class="flex items-center justify-between gap-3 bg-red-800 p-3 text-white">
                    <h2 id="critical-alarm-title" tabindex="-1" x-ref="heading" class="font-semibold" aria-live="assertive">{{ $criticalCount }} critical {{ Str::plural('alarm', $criticalCount) }} awaiting acknowledgment</h2>
                    <button type="button" class="text-sm underline" @click="minimizePanel()">Minimize</button>
                </div>
                @foreach ($alarms as $alarm)
                    <article wire:key="critical-alarm-{{ $alarm->id }}" class="space-y-2 border-b border-zinc-200 p-3 dark:border-zinc-800" >
                        <p class="text-xs font-bold uppercase text-red-700 dark:text-red-300">{{ str($alarm->event_type)->replace('.', ' ') }}</p>
                        <h3 class="font-semibold">{{ $alarm->title }}</h3>
                        <p class="text-sm">{{ $alarm->instructions }}</p>
                        @if ($alarm->context)
                            <dl class="grid grid-cols-[auto_1fr] gap-x-3 text-xs">
                                @foreach ($alarm->context as $label => $value)
                                    <dt class="font-semibold">{{ $label }}</dt><dd>{{ $value }}</dd>
                                @endforeach
                            </dl>
                        @endif
                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Received {{ $alarm->created_at->format('H:i:s T') }} · {{ $alarm->created_at->diffForHumans() }}</p>
                        <p class="text-xs">Acknowledgment due {{ $alarm->acknowledge_by?->format('H:i:s T') }}{{ $alarm->escalated_at ? ' · Escalated' : '' }}{{ $alarm->backup_escalated_at ? ' · Backup notified' : '' }}{{ (($alarm->escalation_failed_at && ! $alarm->backup_escalated_at) || $alarm->backup_escalation_failed_at) ? ' · Escalation needs an eligible recipient' : '' }}</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-button variant="danger" size="sm" wire:click="acknowledge({{ $alarm->id }})" wire:loading.attr="disabled">Acknowledge alarm</x-button>
                            <a href="{{ $alarm->action_url }}" class="text-sm font-semibold underline">Open response record</a>
                        </div>
                    </article>
                @endforeach
                <div class="flex flex-wrap items-center gap-3 p-3 text-sm">
                    <a href="{{ route('notifications.index', ['filter' => 'pending']) }}" class="underline">View alarm queue</a>
                    <button type="button" class="underline" x-show="audioState !== 'Alarm audio ready'" @click="enableAudio()">Enable alarm audio</button>
                    <span x-text="audioState" role="status"></span>
                </div>
            </aside>
        </template>
    @endif
</div>
