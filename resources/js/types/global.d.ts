import type { Auth } from '@/types/auth';

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

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            dokany: {
                baseUrl: string;
                supportPhoneE164: string;
                seoOgImage: string;
                seoOrganizationLogo: string;
                analyticsPublicPathExcludePrefixes: string[];
            };
            auth: Auth;
            sidebarOpen: boolean;
            flash: {
                success?: string | null;
                error?: string | null;
                payment_receipt_received?: boolean | null;
            };
            sellerAccess: {
                accessEndsAt: string | null;
                accessDays: number;
                accessMonths: number | null;
                isExpired: boolean;
            } | null;
            [key: string]: unknown;
        };
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
