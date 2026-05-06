import { createInertiaApp } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const rawAppName = import.meta.env.VITE_APP_NAME || 'Dokany';
const appName = rawAppName.toLowerCase().includes('laravel') ? 'Dokany' : rawAppName;

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('public/'):
                return null;
            case name === 'auth/Register':
                return null;
            case name === 'auth/Login':
                return null;
            case name.startsWith('onboarding/'):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// --- Lightweight analytics (page duration / journey) ---
let currentPath = globalThis.location?.pathname ?? '/';
let startedAtMs = Date.now();
let currentComponent: string | null = null;

function shouldTrackPath(path: string): boolean {
    return !/^\/(dashboard|merchant|settings)(\/|$)/.test(path);
}

function tryReadInitialComponent(): void {
    try {
        const el = globalThis.document?.querySelector?.('[data-page]');
        const raw = el?.getAttribute?.('data-page');
        if (!raw) {
            return;
        }
        const page = JSON.parse(raw) as { component?: unknown; url?: unknown };
        if (typeof page.component === 'string' && page.component.length > 0) {
            currentComponent = page.component;
        }
        if (typeof page.url === 'string' && page.url.length > 0) {
            currentPath = new URL(page.url, globalThis.location?.origin ?? 'http://localhost').pathname;
        }
    } catch {
        // ignore
    }
}

function getCsrfToken(): string | null {
    const el = globalThis.document?.querySelector('meta[name="csrf-token"]');
    const v = el?.getAttribute('content');
    return v && v.length > 0 ? v : null;
}

async function flushPageView(reason: string): Promise<void> {
    const end = Date.now();
    const durationSeconds = Math.max(0, Math.round((end - startedAtMs) / 1000));
    const path = currentPath;
    const component = currentComponent;

    // Reset immediately to avoid double-counting.
    currentPath = globalThis.location?.pathname ?? path;
    startedAtMs = end;

    if (durationSeconds < 1) {
        return;
    }

    if (!shouldTrackPath(path)) {
        return;
    }

    const csrf = getCsrfToken();
    if (!csrf) {
        return;
    }

    try {
        await fetch('/analytics/pageview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                path,
                component,
                started_at: new Date(end - durationSeconds * 1000).toISOString(),
                duration_seconds: durationSeconds,
                referrer: globalThis.document?.referrer || null,
                reason,
            }),
            keepalive: reason === 'unload' || reason === 'visibility',
        });
    } catch {
        // ignore
    }
}

tryReadInitialComponent();

router.on('start', () => {
    void flushPageView('navigate');
});

router.on('success', (event: any) => {
    try {
        const page = event?.detail?.page;
        const url = typeof page?.url === 'string' ? page.url : globalThis.location?.href;
        if (typeof url === 'string' && url.length > 0) {
            currentPath = new URL(url, globalThis.location?.origin ?? 'http://localhost').pathname;
        }
        currentComponent = typeof page?.component === 'string' && page.component.length > 0 ? page.component : null;
        startedAtMs = Date.now();
    } catch {
        // ignore
    }
});

globalThis.addEventListener?.('visibilitychange', () => {
    if (globalThis.document?.visibilityState === 'hidden') {
        void flushPageView('visibility');
    }
});

globalThis.addEventListener?.('beforeunload', () => {
    void flushPageView('unload');
});
