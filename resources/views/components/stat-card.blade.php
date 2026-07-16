@props(['label', 'value', 'hint' => null, 'tone' => 'default', 'icon' => null, 'compact' => false, 'stacked' => false, 'active' => false, 'href' => null])

@php
    $tones = [
        'default' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400',
        'danger' => 'bg-red-50 text-red-700 dark:bg-red-950/50 dark:text-red-400',
        'info' => 'bg-accent-50 text-accent-700 dark:bg-accent-950/50 dark:text-accent-400',
    ];
    $iconTone = $tones[$tone] ?? $tones['default'];

    $icons = [
        'users' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        'guards' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'check' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'pause' => 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z',
        'plan' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        'billing' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        'chart' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'shifts' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'incidents' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'sites' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
        'schedules' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'patrols' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
        'gps' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
        'dispatch' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    ];
    $path = $icons[$icon] ?? $icons['users'];

    $shellClass = $stacked
        ? 'flex h-full min-w-0 flex-col items-center justify-center px-3 py-3 text-center'
        : ($compact
            ? 'flex min-w-0 w-full items-center gap-3 px-4 py-3 text-left'
            : 'flex min-w-0 items-center gap-3 rounded-lg border border-zinc-200/90 bg-white px-3 py-2.5 dark:border-zinc-800 dark:bg-zinc-900');

    if ($active) {
        $shellClass .= $compact
            ? ' bg-zinc-50 dark:bg-zinc-800/80'
            : ' border-zinc-400 bg-zinc-50 shadow-sm dark:border-zinc-600 dark:bg-zinc-800';
    }

    $iconShellClass = $stacked
        ? 'mb-1.5 flex h-7 w-7 items-center justify-center rounded-md'
        : ($compact ? 'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg' : 'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg');
    $svgClass = $stacked ? 'h-3.5 w-3.5' : ($compact ? 'h-3.5 w-3.5' : 'h-4 w-4');
    $valueClass = $stacked
        ? 'truncate text-base font-semibold leading-tight text-zinc-900 dark:text-zinc-100'
        : ($compact ? 'text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50' : 'truncate text-lg font-semibold leading-tight text-zinc-900 dark:text-zinc-100');
    $labelClass = $stacked
        ? 'mt-0.5 truncate text-[10px] font-medium text-zinc-500 dark:text-zinc-400'
        : ($compact ? 'mt-0.5 text-xs font-medium text-zinc-500 dark:text-zinc-400' : 'truncate text-[11px] font-medium text-zinc-500 dark:text-zinc-400');

    $isInteractive = $attributes->whereStartsWith('wire:click')->isNotEmpty();
    if ($isInteractive) {
        $shellClass .= ' transition hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-accent-600/30 dark:hover:bg-zinc-800/60';
    }
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $shellClass.' transition hover:bg-zinc-50 dark:hover:bg-zinc-800/60']) }}>
@elseif ($isInteractive)
    <button type="button" {{ $attributes->merge(['class' => $shellClass]) }}>
@else
    <div {{ $attributes->merge(['class' => $shellClass]) }}>
@endif
    <div class="{{ $iconShellClass }} {{ $iconTone }}">
        <svg class="{{ $svgClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
        </svg>
    </div>
    <div class="min-w-0 flex-1">
        <div class="{{ $valueClass }}">{{ $value }}</div>
        <div class="{{ $labelClass }}">{{ $label }}</div>
        @if ($hint)
            <div class="truncate text-[10px] text-zinc-400 dark:text-zinc-500">{{ $hint }}</div>
        @endif
    </div>
@if ($href)
    </a>
@elseif ($isInteractive)
    </button>
@else
    </div>
@endif
