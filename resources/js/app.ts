import { createInertiaApp } from '@inertiajs/vue3';
import PokerLayout from '@/layouts/PokerLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        return name.startsWith('Poker/') ? PokerLayout : null;
    },
    progress: {
        color: '#4B5563',
    },
});

// This will listen for flash toast data from the server...
initializeFlashToast();
