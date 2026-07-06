import { Html5Qrcode } from 'html5-qrcode';
import { initPushNotifications } from './push-notifications';

const DB_NAME = 'GuardCore Pro-offline';
const STORE_NAME = 'queue';
const DB_VERSION = 1;

function openDb() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id' });
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

export async function enqueueOfflineAction(action) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const item = {
            id: crypto.randomUUID(),
            created_at: new Date().toISOString(),
            ...action,
        };
        tx.objectStore(STORE_NAME).add(item);
        tx.oncomplete = () => resolve(item);
        tx.onerror = () => reject(tx.error);
    });
}

export async function readOfflineQueue() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const request = tx.objectStore(STORE_NAME).getAll();
        request.onsuccess = () => resolve(request.result.sort((a, b) => a.created_at.localeCompare(b.created_at)));
        request.onerror = () => reject(request.error);
    });
}

export async function clearOfflineQueue(ids) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        ids.forEach((id) => store.delete(id));
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

export function getCoords() {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            resolve({ lat: 0, lng: 0 });
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
            () => resolve(window.guardCoords || { lat: 0, lng: 0 }),
            { enableHighAccuracy: true, timeout: 8000, maximumAge: 10000 }
        );
    });
}

export async function guardWithGeo(wire, callbackName, offlineType = null, buildOfflineData = null) {
    const coords = await getCoords();
    wire.set('latitude', coords.lat);
    wire.set('longitude', coords.lng);

    if (!navigator.onLine && offlineType) {
        const data = buildOfflineData ? buildOfflineData(coords, wire) : { latitude: coords.lat, longitude: coords.lng };
        await enqueueOfflineAction({ type: offlineType, data });
        wire.set('statusMessage', `${offlineType.replace(/_/g, ' ')} queued — will sync when online.`);
        return;
    }

    if (typeof wire[callbackName] === 'function') {
        return wire[callbackName]();
    }
}

let qrScanner = null;

export async function startQrScanner(elementId, onScan) {
    const element = document.getElementById(elementId);
    if (!element) return;

    if (qrScanner) {
        await stopQrScanner();
    }

    qrScanner = new Html5Qrcode(elementId);
    const cameras = await Html5Qrcode.getCameras();
    const cameraId = cameras.length ? cameras[cameras.length - 1].id : { facingMode: 'environment' };

    await qrScanner.start(
        cameraId,
        { fps: 10, qrbox: { width: 220, height: 220 } },
        (decoded) => {
            onScan(decoded);
        },
        () => {}
    );
}

export async function stopQrScanner() {
    if (!qrScanner) return;
    try {
        await qrScanner.stop();
        await qrScanner.clear();
    } catch (_) {}
    qrScanner = null;
}

let nfcAbortController = null;

export async function startNfcScanner(onScan) {
    if (!('NDEFReader' in window)) {
        throw new Error('Web NFC is not supported on this device.');
    }

    await stopNfcScanner();

    const reader = new NDEFReader();
    nfcAbortController = new AbortController();

    reader.addEventListener('reading', ({ message, serialNumber }) => {
        let value = serialNumber;
        for (const record of message.records) {
            if (record.recordType === 'text') {
                const decoder = new TextDecoder(record.encoding || 'utf-8');
                value = decoder.decode(record.data);
                break;
            }
            if (record.recordType === 'url') {
                const decoder = new TextDecoder();
                value = decoder.decode(record.data);
                break;
            }
        }
        onScan(value);
    });

    await reader.scan({ signal: nfcAbortController.signal });
    return reader;
}

export async function stopNfcScanner() {
    if (nfcAbortController) {
        nfcAbortController.abort();
        nfcAbortController = null;
    }
}

export function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
}

export function watchGeolocation(onUpdate) {
    if (!navigator.geolocation) return;
    navigator.geolocation.watchPosition(
        (pos) => {
            window.guardCoords = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            onUpdate?.(window.guardCoords);
        },
        () => {},
        { enableHighAccuracy: true, maximumAge: 15000 }
    );
}

export async function flushOfflineQueue(wire) {
    if (!navigator.onLine) return 0;
    const items = await readOfflineQueue();
    if (!items.length) return 0;

    const payload = items.map(({ type, data }) => ({ type, ...data }));
    await wire.syncOfflineQueue(payload);
    await clearOfflineQueue(items.map((i) => i.id));
    return items.length;
}

export function initGuardPwa() {
    registerServiceWorker();
    watchGeolocation((coords) => {
        const root = document.querySelector('[data-guard-app]');
        if (root && window.Livewire) {
            const wire = Livewire.find(root.getAttribute('wire:id'));
            wire?.set('latitude', coords.lat);
            wire?.set('longitude', coords.lng);
        }
    });

    window.guardWithGeo = guardWithGeo;
    window.startQrScanner = startQrScanner;
    window.stopQrScanner = stopQrScanner;
    window.startNfcScanner = startNfcScanner;
    window.stopNfcScanner = stopNfcScanner;
    window.enqueueOfflineAction = enqueueOfflineAction;
    window.flushOfflineQueue = flushOfflineQueue;

    if (document.querySelector('meta[name="vapid-public-key"]')) {
        initPushNotifications();
    }

    window.addEventListener('online', () => {
        const root = document.querySelector('[data-guard-app]');
        if (root && window.Livewire) {
            const wire = Livewire.find(root.getAttribute('wire:id'));
            flushOfflineQueue(wire).then((count) => {
                if (count > 0) wire?.$refresh();
            });
        }
    });

    document.addEventListener('livewire:init', () => {
        const root = document.querySelector('[data-guard-app]');
        if (root && window.Livewire) {
            const wire = Livewire.find(root.getAttribute('wire:id'));
            flushOfflineQueue(wire);
        }
    });
}

initGuardPwa();
