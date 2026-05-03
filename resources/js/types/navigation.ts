import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    /** When true, item is active if current URL starts with this href path. */
    matchPrefix?: boolean;
    /** Count shown as a badge (e.g. pending orders); omit or zero to hide. */
    badge?: number;
};
