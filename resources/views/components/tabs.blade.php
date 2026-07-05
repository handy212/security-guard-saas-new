@props(['tabs' => [], 'active' => '', 'action' => 'setTab'])

<div class="flex gap-1 overflow-x-auto rounded-xl border border-zinc-200 bg-zinc-50 p-1">
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
                'bg-white text-zinc-900 shadow-sm' => $isActive,
                'text-zinc-500 hover:bg-white/60 hover:text-zinc-800' => ! $isActive,
            ])
        >
            <span>{{ $label }}</span>
            @if ($badge)
                <span @class([
                    'inline-flex min-w-[1.25rem] items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none',
                    'bg-amber-100 text-amber-800' => ! $isActive,
                    'bg-accent-100 text-accent-800' => $isActive,
                ])>{{ $badge }}</span>
            @endif
        </button>
    @endforeach
</div>
