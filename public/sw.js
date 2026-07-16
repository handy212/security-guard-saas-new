const CACHE = 'guardops-field-v1';
const PRECACHE = ['/guard', '/build/manifest.json'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('push', (event) => {
    let payload = { title: 'GuardCore Pro', body: 'New notification', url: '/dashboard' };

    try {
        if (event.data) {
            payload = { ...payload, ...event.data.json() };
        }
    } catch (_) {}

    event.waitUntil(
        self.registration.showNotification(payload.title, {
            body: payload.body,
            icon: '/icons/icon-192.png',
            badge: '/icons/icon-192.png',
            data: { url: payload.url },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if ('focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    if (url.pathname.startsWith('/guard') || url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                const network = fetch(event.request)
                    .then((response) => {
                        if (response.ok) {
                            const clone = response.clone();
                            caches.open(CACHE).then((cache) => cache.put(event.request, clone));
                        }
                        return response;
                    })
                    .catch(() => cached);

                return cached || network;
            })
        );
    }
});
