<div class="relative" wire:poll.30s x-data="{ open: @entangle('open') }" @click.outside="open = false">
    @if ($count > 0)
        <button
            type="button"
            @click="open = !open"
            @class([
                'relative flex h-9 w-9 items-center justify-center rounded-lg transition',
                'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40' => $hasDanger,
                'text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40' => ! $hasDanger,
            ])
            aria-label="{{ $count }} item{{ $count === 1 ? '' : 's' }} needing attention"
            title="{{ $count }} needing attention"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <span @class([
                'absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full px-1 text-[10px] font-bold text-white',
                'bg-red-600' => $hasDanger,
                'bg-amber-500' => ! $hasDanger,
            ])>
                {{ $count > 9 ? '9+' : $count }}
            </span>
        </button>

        <div
            x-show="open"
            x-cloak
            x-transition
            class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
        >
            <div class="border-b border-zinc-100 px-3 py-2 dark:border-zinc-800">
                <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Needs attention</span>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $count }} item{{ $count === 1 ? '' : 's' }} requiring action</p>
            </div>
            <ul class="max-h-80 overflow-y-auto">
                @foreach ($items as $item)
                    <li wire:key="attention-{{ $item['key'] }}" class="border-b border-zinc-50 last:border-0 dark:border-zinc-800">
                        <a
                            href="{{ $item['href'] }}"
                            @click="open = false"
                            class="flex items-start gap-3 px-3 py-2.5 transition hover:bg-zinc-50 dark:hover:bg-zinc-800"
                        >
                            <span @class([
                                'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                'bg-red-500' => ($item['tone'] ?? '') === 'danger',
                                'bg-amber-500' => ($item['tone'] ?? '') === 'warning',
                                'bg-accent-500' => ! in_array($item['tone'] ?? '', ['danger', 'warning'], true),
                            ])></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item['label'] }}</p>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $item['detail'] }}</p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
