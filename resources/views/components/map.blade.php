@props(['id' => 'map', 'height' => '320px', 'lat' => 0, 'lng' => 0, 'zoom' => 13, 'markers' => [], 'polyline' => []])

<div wire:ignore {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border']) }}>
    <div id="{{ $id }}" style="height: {{ $height }}; width: 100%;"></div>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @endpush
@endonce

@push('scripts')
<script>
(function () {
    const initMap = () => {
        const el = document.getElementById(@json($id));
        if (!el || el.dataset.initialized) return;
        el.dataset.initialized = '1';
        const map = L.map(el).setView([@json($lat), @json($lng)], @json($zoom));
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);
        const markers = @json($markers);
        markers.forEach(m => {
            L.marker([m.lat, m.lng]).addTo(map).bindPopup(m.label || '');
        });
        const polyline = @json($polyline);
        if (polyline.length > 1) {
            L.polyline(polyline.map(p => [p.lat, p.lng]), { color: '#2563eb' }).addTo(map);
            map.fitBounds(polyline.map(p => [p.lat, p.lng]));
        }
    };
    document.addEventListener('DOMContentLoaded', initMap);
    document.addEventListener('livewire:navigated', initMap);
})();
</script>
@endpush
