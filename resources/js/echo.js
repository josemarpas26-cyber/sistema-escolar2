import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY
    || document.head.querySelector('meta[name="pusher-key"]')?.content;
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER
    || document.head.querySelector('meta[name="pusher-cluster"]')?.content
    || 'mt1';
    
if (pusherKey) {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: pusherCluster,
        forceTLS: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        },
    });
} else {
    console.warn('[Echo] PUSHER_APP_KEY/VITE_PUSHER_APP_KEY não configurada; notificações em tempo real foram desativadas.');
}
