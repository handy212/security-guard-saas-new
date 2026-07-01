import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const storedTheme = localStorage.getItem('theme');
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

if (storedTheme === 'dark' || (! storedTheme && prefersDark)) {
    document.documentElement.classList.add('dark');
}

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

    echo.connector.pusher.connection.bind('connected', () => {
        window.Echo = echo;
    });
}
