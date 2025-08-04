import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Добавляем Laravel Echo с Reverb
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    cluster: null,  // Добавь это, чтобы убрать лишний /app и ошибку cluster
    wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT || 8081,  // Fallback на 8081, если env не подхватился
    wssPort: import.meta.env.VITE_REVERB_PORT || 8081,  // То же для wss
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME === 'https'),
    enabledTransports: ['ws'],
    disableStats: true,
});

