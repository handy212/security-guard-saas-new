@props(['title' => 'Incident overview', 'series' => collect()])

@php
    $values = collect($series)->values();
    $total = $values->sum();
    $recent = $values->slice(-3)->sum();
    $prior = $values->slice(-6, 3)->sum();
    $delta = $prior > 0
        ? round((($recent - $prior) / $prior) * 100, 1)
        : ($recent > 0 ? 100.0 : 0.0);
    $avg = $values->count() > 0 ? round($total / max($values->count(), 1), 1) : 0;
@endphp

<section class="card-surface overflow-hidden">
    <div class="card-header">
        <div>
            <h2 class="card-header-title">{{ $title }}</h2>
            <p class="card-header-meta">Trend across the last 7 days</p>
        </div>
    </div>

    <div class="space-y-4 p-5">
        <x-dashboard.chart-metric
            :value="$total"
            label="Incidents logged"
            :hint="'Avg '.$avg.'/day'"
            :delta="$total > 0 ? $delta : null"
        />

        @if ($total > 0)
            <div class="h-52">
                <canvas
                    data-dashboard-chart="bar"
                    data-chart-payload="{{ collect($series)->toJson() }}"
                    class="h-full w-full"
                ></canvas>
            </div>
        @else
            <x-empty-state title="No data" description="Incident trends will chart here over time." />
        @endif
    </div>
</section>
