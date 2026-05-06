<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance, BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage<{ auth: { user: { name?: string; role?: string } | null } }>();

const isSeller = computed(() => page.props.auth.user?.role === 'seller');
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
const isRtlDashboard = computed(() => isSeller.value || isAdmin.value);

const firstName = computed(() => {
    const raw = page.props.auth.user?.name?.trim() ?? '';
    if (!raw) {
        return '';
    }
    return raw.split(/\s+/)[0] ?? raw;
});

const { appearance, resolvedAppearance, updateAppearance } = useAppearance();

const themeOrder: Appearance[] = ['light', 'dark', 'system'];

function cycleTheme(): void {
    const idx = themeOrder.indexOf(appearance.value);
    const next = themeOrder[(idx + 1) % themeOrder.length];
    updateAppearance(next);
}
</script>

<template>
    <header
        class="flex min-h-14 shrink-0 flex-wrap items-center gap-3 border-b border-sidebar-border/40 bg-background/80 px-4 py-3 backdrop-blur-md transition-[width,height] ease-linear md:min-h-16 md:px-6 dark:border-sidebar-border/30"
        :dir="isRtlDashboard ? 'rtl' : undefined"
        :lang="isRtlDashboard ? 'ar' : undefined"
    >
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <SidebarTrigger class="-ms-1 shrink-0 text-foreground/80" />
            <div class="min-w-0 flex-1 text-start">
                <p v-if="isSeller" class="text-base font-bold tracking-tight text-foreground md:text-lg">
                    مرحباً، {{ firstName || 'تاجر' }}!
                </p>
                <p v-else-if="isAdmin" class="text-base font-bold tracking-tight text-foreground md:text-lg">
                    لوحة التحكم
                </p>
                <Breadcrumbs
                    v-if="breadcrumbs.length > 0"
                    :breadcrumbs="breadcrumbs"
                    :class="isSeller ? 'mt-0.5 text-[11px] text-muted-foreground md:text-xs' : ''"
                />
            </div>
        </div>

        <div
            v-if="isSeller"
            class="flex shrink-0 items-center gap-2"
        >
            <Button
                type="button"
                variant="outline"
                size="icon"
                class="size-10 shrink-0 rounded-full border-sidebar-border/60 bg-white/80 shadow-sm dark:bg-card/60"
                :title="`المظهر: ${appearance}`"
                @click="cycleTheme"
            >
                <Sun v-if="resolvedAppearance === 'light'" class="size-[1.15rem]" stroke-width="2" />
                <Moon v-else class="size-[1.15rem]" stroke-width="2" />
            </Button>
            <Button
                variant="outline"
                size="icon"
                class="size-10 shrink-0 rounded-full border-sidebar-border/60 bg-white/80 shadow-sm dark:bg-card/60"
                as-child
            >
                <Link href="/merchant/orders" title="الطلبات">
                    <Bell class="size-[1.15rem]" stroke-width="2" />
                </Link>
            </Button>
        </div>
    </header>
</template>
