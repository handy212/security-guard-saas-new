@props([
    'id' => 'map',
    'height' => '320px',
    'lat' => 0,
    'lng' => 0,
    'zoom' => 13,
    'markers' => [],
    'polyline' => [],
    'circles' => [],
    'fitBounds' => true,
])

@php
    $mapOptions = [
        'lat' => (float) $lat,
        'lng' => (float) $lng,
        'zoom' => (int) $zoom,
        'markers' => $markers,
        'polyline' => $polyline,
        'circles' => $circles,
        'fitBounds' => (bool) $fitBounds,
    ];
@endphp

<div
    {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900']) }}
    data-map-shell
    data-map-target="{{ $id }}"
>
    <script type="application/json" data-map-payload="{{ $id }}">@json($mapOptions)</script>
    <div
        wire:ignore
        id="{{ $id }}"
        data-guard-core-pro-map
        style="height: {{ $height }}; width: 100%;"
    ></div>
</div>
