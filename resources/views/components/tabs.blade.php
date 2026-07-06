@props(['tabs' => [], 'active' => '', 'action' => 'setTab'])

<div class="flex gap-1 overflow-x-auto rounded-xl border border-zinc-200 bg-zinc-50 p-1 dark:border-zinc-700 dark:bg-zinc-800/80">
    @foreach ($tabs as $key => $tab)
        @php
            $label = is_array($tab) ? ($tab['label'] ?? $key) : $tab;
            $badge = is_array($tab) ? ($tab['badge'] ?? null) : null;
            $hint = is_array($tab) ? ($tab['hint'] ?? null) : null;
            $isActive = $active === $key;
        @endphp
        <button
            type="button"
            wire:click="{{ $action }}('{{ $key }}')"
            title="{{ $hint }}"
            @class([
                'group relative flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-100' => $isActive,
                'text-zinc-500 hover:bg-white/60 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-900/60 dark:hover:text-zinc-200' => ! $isActive,
            ])
        >
            <span>{{ $label }}</span>
            @if ($badge)
                <span @class([
                    'inline-flex min-w-[1.25rem] items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none',
                    'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' => ! $isActive,
                    'bg-accent-100 text-accent-800 dark:bg-accent-600/30 dark:text-accent-300' => $isActive,
                ])>{{ $badge }}</span>
            @endif
        </button>
    @endforeach
</div>
