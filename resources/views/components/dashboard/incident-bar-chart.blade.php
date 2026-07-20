@props(['title' => 'Incident overview', 'series' => collect()])

<section class="card-surface overflow-hidden">
    <div class="card-header">
        <div>
            <h2 class="card-header-title">{{ $title }}</h2>
            <p class="card-header-meta">Trend across the last 7 days</p>
        </div>
    </div>

    <div class="p-5">
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
    </div>
</section>
