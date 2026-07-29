@props([
    'value' => 0,
    'label' => null,
    'showValue' => true,
    'size' => 'md',
    'tone' => 'accent',
])

@php
    $pct = max(0, min(100, (int) round((float) $value)));
    $barH = match ($size) {
        'sm' => 'h-1',
        'lg' => 'h-2.5',
        default => 'h-1.5',
    };
    $fillTone = match ($tone) {
        'success' => 'bg-emerald-600 dark:bg-emerald-500',
        'warning' => 'bg-amber-500 dark:bg-amber-400',
        'danger' => 'bg-red-600 dark:bg-red-500',
        'neutral' => 'bg-zinc-900 dark:bg-zinc-100',
        default => 'bg-accent-600 dark:bg-accent-500',
    };
@endphp

<div {{ $attributes->class(['progress']) }}>
    @if ($label || $showValue)
        <div class="mb-1.5 flex items-center justify-between gap-3">
            @if ($label)
                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $label }}</span>
            @else
                <span></span>
            @endif
            @if ($showValue)
                <span class="text-xs font-semibold tabular-nums text-zinc-700 dark:text-zinc-200">{{ $pct }}%</span>
            @endif
        </div>
    @endif
    <div class="progress-track {{ $barH }}" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-fill {{ $fillTone }}" style="width: {{ $pct }}%"></div>
    </div>
</div>
