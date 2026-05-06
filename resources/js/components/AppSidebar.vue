<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronDown,
    ClipboardList,
    CreditCard,
    FileText,
    LayoutGrid,
    Layers,
    Link2,
    LogOut,
    Package,
    Settings,
    ShoppingBag,
    Store,
    UserCircle,
    X,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
    useSidebar,
} from '@/components/ui/sidebar';
import { useInitials } from '@/composables/useInitials';
import { dashboard, logout } from '@/routes';
import { edit as merchantStoreSettings } from '@/routes/merchant/store-settings';
import { edit as profileEdit } from '@/routes/profile';
import type { NavItem, User } from '@/types';

const page = usePage<{
    auth: { user: User | null };
    sellerNavBadges: { pending_orders: number } | null;
}>();
const { getInitials } = useInitials();
const { setOpenMobile } = useSidebar();

const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
const isSeller = computed(() => page.props.auth.user?.role === 'seller');

const sellerUser = computed(() => (isSeller.value ? page.props.auth.user : null));

const sellerPendingOrders = computed(() => page.props.sellerNavBadges?.pending_orders ?? 0);

const navGroupLabel = computed(() => {
    if (isAdmin.value) {
        return 'Platform';
    }
    if (isSeller.value) {
        return 'المتجر';
    }
    return 'Platform';
});

const sellerNavGeneralItems = computed<NavItem[]>(() => [
    {
        title: 'الرئيسية',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'المنتجات',
        href: '/merchant/products',
        icon: Package,
    },
    {
        title: 'الأصناف',
        href: '/merchant/categories',
        icon: Layers,
    },
    {
        title: 'الطلبات',
        href: '/merchant/orders',
        icon: ShoppingBag,
        matchPrefix: true,
        badge: sellerPendingOrders.value,
    },
]);

const sellerNavToolsItems = computed<NavItem[]>(() => [
    {
        title: 'رابط الدفع',
        href: '/merchant/payment-links',
        icon: Link2,
    },
    {
        title: 'الفواتير',
        href: '/merchant/invoices',
        icon: FileText,
    },
    {
        title: 'المدفوعات',
        href: '/merchant/payments',
        icon: CreditCard,
    },
    {
        title: 'الإعدادات',
        href: '/merchant/store-settings',
        icon: Settings,
    },
]);

const mainNavItems = computed<NavItem[]>(() => {
    if (isAdmin.value) {
        return [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
            {
                title: 'Requests',
                href: '/admin/requests',
                icon: ClipboardList,
            },
            {
                title: 'Sellers',
                href: '/admin/sellers',
                icon: Store,
            },
            {
                title: 'Settings',
                href: '/admin/settings',
                icon: Settings,
            },
        ];
    }

    if (isSeller.value) {
        return [];
    }

    return [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];
});

function handleLogoutClick(): void {
    router.flushAll();
}

function onSellerLogoutClick(): void {
    setOpenMobile(false);
    handleLogoutClick();
}
</script>

