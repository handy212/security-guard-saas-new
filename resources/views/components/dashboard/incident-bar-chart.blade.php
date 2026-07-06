@props(['title' => 'Incident overview', 'series' => collect()])

<section class="card-surface overflow-hidden p-5">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h2>
    </div>

    @if ($series->sum() > 0)
        <div class="h-52">
            <canvas
                data-dashboard-chart="bar"
                data-chart-payload="{{ $series->toJson() }}"
                class="h-full w-full"
            ></canvas>
        </div>
    @else
        <x-empty-state title="No data" description="Incident trends will chart here over time." />
    @endif
</section>
