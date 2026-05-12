import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { initializeTheme } from './composables/useAppearance';
import Swal from 'sweetalert2';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4F61E8',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Gestion globale des erreurs serveur (500, 503, etc.)
router.on('error', (event) => {
    Swal.fire({
        icon: 'error',
        title: 'Une erreur est survenue',
        text: 'Le serveur a rencontré un problème. Veuillez réessayer ou contacter le support si le problème persiste.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#4f46e5',
    });
});

router.on('invalid', (event) => {
    event.preventDefault();
    Swal.fire({
        icon: 'error',
        title: 'Erreur inattendue',
        text: 'Une réponse inattendue a été reçue du serveur. Veuillez réessayer.',
        confirmButtonText: 'OK',
        confirmButtonColor: '#4f46e5',
    });
});
