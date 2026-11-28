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

function parsePayload(payload) {
    try {
        return JSON.parse(payload || '{}');
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

const shieldSymbols = { protected: '✓', unprotected: '×', understaffed: '!', verification_required: '!', inactive: '–', not_scheduled: '–' };

function drawSiteMarkers(entry, sites) {
    const { map, siteLayer } = entry;
    siteLayer.clearLayers();

    const groups = new Map();
    sites.forEach((site) => {
        const point = map.project([site.lat, site.lng]);
        const key = map.getZoom() >= 17
            ? `${site.lat}:${site.lng}`
            : `${Math.floor(point.x / 48)}:${Math.floor(point.y / 48)}`;
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(site);
    });

    for (const group of groups.values()) {
        if (group.length > 1) {
            const bounds = L.latLngBounds(group.map((site) => [site.lat, site.lng]));
            const label = `${group.length} sites`;
            const cluster = L.marker(bounds.getCenter(), {
                title: label,
                alt: label,
                icon: L.divIcon({ className: 'tracking-cluster', html: String(group.length), iconSize: [34, 34] }),
            }).addTo(siteLayer);
            if (map.getZoom() >= 17 || bounds.getNorthEast().equals(bounds.getSouthWest())) {
                const list = document.createElement('div');
                group.forEach((site) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'block text-left underline';
                    button.textContent = `${site.label} · ${site.statusLabel}`;
                    button.addEventListener('click', () => selectSite(entry, site));
                    list.append(button);
                });
                cluster.bindPopup(list);
            } else {
                cluster.on('click', () => map.fitBounds(bounds, { padding: [25, 25], maxZoom: 17 }));
            }
            continue;
        }

        const [site] = group;
        const status = Object.hasOwn(shieldSymbols, site.status) ? site.status : 'not_scheduled';
        const label = `${site.label} · ${site.statusLabel}`;
        const size = status === 'unprotected' ? 36 : 30;
        const marker = L.marker([site.lat, site.lng], {
            title: label,
            alt: label,
            zIndexOffset: status === 'unprotected' ? 200 : 100,
            icon: L.divIcon({
                className: 'tracking-site-icon',
                html: `<span class="tracking-shield tracking-shield--${status}" aria-hidden="true">${shieldSymbols[status]}</span>`,
                iconSize: [size, size],
                iconAnchor: [size / 2, size / 2],
            }),
        }).on('click', () => selectSite(entry, site)).addTo(siteLayer);
        marker.getElement()?.setAttribute('aria-label', label);
    }
}

function selectSite(entry, site) {
    entry.map.closePopup();
    entry.element.dispatchEvent(new CustomEvent('tracking-site-selected', {
        bubbles: true,
        detail: { siteId: site.siteId },
    }));
}

function applyLayers(entry, options) {
    const { map, layers } = entry;
    layers.clearLayers();

    const markers = Array.isArray(options.markers)
        ? options.markers.filter((marker) => marker?.lat != null && marker?.lng != null)
        : [];
    const polyline = Array.isArray(options.polyline) ? options.polyline : [];
    const circles = Array.isArray(options.circles) ? options.circles : [];
    const bounds = [];
    entry.sites = markers.filter((marker) => marker.siteId != null);

    const alarmsByPosition = new Map();

    markers.forEach((marker) => {
        const point = [Number(marker.lat), Number(marker.lng)];
        bounds.push(point);
        if (marker.alarmId) {
            const key = point.map((coordinate) => coordinate.toFixed(6)).join(':');
            if (!alarmsByPosition.has(key)) alarmsByPosition.set(key, []);
            alarmsByPosition.get(key).push(marker);
        } else if (marker.standbyPointId != null) {
            const label = `Standby point · ${marker.label}`;
            const pin = L.marker(point, {
                title: label,
                zIndexOffset: 300,
                icon: L.divIcon({ className: 'tracking-standby', html: 'S', iconSize: [28, 28], iconAnchor: [14, 14] }),
            }).bindPopup(popupHtml(marker)).addTo(layers);
            pin.getElement()?.setAttribute('aria-label', label);
        } else if (marker.siteId == null) {
            L.marker(point).bindPopup(popupHtml(marker)).addTo(layers);
        }
    });

    for (const alarms of alarmsByPosition.values()) {
        const [first] = alarms;
        const label = alarms.length === 1 ? `Patriot alarm · ${first.label}` : `${alarms.length} Patriot alarms`;
        const popup = alarms.map(popupHtml).join('<hr>');
        const marker = L.marker([first.lat, first.lng], {
            title: label,
            alt: label,
            zIndexOffset: 1000,
            icon: L.divIcon({
                className: alarms.some((alarm) => alarm.critical) ? 'tracking-alarm tracking-alarm--critical' : 'tracking-alarm',
                html: `<span aria-hidden="true">${alarms.length === 1 ? '!' : alarms.length}</span>`,
                iconSize: [34, 34],
                iconAnchor: [17, 17],
            }),
        }).bindPopup(popup).addTo(layers);
        marker.getElement()?.setAttribute('aria-label', label);
    }

    circles.forEach((circle) => {
        if (circle?.lat == null || circle?.lng == null || ! circle.radius) {
            return;
        }
        const point = [Number(circle.lat), Number(circle.lng)];
        const area = L.circle(point, {
            radius: Number(circle.radius),
            color: circle.color || '#0284c7',
            fillOpacity: 0.08,
            weight: 1.5,
        }).addTo(layers).getBounds();
        bounds.push(area.getSouthWest(), area.getNorthEast());
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

    entry.bounds = bounds;
    entry.pointZoom = Number(options.zoom) || 14;
    if (entry.fitButton) entry.fitButton.disabled = bounds.length === 0;

    const resetViewport = options.viewportKey == null || entry.viewportKey !== options.viewportKey;
    entry.viewportKey = options.viewportKey;
    if (!resetViewport) {
        drawSiteMarkers(entry, entry.sites);
        return;
    }

    if (options.fitBounds === false && options.lat != null && options.lng != null) {
        map.setView([Number(options.lat), Number(options.lng)], Number(options.zoom) || 13);
    } else if (bounds.length) {
        fitVisibleLocations(entry);
    } else if (options.lat != null && options.lng != null) {
        map.setView([Number(options.lat), Number(options.lng)], Number(options.zoom) || 13);
    }

    drawSiteMarkers(entry, entry.sites);
}

function fitVisibleLocations({ map, bounds, pointZoom }) {
    const visibleBounds = L.latLngBounds(bounds);
    if (!visibleBounds.isValid()) return;

    if (visibleBounds.getSouthWest().equals(visibleBounds.getNorthEast())) {
        map.setView(visibleBounds.getCenter(), pointZoom);
    } else {
        map.fitBounds(visibleBounds, { padding: [28, 28], maxZoom: 16 });
    }
}

function addFitControl(entry) {
    const control = L.control({ position: 'topleft' });
    control.onAdd = () => {
        const container = L.DomUtil.create('div', 'leaflet-bar');
        const button = L.DomUtil.create('button', 'map-fit-button', container);
        button.type = 'button';
        button.title = 'Fit visible locations';
        button.setAttribute('aria-label', button.title);
        button.textContent = '⌖';
        L.DomEvent.disableClickPropagation(container);
        button.addEventListener('click', () => fitVisibleLocations(entry));
        entry.fitButton = button;

        return container;
    };
    control.addTo(entry.map);
}

export function initGuardCoreProMap(el) {
    if (! el || typeof el.id !== 'string' || ! el.id) {
        return;
    }

    const shell = el.closest('[data-map-shell]');
    const payload = shell?.querySelector('[data-map-payload]')?.textContent ?? '';
    const existing = maps.get(el.id);

    if (existing?.element === el) {
        if (existing.payload !== payload) {
            existing.payload = payload;
            applyLayers(existing, parsePayload(payload));
        }

        return;
    }

    if (existing) {
        existing.map.remove();
    }

    const options = parsePayload(payload);
    const lat = Number(options.lat) || 0;
    const lng = Number(options.lng) || 0;
    const zoom = Number(options.zoom) || 13;
    const map = L.map(el).setView([lat, lng], zoom);
    const layers = L.layerGroup().addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const entry = { element: el, payload, map, layers, siteLayer: L.layerGroup().addTo(map), sites: [] };
    map.on('zoomend', () => {
        if (entry.sites.length) drawSiteMarkers(entry, entry.sites);
    });
    if (shell?.hasAttribute('data-map-fit-control')) addFitControl(entry);

    const resizeObserver = new ResizeObserver(() => map.invalidateSize({ debounceMoveend: true }));
    resizeObserver.observe(el);
    map.once('unload', () => resizeObserver.disconnect());

    maps.set(el.id, entry);
    applyLayers(entry, options);
}

function initAllMaps() {
    for (const [id, entry] of maps) {
        if (! entry.element.isConnected) {
            entry.map.remove();
            maps.delete(id);
        }
    }

    document.querySelectorAll('[data-guard-core-pro-map]').forEach(initGuardCoreProMap);
}

document.addEventListener('DOMContentLoaded', initAllMaps);
document.addEventListener('livewire:navigated', initAllMaps);

document.addEventListener('livewire:init', () => {
    let scheduled = false;
    Livewire.hook('morphed', () => {
        if (scheduled) return;

        scheduled = true;
        requestAnimationFrame(() => {
            scheduled = false;
            initAllMaps();
        });
    });
});

window.initGuardCoreProMap = initGuardCoreProMap;
