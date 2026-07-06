@props(['id' => 'map', 'height' => '320px', 'lat' => 0, 'lng' => 0, 'zoom' => 13, 'markers' => [], 'polyline' => []])

@php
    $mapOptions = json_encode([
        'lat' => $lat,
        'lng' => $lng,
        'zoom' => $zoom,
        'markers' => $markers,
        'polyline' => $polyline,
    ]);
@endphp

<div wire:ignore {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-zinc-200 bg-white']) }}>
    <div
        id="{{ $id }}"
        data-guard-core-pro-map
        data-map-options="{{ $mapOptions }}"
        style="height: {{ $height }}; width: 100%;"
    ></div>
</div>
