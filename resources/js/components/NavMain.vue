<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

withDefaults(
    defineProps<{
        items: NavItem[];
        groupLabel?: string;
        /** تنسيق أزرار التنقل لمظهر لوحة التاجر (سايدبار داكن) */
        merchantPro?: boolean;
    }>(),
    { groupLabel: 'Platform', merchantPro: false },
);

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const { isMobile, setOpenMobile } = useSidebar();

function onMerchantNavClick(): void {
    if (isMobile.value) {
        setOpenMobile(false);
    }
}
</script>

<template>
    <SidebarGroup :class="merchantPro ? 'px-3 py-0.5' : 'px-2 py-0'">
        <SidebarGroupLabel
            v-if="merchantPro"
            class="mb-2.5 h-auto px-1 py-0 text-[11px] font-semibold tracking-wide text-sidebar-foreground/55"
        >
            {{ groupLabel }}
        </SidebarGroupLabel>
        <SidebarGroupLabel v-else>{{ groupLabel }}</SidebarGroupLabel>
        <SidebarMenu :class="merchantPro ? 'gap-1' : ''">
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="
                        item.matchPrefix
                            ? isCurrentOrParentUrl(item.href)
                            : isCurrentUrl(item.href)
                    "
                    :tooltip="item.title"
                    :class="
                        merchantPro
                            ? 'merchant-pro-nav-item h-11 rounded-xl px-2.5 text-[15px] !text-start md:h-10'
                            : undefined
                    "
                >
                    <Link
                        :href="item.href"
                        :class="
                            merchantPro
                                ? 'flex w-full min-w-0 items-center gap-3'
                                : undefined
                        "
                        @click="merchantPro ? onMerchantNavClick() : undefined"
                    >
                        <span
                            v-if="merchantPro"
                            class="merchant-pro-nav-icon-wrap flex size-9 shrink-0 items-center justify-center rounded-lg bg-sidebar-accent text-sidebar-foreground shadow-[inset_0_0_0_1px_hsl(var(--sidebar-border))]"
                        >
                            <component :is="item.icon" class="size-[1.125rem] stroke-[1.75]" />
                        </span>
                        <template v-else>
                            <component :is="item.icon" class="shrink-0" />
                        </template>
                        <span
                            :class="
                                merchantPro
                                    ? 'min-w-0 flex-1 truncate font-medium leading-snug text-sidebar-foreground'
                                    : undefined
                            "
                        >{{ item.title }}</span>
                        <span
                            v-if="merchantPro && item.badge != null && item.badge > 0"
                            class="flex h-5 min-w-[1.25rem] shrink-0 items-center justify-center rounded-full bg-sidebar-primary px-1.5 text-[11px] font-bold tabular-nums text-sidebar-primary-foreground"
                            dir="ltr"
                            lang="en"
                        >
                            {{ item.badge > 99 ? '99+' : item.badge }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
