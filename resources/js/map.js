import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix default marker icons when bundled with Vite.
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const maps = new Map();

function parseOptions(el) {
    try {
        return JSON.parse(el.dataset.mapOptions || '{}');
    } catch {
        return {};
    }
}

export function initGuardOpsMap(el) {
    if (!el || typeof el.id !== 'string' || !el.id) {
        return;
    }

    const options = parseOptions(el);
    const lat = Number(options.lat) || 0;
    const lng = Number(options.lng) || 0;
    const zoom = Number(options.zoom) || 13;
    const markers = Array.isArray(options.markers) ? options.markers : [];
    const polyline = Array.isArray(options.polyline) ? options.polyline : [];

    if (maps.has(el.id)) {
        const existing = maps.get(el.id);
        existing.remove();
        maps.delete(el.id);
        delete el.dataset.initialized;
    }

    if (el.dataset.initialized) {
        return;
    }

    el.dataset.initialized = '1';

    const map = L.map(el).setView([lat, lng], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    markers.forEach((marker) => {
        if (marker?.lat == null || marker?.lng == null) {
            return;
        }
        L.marker([marker.lat, marker.lng]).addTo(map).bindPopup(marker.label || '');
    });

    if (polyline.length > 1) {
        const points = polyline
            .filter((point) => point?.lat != null && point?.lng != null)
            .map((point) => [point.lat, point.lng]);

        if (points.length > 1) {
            L.polyline(points, { color: '#0284c7', weight: 4 }).addTo(map);
            map.fitBounds(points, { padding: [24, 24] });
        }
    }

    maps.set(el.id, map);

    requestAnimationFrame(() => map.invalidateSize());
}

function initAllMaps() {
    document.querySelectorAll('[data-guardops-map]').forEach(initGuardOpsMap);
}

document.addEventListener('DOMContentLoaded', initAllMaps);
document.addEventListener('livewire:navigated', initAllMaps);

// Livewire partial updates (filters) on pages with wire:ignore maps.
document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', () => {
        requestAnimationFrame(initAllMaps);
    });
});

window.initGuardOpsMap = initGuardOpsMap;
