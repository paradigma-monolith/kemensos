import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { initSentry } from './sentry';
import './echo';

createInertiaApp({
    title: (title) => title ? `${title} — Hybrid Laravel` : 'Hybrid Laravel Architecture',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`];
        if (!page) {
            throw new Error(`Inertia page not found: ./Pages/${name}.vue`);
        }
        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        initSentry(app);
        app.mount(el);
    },
    progress: {
        color: '#6366f1',
        showSpinner: true,
    },
});
