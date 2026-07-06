@props(['title' => 'Top incidents', 'breakdown' => collect()])

@php
    $total = $breakdown->sum();
    $colors = ['#0ea5e9', '#f59e0b', '#8b5cf6', '#06b6d4', '#f43f5e'];
@endphp

<section class="card-surface overflow-hidden p-5">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h2>
    </div>

    @if ($total > 0)
        <div class="flex flex-col items-center gap-5 sm:flex-row">
            <div class="relative h-44 w-44 shrink-0">
                <canvas
                    data-dashboard-chart="donut"
                    data-chart-payload="{{ $breakdown->toJson() }}"
                    class="h-full w-full"
                ></canvas>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $total }}</span>
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
</section>
