import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY
    || document.head.querySelector('meta[name="pusher-key"]')?.content;
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER
    || document.head.querySelector('meta[name="pusher-cluster"]')?.content
    || 'mt1';
const pusherHost = import.meta.env.VITE_PUSHER_HOST
    || document.head.querySelector('meta[name="pusher-host"]')?.content
    || undefined;
const pusherPort = Number(
    import.meta.env.VITE_PUSHER_PORT
    || document.head.querySelector('meta[name="pusher-port"]')?.content,
);
const pusherScheme = import.meta.env.VITE_PUSHER_SCHEME
    || document.head.querySelector('meta[name="pusher-scheme"]')?.content
    || 'https';
const useTLS = pusherScheme === 'https';
    
if (pusherKey) {
    const echoOptions = {
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: useTLS,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        },

    };

    if (pusherHost) {
        echoOptions.wsHost = pusherHost;
        echoOptions.httpHost = pusherHost;
        echoOptions.enabledTransports = ['ws', 'wss'];
    }

    if (Number.isFinite(pusherPort) && pusherPort > 0) {
        echoOptions.wsPort = pusherPort;
        echoOptions.wssPort = pusherPort;
    }

    window.Echo = new Echo(echoOptions);
} else {
    console.warn('[Echo] PUSHER_APP_KEY/VITE_PUSHER_APP_KEY não configurada; notificações em tempo real foram desativadas.');
}
