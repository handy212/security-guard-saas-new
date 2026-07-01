@props(['title' => null, 'series' => [], 'color' => 'zinc'])

@php
    $max = max(1, collect($series)->max() ?? 1);
    $colors = [
        'zinc' => 'bg-zinc-800',
        'amber' => 'bg-amber-500',
        'emerald' => 'bg-emerald-500',
    ];
    $barColor = $colors[$color] ?? $colors['zinc'];
@endphp

<div>
    @if ($title)
        <h3 class="mb-3 text-xs font-medium text-zinc-500">{{ $title }}</h3>
    @endif

    @if (collect($series)->sum() > 0)
        <div class="flex h-28 items-end gap-1.5">
            @foreach ($series as $day => $count)
                @php $height = max(8, (int) round(($count / $max) * 100)); @endphp
                <div class="flex min-w-0 flex-1 flex-col items-center gap-1.5">
                    <span class="text-[10px] font-medium text-zinc-500">{{ $count ?: '' }}</span>
                    <div class="flex w-full items-end" style="height: 4.5rem">
                        <div class="{{ $barColor }} w-full rounded-sm" style="height: {{ $height }}%"></div>
                    </div>
                    <span class="text-[10px] text-zinc-400">{{ \Carbon\Carbon::parse($day)->format('D') }}</span>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex h-20 items-center justify-center rounded-lg bg-zinc-50">
            <p class="text-xs text-zinc-400">No data</p>
        </div>
    @endif
</div>
