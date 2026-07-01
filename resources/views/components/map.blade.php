@props(['id' => 'map', 'height' => '320px', 'lat' => 0, 'lng' => 0, 'zoom' => 13, 'markers' => [], 'polyline' => []])

<div wire:ignore {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border min-h-[240px] sm:min-h-0']) }}>
    <div id="{{ $id }}" style="height: {{ $height }}; width: 100%;" class="min-h-[240px] sm:min-h-0"></div>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            window.guardOpsMaps = window.guardOpsMaps || {};

            window.guardOpsUpdateMap = function (id, config) {
                if (typeof L === 'undefined') return;
                const el = document.getElementById(id);
                if (!el) return;

                let entry = window.guardOpsMaps[id];
                if (!entry) {
                    const map = L.map(el).setView([config.lat, config.lng], config.zoom ?? 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(map);
                    entry = {
                        map,
                        markerLayer: L.layerGroup().addTo(map),
                        polylineLayer: null,
                    };
                    window.guardOpsMaps[id] = entry;
                    setTimeout(() => map.invalidateSize(), 100);
                }

                entry.map.setView([config.lat, config.lng], config.zoom ?? entry.map.getZoom());

                entry.markerLayer.clearLayers();
                (config.markers || []).forEach((m) => {
                    L.marker([m.lat, m.lng]).addTo(entry.markerLayer).bindPopup(m.label || '');
                });

                if (entry.polylineLayer) {
                    entry.map.removeLayer(entry.polylineLayer);
                    entry.polylineLayer = null;
                }

                const polyline = config.polyline || [];
                if (polyline.length > 1) {
                    entry.polylineLayer = L.polyline(polyline.map((p) => [p.lat, p.lng]), { color: '#2563eb' }).addTo(entry.map);
                    entry.map.fitBounds(polyline.map((p) => [p.lat, p.lng]));
                }

                setTimeout(() => entry.map.invalidateSize(), 50);
            };
        </script>
    @endpush
@endonce

@push('scripts')
<script>
    (function () {
        const config = {
            id: @json($id),
            lat: @json($lat),
            lng: @json($lng),
            zoom: @json($zoom),
            markers: @json($markers),
            polyline: @json($polyline),
        };

        const boot = () => window.guardOpsUpdateMap(config.id, config);

        document.addEventListener('DOMContentLoaded', boot);
        document.addEventListener('livewire:navigated', boot);
    })();
</script>
@endpush
