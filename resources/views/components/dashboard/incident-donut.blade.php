@props(['title' => 'Top incidents', 'breakdown' => collect()])

@php
    $total = $breakdown->sum();
    // Must match resources/js/dashboard-charts.js chartPalette()
    $colors = ['#0f766e', '#d97706', '#0891b2', '#e11d48', '#65a30d'];
@endphp

<section class="card-surface overflow-hidden">
    <div class="card-header">
        <div>
            <h2 class="card-header-title">{{ $title }}</h2>
            <p class="card-header-meta">Last 7 days</p>
        </div>
    </div>
    <div class="p-5">

    @if ($total > 0)
        <div class="flex flex-col items-center gap-5 sm:flex-row">
            <div class="relative h-44 w-44 shrink-0">
                <canvas
                    data-dashboard-chart="donut"
                    data-chart-payload="{{ $breakdown->toJson() }}"
                    class="h-full w-full"
                ></canvas>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $total }}</span>
                    <span class="text-[11px] font-medium text-zinc-400">7 days</span>
                </div>
            </div>
            <div class="min-w-0 flex-1 space-y-0">
                @foreach ($breakdown as $label => $count)
                    <div class="flex items-center justify-between border-b border-zinc-100 py-2 text-sm last:border-0 dark:border-zinc-800">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $colors[$loop->index % count($colors)] }}"></span>
                            <span class="truncate text-zinc-600 dark:text-zinc-300">{{ $label }}</span>
                        </div>
                        <span class="shrink-0 font-semibold text-zinc-900 dark:text-zinc-100">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <x-empty-state title="No incidents" description="Incident categories will appear here once logged." />
    @endif
    </div>
</section>
