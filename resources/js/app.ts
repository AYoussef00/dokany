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
const FALLBACK_ANALYTICS_EXCLUDED_PREFIXES = ['/dashboard', '/merchant', '/settings'];

let currentPath = globalThis.location?.pathname ?? '/';
let startedAtMs = Date.now();
let currentComponent: string | null = null;
let analyticsExcludedPrefixes: string[] = [...FALLBACK_ANALYTICS_EXCLUDED_PREFIXES];

function normalizeAnalyticsExcludedPrefixes(input: unknown): string[] | null {
    if (!Array.isArray(input)) {
        return null;
    }
    const out: string[] = [];
    for (const x of input) {
        if (typeof x === 'string' && x.length > 0) {
            out.push(x.startsWith('/') ? x : `/${x}`);
        }
    }
    return out.length > 0 ? out : null;
}

/** Mirrors App\Support\Analytics\PublicPageViewPathRules::isExcludedFromTracking */
function isPathExcludedFromPublicAnalytics(path: string, prefixes: string[]): boolean {
    const normalized = `/${path.replace(/^\/+/, '')}`;
    for (const prefix of prefixes) {
        const p = `/${prefix.replace(/^\/+/, '')}`;
        const base = p.replace(/\/+$/, '') || '/';
        if (normalized === base || normalized.startsWith(`${base}/`)) {
            return true;
        }
    }
    return false;
}

function shouldTrackPath(path: string): boolean {
    return !isPathExcludedFromPublicAnalytics(path, analyticsExcludedPrefixes);
}

function syncAnalyticsPrefixesFromInertiaPage(page: unknown): void {
    try {
        if (!page || typeof page !== 'object') {
            return;
        }
        const props = (page as { props?: unknown }).props;
        if (!props || typeof props !== 'object') {
            return;
        }
        const dokany = (props as { dokany?: { analyticsPublicPathExcludePrefixes?: unknown } }).dokany;
        const next = normalizeAnalyticsExcludedPrefixes(dokany?.analyticsPublicPathExcludePrefixes);
        if (next) {
            analyticsExcludedPrefixes = next;
        }
    } catch {
        // ignore
    }
}

function tryReadInitialComponent(): void {
    try {
        const el = globalThis.document?.querySelector?.('[data-page]');
        const raw = el?.getAttribute?.('data-page');
        if (!raw) {
            return;
        }
        const page = JSON.parse(raw) as { component?: unknown; url?: unknown; props?: unknown };
        syncAnalyticsPrefixesFromInertiaPage(page);
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
        syncAnalyticsPrefixesFromInertiaPage(page);
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
