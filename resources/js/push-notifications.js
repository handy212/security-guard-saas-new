export function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    const output = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i += 1) {
        output[i] = raw.charCodeAt(i);
    }
    return output;
}

function arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    bytes.forEach((b) => {
        binary += String.fromCharCode(b);
    });
    return window.btoa(binary);
}

function csrfHeaders() {
    const headers = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const meta = document.querySelector('meta[name="csrf-token"]')?.content;
    if (meta) {
        headers['X-CSRF-TOKEN'] = meta;
    }

    return headers;
}

let subscribeInFlight = null;
let subscribedEndpoint = null;

export async function registerPushSubscription() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        return false;
    }

    const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
    if (!vapidKey) {
        return false;
    }

    if (subscribeInFlight) {
        return subscribeInFlight;
    }

    subscribeInFlight = (async () => {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            return false;
        }

        const registration = await navigator.serviceWorker.ready;
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey),
            });
        }

        if (subscribedEndpoint === subscription.endpoint) {
            return true;
        }

        const response = await fetch('/push/subscribe', {
            method: 'POST',
            credentials: 'same-origin',
            headers: csrfHeaders(),
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                public_key: arrayBufferToBase64(subscription.getKey('p256dh')),
                auth_token: arrayBufferToBase64(subscription.getKey('auth')),
                content_encoding: 'aesgcm',
            }),
        });

        if (response.ok) {
            subscribedEndpoint = subscription.endpoint;
        }

        return response.ok;
    })().finally(() => {
        subscribeInFlight = null;
    });

    return subscribeInFlight;
}

export function initPushNotifications() {
    if (!document.querySelector('meta[name="vapid-public-key"]')?.content) {
        return;
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').then(() => {
            registerPushSubscription().catch(() => {});
        }).catch(() => {});
    }
}