<template>
    <Sidebar
        :side="isSeller ? 'right' : 'left'"
        :content-dir="isSeller ? 'rtl' : undefined"
        collapsible="icon"
        variant="inset"
    >
        <template v-if="isSeller && sellerUser">
            <div
                class="merchant-pro-mobile-drawer-head border-b border-sidebar-border px-4 pb-5 md:hidden"
                style="padding-top: max(1.125rem, env(safe-area-inset-top, 0px))"
            >
                <div class="mb-4 flex justify-end">
                    <button
                        type="button"
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-sidebar-border bg-sidebar-accent text-sidebar-foreground transition hover:bg-sidebar-accent/80"
                        aria-label="إغلاق القائمة"
                        @click="setOpenMobile(false)"
                    >
                        <X class="size-[1.125rem]" stroke-width="2.25" />
                    </button>
                </div>
                <p class="mb-2 px-0.5 text-[11px] font-medium text-sidebar-foreground/45">
                    المتجر
                </p>
                <Link
                    :href="merchantStoreSettings.url()"
                    prefetch
                    class="flex items-center justify-between gap-3 rounded-2xl border border-sidebar-border bg-sidebar-accent px-3.5 py-3 shadow-[inset_0_1px_0_rgb(255_255_255_/0.7)] transition hover:bg-sidebar-accent/80"
                    @click="setOpenMobile(false)"
                >
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-sidebar-primary text-sm font-bold text-sidebar-primary-foreground shadow-sm ring-1 ring-sidebar-border/60"
                        >
                            {{ getInitials(sellerUser.name).slice(0, 1) || '—' }}
                        </span>
                        <span
                            class="truncate text-[15px] font-semibold leading-tight text-sidebar-foreground"
                            dir="auto"
                        >{{ sellerUser.name }}</span>
                    </div>
                    <ChevronDown class="size-4 shrink-0 text-sidebar-foreground/50" stroke-width="2" />
                </Link>
            </div>

            <SidebarHeader class="merchant-pro-sidebar-profile hidden px-3 pb-4 pt-5 md:block">
                <Link
                    :href="dashboard()"
                    class="flex flex-col items-center gap-3 text-center outline-none ring-sidebar-ring focus-visible:ring-2 [&:not(:focus-visible)]:ring-0 group-data-[collapsible=icon]:items-center group-data-[collapsible=icon]:gap-0"
                >
                    <Avatar
                        class="size-[4.25rem] shrink-0 border-[3px] border-[#c8a97e]/45 shadow-md ring-2 ring-white/10 group-data-[collapsible=icon]:size-9"
                    >
                        <AvatarImage
                            v-if="sellerUser.store_logo_url"
                            :src="sellerUser.store_logo_url"
                            :alt="sellerUser.name"
                        />
                        <AvatarFallback
                            class="bg-sidebar-accent/50 text-lg font-semibold text-sidebar-foreground group-data-[collapsible=icon]:text-xs"
                        >
                            {{ getInitials(sellerUser.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 px-1 group-data-[collapsible=icon]:hidden">
                        <p
                            class="text-sm font-semibold leading-snug text-sidebar-foreground"
                            dir="auto"
                        >
                            {{ sellerUser.name }}
                        </p>
                        <p
                            class="mt-1 break-all text-xs leading-snug text-sidebar-foreground/65"
                            dir="ltr"
                        >
                            {{ sellerUser.email }}
                        </p>
                    </div>
                </Link>
            </SidebarHeader>
        </template>

        <SidebarHeader v-else>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent
            :class="
                isSeller
                    ? 'merchant-pro-nav-scroll gap-0 overflow-x-hidden px-0 pb-3 pt-2 md:pb-4 md:pt-1'
                    : 'px-0 pb-4 pt-2 md:pt-0'
            "
        >
            <template v-if="isSeller">
                <NavMain
                    :items="sellerNavGeneralItems"
                    group-label="عام"
                    :merchant-pro="true"
                />
                    <SidebarSeparator class="mx-4 my-3 bg-sidebar-border md:mx-3" />
                <NavMain
                    :items="sellerNavToolsItems"
                    group-label="أدوات"
                    :merchant-pro="true"
                />
            </template>
            <NavMain
                v-else
                :items="mainNavItems"
                :group-label="navGroupLabel"
            />
        </SidebarContent>

        <SidebarFooter
            v-if="isSeller"
            class="mt-auto border-t border-sidebar-border p-3 pb-[max(0.75rem,env(safe-area-inset-bottom,0px))] md:p-2 md:pb-2"
        >
            <SidebarMenu class="gap-2 md:gap-1">
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="sm"
                        as-child
                        class="merchant-pro-footer-btn h-11 justify-start gap-2.5 rounded-xl px-3 md:h-9 md:px-2"
                        tooltip="الملف الشخصي"
                    >
                        <Link :href="profileEdit()" prefetch @click="setOpenMobile(false)">
                            <UserCircle class="size-[1.125rem] opacity-90" stroke-width="1.75" />
                            <span class="group-data-[collapsible=icon]:hidden">الملف الشخصي</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="sm"
                        as-child
                        class="merchant-pro-footer-btn h-11 justify-start gap-2.5 rounded-xl px-3 md:h-9 md:px-2"
                        tooltip="تسجيل الخروج"
                    >
                        <Link
                            :href="logout()"
                            data-test="logout-button"
                            @click="onSellerLogoutClick"
                        >
                            <LogOut class="size-[1.125rem] opacity-90" stroke-width="1.75" />
                            <span class="group-data-[collapsible=icon]:hidden">تسجيل الخروج</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>

        <SidebarFooter v-else>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
