import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

import axios from 'axios';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueSweetalert2, {
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });

        axios.interceptors.request.use(
            (config) => {
                console.log('Запрос улетает:', config.url);  // Дебаг
                return config;
            },
            (error) => {
                return Promise.reject(error);
            }
        );

        axios.interceptors.response.use(
            (response) => {
                if (response.config.method !== 'get') {
                    app.config.globalProperties.$swal({
                        icon: 'success',
                        title: 'Успех',
                    });
                }
                console.log('Ответ пришёл:', response.data);  // Дебаг
                return response;
            },
            (error) => {
                let message = 'Неуспех :(';
                if (error.response && error.response.data.message) {
                    message = error.response.data.message;
                } else if (error.response && error.response.data.errors) {
                    Object.values(error.response.data.errors).flat().forEach(errMsg => {
                        app.config.globalProperties.$swal({
                            icon: 'error',
                            title: errMsg,
                        });
                    });
                    return Promise.reject(error);
                } else if (error.message) {
                    message = error.message;
                }
                app.config.globalProperties.$swal({
                    icon: 'error',
                    title: message,
                });
                console.error('Ошибка ответа:', error);
                return Promise.reject(error);
            }
        );

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
