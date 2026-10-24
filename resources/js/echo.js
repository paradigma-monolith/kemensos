import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

if (typeof window !== 'undefined') {
    window.Pusher = Pusher;
}

let echoInstance = null;

/**
 * Get or initialize Laravel Echo instance connected to Laravel Reverb WebSocket.
 *
 * @returns {Echo|null}
 */
export function getEcho() {
    if (echoInstance) {
        return echoInstance;
    }

    const appKey = import.meta.env.VITE_REVERB_APP_KEY;
    if (!appKey) {
        return null;
    }

    const host = import.meta.env.VITE_REVERB_HOST || (typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1');
    const port = Number(import.meta.env.VITE_REVERB_PORT || 8080);
    const scheme = import.meta.env.VITE_REVERB_SCHEME || 'http';

    try {
        echoInstance = new Echo({
            broadcaster: 'reverb',
            key: appKey,
            wsHost: host,
            wsPort: port,
            wssPort: port,
            forceTLS: scheme === 'https',
            enabledTransports: ['ws', 'wss'],
        });

        if (typeof window !== 'undefined') {
            window.Echo = echoInstance;
        }
    } catch (e) {
        console.warn('[Echo] Inisialisasi Echo fallback:', e);
    }

    return echoInstance;
}

export const echo = typeof window !== 'undefined' ? getEcho() : null;
export default getEcho;
