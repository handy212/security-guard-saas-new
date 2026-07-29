@props([
    'value' => 0,
    'label' => null,
    'delta' => null,
    'hint' => null,
])

@php
    $deltaNum = is_numeric($delta) ? (float) $delta : null;
    $deltaPositive = $deltaNum !== null && $deltaNum > 0;
    $deltaNegative = $deltaNum !== null && $deltaNum < 0;
@endphp

<div {{ $attributes->class(['flex flex-wrap items-end justify-between gap-3']) }}>
    <div class="min-w-0">
        @if ($label)
            <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ $label }}</p>
        @endif
        <p class="mt-0.5 text-2xl font-semibold tabular-nums tracking-tight text-zinc-900 dark:text-zinc-100">{{ $value }}</p>
        @if ($hint)
            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
        @endif
    </div>
    @if ($deltaNum !== null)
        <span @class([
            'inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-semibold tabular-nums',
            'border-emerald-200/90 bg-emerald-50 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-200' => $deltaPositive,
            'border-red-200/90 bg-red-50 text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200' => $deltaNegative,
            'border-zinc-200/90 bg-zinc-50 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300' => ! $deltaPositive && ! $deltaNegative,
        ])>
            {{ $deltaPositive ? '+' : '' }}{{ rtrim(rtrim(number_format($deltaNum, 1, '.', ''), '0'), '.') }}%
        </span>
    @endif
</div>
