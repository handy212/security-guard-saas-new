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

function parsePayload(mapId) {
    const el = document.querySelector(`[data-map-payload="${mapId}"]`);
    if (! el) {
        return {};
    }

    try {
        return JSON.parse(el.textContent || '{}');
    } catch {
        return {};
    }
}

function popupHtml(marker) {
    if (marker.html) {
        return marker.html;
    }

    const parts = [];
    if (marker.label) {
        parts.push(`<strong>${escapeHtml(marker.label)}</strong>`);
    }
    if (marker.meta) {
        parts.push(`<div class="text-xs opacity-80">${escapeHtml(marker.meta)}</div>`);
    }
    if (marker.url) {
        parts.push(`<a href="${escapeAttr(marker.url)}" class="text-xs underline">Open</a>`);
    }

    return parts.join('') || '';
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function escapeAttr(value) {
    return String(value).replaceAll('"', '&quot;');
}

function applyLayers(entry, options) {
    const { map, layers } = entry;
    layers.clearLayers();

    const markers = Array.isArray(options.markers) ? options.markers : [];
    const polyline = Array.isArray(options.polyline) ? options.polyline : [];
    const circles = Array.isArray(options.circles) ? options.circles : [];
    const bounds = [];

    markers.forEach((marker) => {
        if (marker?.lat == null || marker?.lng == null) {
            return;
        }
        const point = [Number(marker.lat), Number(marker.lng)];
        bounds.push(point);
        L.marker(point).bindPopup(popupHtml(marker)).addTo(layers);
    });

    circles.forEach((circle) => {
        if (circle?.lat == null || circle?.lng == null || ! circle.radius) {
            return;
        }
        const point = [Number(circle.lat), Number(circle.lng)];
        bounds.push(point);
        L.circle(point, {
            radius: Number(circle.radius),
            color: circle.color || '#0284c7',
            fillOpacity: 0.08,
            weight: 1.5,
        }).addTo(layers);
    });

    if (polyline.length > 1) {
        const points = polyline
            .filter((point) => point?.lat != null && point?.lng != null)
            .map((point) => [Number(point.lat), Number(point.lng)]);

        if (points.length > 1) {
            L.polyline(points, { color: '#0284c7', weight: 4 }).addTo(layers);
            points.forEach((point) => bounds.push(point));
        }
    }

    if (options.fitBounds !== false && bounds.length > 1) {
        map.fitBounds(bounds, { padding: [28, 28], maxZoom: 16 });
    } else if (bounds.length === 1) {
        map.setView(bounds[0], Number(options.zoom) || 14);
    } else if (options.lat != null && options.lng != null) {
        map.setView([Number(options.lat), Number(options.lng)], Number(options.zoom) || 13);
    }

    requestAnimationFrame(() => map.invalidateSize());
}

export function initGuardCoreProMap(el) {
    if (! el || typeof el.id !== 'string' || ! el.id) {
        return;
    }

    const options = parsePayload(el.id);
    const lat = Number(options.lat) || 0;
    const lng = Number(options.lng) || 0;
    const zoom = Number(options.zoom) || 13;

    if (maps.has(el.id)) {
        applyLayers(maps.get(el.id), options);

        return;
    }

    if (el.dataset.initialized) {
        return;
    }

    el.dataset.initialized = '1';

    const map = L.map(el).setView([lat, lng], zoom);
    const layers = L.layerGroup().addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const entry = { map, layers };
    maps.set(el.id, entry);
    applyLayers(entry, options);
}

function initAllMaps() {
    document.querySelectorAll('[data-guard-core-pro-map]').forEach(initGuardCoreProMap);
}

document.addEventListener('DOMContentLoaded', initAllMaps);
document.addEventListener('livewire:navigated', initAllMaps);

document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', () => {
        requestAnimationFrame(initAllMaps);
    });
});

window.initGuardCoreProMap = initGuardCoreProMap;
