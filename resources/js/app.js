import './dashboard-charts.js';
import './map.js';
import { initTheme, setTheme } from './theme.js';

initTheme();
window.setTheme = setTheme;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('sidebarNav', (activeGroup = null) => ({
        open: activeGroup,
        flyout: null,
        flyoutTimer: null,
        favorites: JSON.parse(localStorage.getItem('GuardCore Pro-nav-favorites') || '[]'),
        showFlyout(id, el = null) {
            clearTimeout(this.flyoutTimer);
            this.flyout = id;
            if (! el || el.hasAttribute('data-flyout-id')) {
                return;
            }
            this.$nextTick(() => {
                const panel = document.querySelector(`[data-flyout-id="${id}"]`);
                if (! panel) {
                    return;
                }
                const rect = el.getBoundingClientRect();
                const panelHeight = panel.offsetHeight || 240;
                let top = rect.top;
                if (top + panelHeight > window.innerHeight - 12) {
                    top = Math.max(12, window.innerHeight - panelHeight - 12);
                }
                panel.style.top = `${top}px`;
            });
        },
        hideFlyout() {
            clearTimeout(this.flyoutTimer);
            this.flyoutTimer = setTimeout(() => {
                this.flyout = null;
            }, 140);
        },
        toggleFavorite(href, label) {
            const i = this.favorites.findIndex((f) => f.href === href);
            if (i >= 0) {
                this.favorites.splice(i, 1);
            } else {
                this.favorites.push({ href, label });
            }
            localStorage.setItem('GuardCore Pro-nav-favorites', JSON.stringify(this.favorites));
        },
        isFavorite(href) {
            return this.favorites.some((f) => f.href === href);
        },
    }));
});

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { initPushNotifications } from './push-notifications';

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
    window.Pusher = Pusher;

    const echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    // Only expose Echo to Livewire after the socket connects so X-Socket-ID is never "undefined".
    echo.connector.pusher.connection.bind('connected', () => {
        window.Echo = echo;
    });
}

if (document.querySelector('meta[name="vapid-public-key"]')) {
    initPushNotifications();
}
