<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Bell,
    Calendar,
    ClipboardList,
    FileText,
    Menu,
    Package,
    Search,
    ShoppingCart,
    Store,
    TrendingUp,
    Users,
    Globe,
    Wallet,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { useSidebar } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';

const ADMIN_STATS_POLL_MS = 15_000;
const SEVEN_DAYS_MS = 7 * 24 * 60 * 60 * 1000;
const RENEWAL_ALERT_SESSION_PREFIX = 'dokany_seller_renewal_swal';

type DashboardStats = {
    total_revenue: number;
    currency_en: string;
    active_merchants_count: number;
    pending_requests_count: number;
    visitors_today: number;
    visitors_total: number;
    top_countries_30d: { country: string; visitors: number }[];
};

type OrderStatusSlice = {
    status: string;
    label: string;
    color: string;
    count: number;
};

type SellerDashboardStats = {
    total_revenue: number;
    currency_en: string;
    products_count: number;
    confirmed_orders_count: number;
    new_orders_count: number;
    invoices_count: number;
    storefront_visits_count: number;
    orders_today_count: number;
    orders_total_count: number;
    order_status_breakdown: OrderStatusSlice[];
    orders_monthly_trend: { label: string; value: number }[];
    orders_first_at: string | null;
    orders_last_at: string | null;
    storefront_url: string | null;
};

const page = usePage<{
    sellerAccess: {
        accessEndsAt: string | null;
        isExpired: boolean;
    } | null;
    dashboardStats: DashboardStats | null;
    sellerDashboardStats: SellerDashboardStats | null;
    auth: { user: { role?: string } | null };
}>();

const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

const isSeller = computed(() => page.props.auth.user?.role === 'seller');

const pageTitle = computed(() => {
    if (isSeller.value) {
        return 'لوحة التحكم';
    }
    return 'Dashboard';
});

const { toggleSidebar } = useSidebar();

const sellerDashboardStats = computed(() => page.props.sellerDashboardStats);

const orderSearch = ref('');

function goOrdersSearch(): void {
    const q = orderSearch.value.trim();
    router.visit(q === '' ? '/merchant/orders' : `/merchant/orders?q=${encodeURIComponent(q)}`);
}

function formatShortDate(iso: string | null): string {
    if (iso === null || iso === '') {
        return '—';
    }
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) {
        return '—';
    }
    return new Intl.DateTimeFormat('ar-EG', { dateStyle: 'medium' }).format(d);
}

const orderStatusPieStyle = computed(() => {
    const s = sellerDashboardStats.value;
    if (!s?.order_status_breakdown?.length) {
        return { background: 'conic-gradient(#e5e7eb 0% 100%)' };
    }
    const slices = s.order_status_breakdown.filter((x) => x.count > 0);
    const total = slices.reduce((a, x) => a + x.count, 0);
    if (total === 0) {
        return { background: 'conic-gradient(#e5e7eb 0% 100%)' };
    }
    let acc = 0;
    const parts = slices.map((x) => {
        const pct = (x.count / total) * 100;
        const start = acc;
        acc += pct;
        return `${x.color} ${start}% ${acc}%`;
    });
    return { background: `conic-gradient(${parts.join(', ')})` };
});

const performanceLineChart = computed(() => {
    const trend = sellerDashboardStats.value?.orders_monthly_trend ?? [];
    const vals = trend.map((d) => d.value);
    const max = Math.max(1, ...vals);
    const w = 640;
    const h = 240;
    const padX = 36;
    const padY = 28;
    const innerW = w - padX * 2;
    const innerH = h - padY * 2;
    const n = trend.length;
    const pts = trend.map((d, i) => {
        const x = padX + (n <= 1 ? innerW / 2 : (i / (n - 1)) * innerW);
        const y = padY + innerH - (d.value / max) * innerH;
        return `${x},${y}`;
    });
    return {
        w,
        h,
        max,
        points: pts.join(' '),
        trend,
    };
});

const dashboardStats = computed(() => page.props.dashboardStats);

function formatRevenue(amount: number, currency: string): string {
    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount);
    return `${formatted} ${currency}`;
}

function formatEnInteger(value: number): string {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(value);
}

function maybeShowSellerRenewalWarning(): void {
    if (!isSeller.value) {
        return;
    }

    const sa = page.props.sellerAccess;
    if (!sa?.accessEndsAt || sa.isExpired) {
        return;
    }

    const end = new Date(sa.accessEndsAt);
    if (Number.isNaN(end.getTime())) {
        return;
    }

    const msLeft = end.getTime() - Date.now();
    if (msLeft <= 0 || msLeft > SEVEN_DAYS_MS) {
        return;
    }

    const storageKey = `${RENEWAL_ALERT_SESSION_PREFIX}:${sa.accessEndsAt}`;
    if (globalThis.sessionStorage?.getItem(storageKey) === '1') {
        return;
    }

    const formatted = new Intl.DateTimeFormat('ar-EG', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(end);

    void Swal.fire({
        icon: 'warning',
        title: 'تنبيه انتهاء الاشتراك',
        html:
            `<div dir="rtl" style="text-align:right;font-size:1rem;line-height:1.65">` +
            `سيُنتهي اشتراكك في <strong>${formatted}</strong>.` +
            `<br><br>يجب عليك التجديد قبل هذا التاريخ لمتابعة استخدام حسابك على المنصة.` +
            `</div>`,
        confirmButtonText: 'حسناً',
        reverseButtons: true,
        allowOutsideClick: true,
        didOpen: () => {
            const el = Swal.getPopup();
            el?.setAttribute('dir', 'rtl');
        },
    }).then(() => {
        globalThis.sessionStorage?.setItem(storageKey, '1');
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'الرئيسية',
                href: dashboard(),
            },
        ],
    },
});

let statsPollId: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    if (isAdmin.value && page.props.dashboardStats) {
        statsPollId = setInterval(() => {
            router.reload({
                only: ['dashboardStats'],
            });
        }, ADMIN_STATS_POLL_MS);
    }

    maybeShowSellerRenewalWarning();
});

onUnmounted(() => {
    if (statsPollId !== null) {
        clearInterval(statsPollId);
        statsPollId = null;
    }
});
</script>

<template>
    <Head :title="pageTitle" />

    <div
        class="dokany-admin-dashboard flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6"
        :dir="isSeller ? 'rtl' : undefined"
        :lang="isSeller ? 'ar' : undefined"
    >
        <div
            v-if="isAdmin"
            dir="rtl"
            lang="ar"
            class="flex flex-col gap-1"
        >
            <h1 class="text-lg font-bold tracking-tight text-foreground md:text-xl">لوحة التحكم</h1>
            <p class="text-sm text-muted-foreground">نظرة سريعة على أداء المنصة خلال آخر 30 يوم.</p>
        </div>

        <div
            v-if="isAdmin && dashboardStats"
            dir="rtl"
            lang="ar"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
            aria-live="polite"
            aria-atomic="true"
        >
                <div class="dokany-stat-card rounded-xl border border-sidebar-border/70 bg-card px-5 py-4 shadow-sm dark:border-sidebar-border">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium text-muted-foreground">زوار اليوم</span>
                        <Users class="size-5 shrink-0 text-primary" stroke-width="1.75" />
                    </div>
                    <p class="mt-3 text-2xl font-bold tracking-tight tabular-nums text-foreground" dir="ltr" lang="en">
                        {{ formatEnInteger(dashboardStats.visitors_today) }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">زيارات فريدة (حسب Session) خلال آخر 24 ساعة</p>
                </div>

                <div class="dokany-stat-card rounded-xl border border-sidebar-border/70 bg-card px-5 py-4 shadow-sm dark:border-sidebar-border">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium text-muted-foreground">إجمالي الزوار</span>
                        <Globe class="size-5 shrink-0 text-primary" stroke-width="1.75" />
                    </div>
                    <p class="mt-3 text-2xl font-bold tracking-tight tabular-nums text-foreground" dir="ltr" lang="en">
                        {{ formatEnInteger(dashboardStats.visitors_total) }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">زيارات فريدة منذ بداية التتبع</p>
                </div>

                <div class="dokany-stat-card rounded-xl border border-sidebar-border/70 bg-card px-5 py-4 shadow-sm dark:border-sidebar-border">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium text-muted-foreground">أعلى الدول (30 يوم)</span>
                        <Globe class="size-5 shrink-0 text-primary" stroke-width="1.75" />
                    </div>
                    <div class="mt-3 space-y-2 text-sm">
                        <div
                            v-for="row in dashboardStats.top_countries_30d"
                            :key="row.country"
                            class="flex items-center justify-between gap-3"
                        >
                            <span class="truncate text-foreground">{{ row.country }}</span>
                            <span class="shrink-0 font-semibold tabular-nums text-foreground" dir="ltr" lang="en">
                                {{ formatEnInteger(row.visitors) }}
                            </span>
                        </div>
                        <p v-if="dashboardStats.top_countries_30d.length === 0" class="text-xs text-muted-foreground">
                            لا توجد بيانات بعد.
                        </p>
                    </div>
                </div>

            <div
                class="rounded-xl border border-sidebar-border/70 bg-card px-5 py-4 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-muted-foreground">إجمالي الإيرادات</span>
                    <Wallet class="size-5 shrink-0 text-[#C8A97E]" stroke-width="1.75" />
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight tabular-nums text-foreground" dir="ltr" lang="en">
                    {{ formatRevenue(dashboardStats.total_revenue, dashboardStats.currency_en) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">من اشتراكات التجار النشطين (مبالغ مسجّلة + التحويلات المؤكدة)</p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 bg-card px-5 py-4 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-muted-foreground">عدد التجار</span>
                    <Store class="size-5 shrink-0 text-[#C8A97E]" stroke-width="1.75" />
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight tabular-nums text-foreground" dir="ltr" lang="en">
                    {{ formatEnInteger(dashboardStats.active_merchants_count) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">تجار نشطون حالياً</p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 bg-card px-5 py-4 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-muted-foreground">طلبات قيد المراجعة</span>
                    <ClipboardList class="size-5 shrink-0 text-[#C8A97E]" stroke-width="1.75" />
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight tabular-nums text-foreground" dir="ltr" lang="en">
                    {{ formatEnInteger(dashboardStats.pending_requests_count) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">في صفحة Requests للإدارة</p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 bg-card px-5 py-4 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-muted-foreground">إجمالي المنتجات</span>
                    <Package class="size-5 shrink-0 text-[#C8A97E]" stroke-width="1.75" />
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight tabular-nums text-foreground" dir="ltr" lang="en">
                    {{ formatEnInteger(dashboardStats.total_products_all_sellers) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    كل المنتجات المضافة من التجار المسجّلين في المنصة
                </p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 bg-card px-5 py-4 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-muted-foreground">إجمالي طلبات المتاجر</span>
                    <ShoppingCart class="size-5 shrink-0 text-[#C8A97E]" stroke-width="1.75" />
                </div>
                <p class="mt-3 text-2xl font-bold tracking-tight tabular-nums text-foreground" dir="ltr" lang="en">
                    {{ formatEnInteger(dashboardStats.total_storefront_orders) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    طلبات العملاء المسجّلة لجميع التجار (كل الحالات)
                </p>
            </div>
        </div>

        <div
            v-else-if="isSeller && sellerDashboardStats"
            dir="rtl"
            lang="ar"
            class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-xl bg-gray-50 dark:bg-background"
        >
            <header
                class="sticky top-0 z-10 border-b border-gray-200 bg-white px-4 py-3 dark:border-border dark:bg-card"
            >
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 flex-1 items-center gap-3">
                        <button
                            type="button"
                            class="rounded-lg p-2 text-gray-700 hover:bg-gray-100 dark:text-foreground dark:hover:bg-muted"
                            aria-label="القائمة"
                            @click="toggleSidebar"
                        >
                            <Menu class="size-5" stroke-width="2" />
                        </button>
                        <div class="relative hidden min-w-0 flex-1 sm:block sm:max-w-md">
                            <Search
                                class="pointer-events-none absolute right-3 top-1/2 size-5 -translate-y-1/2 text-gray-400"
                                stroke-width="2"
                                aria-hidden="true"
                            />
                            <input
                                v-model="orderSearch"
                                type="search"
                                placeholder="بحث في الطلبات…"
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-3 pr-10 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-border dark:bg-muted/40 dark:text-foreground"
                                @keydown.enter.prevent="goOrdersSearch"
                            />
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link
                            v-if="sellerDashboardStats.storefront_url"
                            :href="sellerDashboardStats.storefront_url"
                            class="rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/90"
                        >
                            التوجه للمتجر
                        </Link>
                        <Link
                            href="/merchant/orders"
                            class="relative rounded-lg p-2 text-gray-700 hover:bg-gray-100 dark:text-foreground dark:hover:bg-muted"
                            aria-label="الطلبات"
                        >
                            <Bell class="size-5" stroke-width="2" />
                            <span
                                v-if="sellerDashboardStats.new_orders_count > 0"
                                class="absolute right-1 top-1 size-2 rounded-full bg-red-500"
                                aria-hidden="true"
                            />
                        </Link>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-auto p-4 md:p-6">
                <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div
                        class="rounded-xl border border-gray-200 bg-orange-50 p-6 shadow-sm transition-shadow hover:shadow-md dark:border-border dark:bg-orange-950/20"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm text-gray-600 dark:text-muted-foreground">طلبات اليوم</h3>
                            <div class="rounded-lg bg-white p-2 text-orange-500 shadow-sm dark:bg-card">
                                <Activity class="size-5" stroke-width="2" />
                            </div>
                    </div>
                        <p class="text-2xl font-bold tabular-nums text-gray-900 dark:text-foreground" dir="ltr">
                            {{ formatEnInteger(sellerDashboardStats.orders_today_count) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-muted-foreground">
                            طلبات جديدة مسجّلة اليوم
                        </p>
                    </div>
                    <div
                        class="rounded-xl border border-gray-200 bg-blue-50 p-6 shadow-sm transition-shadow hover:shadow-md dark:border-border dark:bg-blue-950/20"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm text-gray-600 dark:text-muted-foreground">إجمالي الزيارات</h3>
                            <div class="rounded-lg bg-white p-2 text-blue-500 shadow-sm dark:bg-card">
                                <TrendingUp class="size-5" stroke-width="2" />
                            </div>
                    </div>
                        <p class="text-2xl font-bold tabular-nums text-gray-900 dark:text-foreground" dir="ltr">
                            {{ formatEnInteger(sellerDashboardStats.storefront_visits_count) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-muted-foreground">
                            وفق تتبّع الجلسات للرابط العام
                        </p>
                    </div>
                    <div
                        class="rounded-xl border border-gray-200 bg-green-50 p-6 shadow-sm transition-shadow hover:shadow-md dark:border-border dark:bg-green-950/20"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm text-gray-600 dark:text-muted-foreground">إجمالي الطلبات</h3>
                            <div class="rounded-lg bg-white p-2 text-green-600 shadow-sm dark:bg-card">
                                <ShoppingCart class="size-5" stroke-width="2" />
                            </div>
                    </div>
                        <p class="text-2xl font-bold tabular-nums text-gray-900 dark:text-foreground" dir="ltr">
                            {{ formatEnInteger(sellerDashboardStats.orders_total_count) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-muted-foreground">
                            كل الحالات — {{ formatEnInteger(sellerDashboardStats.products_count) }} منتج في المتجر
                        </p>
                    </div>
                    <div
                        class="rounded-xl border border-gray-200 bg-purple-50 p-6 shadow-sm transition-shadow hover:shadow-md dark:border-border dark:bg-purple-950/20"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm text-gray-600 dark:text-muted-foreground">تحتاج إجراء</h3>
                            <div class="rounded-lg bg-white p-2 text-purple-600 shadow-sm dark:bg-card">
                                <FileText class="size-5" stroke-width="2" />
                            </div>
                    </div>
                        <p class="text-2xl font-bold tabular-nums text-gray-900 dark:text-foreground" dir="ltr">
                            {{ formatEnInteger(sellerDashboardStats.new_orders_count) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-muted-foreground">في انتظارك</p>
                    </div>
                </div>

                <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-border dark:bg-card">
                        <div class="mb-4 flex items-center justify-between gap-2">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-foreground">توزيع الطلبات</h2>
                            <Link
                                href="/merchant/orders"
                                class="flex items-center gap-1 text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                            >
                                <Calendar class="size-4" stroke-width="2" />
                                تفاصيل
                            </Link>
                        </div>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-muted-foreground">من التاريخ</span>
                                <span class="font-medium text-gray-900 dark:text-foreground">{{
                                    formatShortDate(sellerDashboardStats.orders_first_at)
                                }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-muted-foreground">إلى التاريخ</span>
                                <span class="font-medium text-gray-900 dark:text-foreground">{{
                                    formatShortDate(sellerDashboardStats.orders_last_at)
                                }}</span>
                            </div>
                            <div class="my-4 h-px bg-gray-200 dark:bg-border" />
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-muted-foreground">عدد الطلبات</span>
                                <span class="text-2xl font-bold text-indigo-600 tabular-nums dark:text-indigo-400" dir="ltr">{{
                                    formatEnInteger(sellerDashboardStats.orders_total_count)
                                }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-muted-foreground">إجمالي المستلم</span>
                                <span class="font-semibold tabular-nums text-gray-900 dark:text-foreground" dir="ltr">{{
                                    formatRevenue(
                                        sellerDashboardStats.total_revenue,
                                        sellerDashboardStats.currency_en,
                                    )
                                }}</span>
                            </div>
                            <div class="flex flex-wrap gap-4 text-xs text-gray-500 dark:text-muted-foreground">
                                <span>الفواتير: <span class="font-semibold tabular-nums text-gray-800 dark:text-foreground" dir="ltr">{{ formatEnInteger(sellerDashboardStats.invoices_count) }}</span></span>
                                <span>مؤكدة: <span class="font-semibold tabular-nums text-gray-800 dark:text-foreground" dir="ltr">{{ formatEnInteger(sellerDashboardStats.confirmed_orders_count) }}</span></span>
                            </div>
                            <Link
                                href="/merchant/orders"
                                class="mt-4 flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                            >
                                عرض الطلبات
                            </Link>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-border dark:bg-card">
                        <h2 class="mb-1 text-lg font-bold text-gray-900 dark:text-foreground">حالة الطلبات</h2>
                        <p class="mb-6 text-sm text-gray-500 dark:text-muted-foreground">
                            توزيع فعلي حسب حالة كل طلب في متجرك
                        </p>
                        <div class="flex h-48 items-center justify-center">
                            <div
                                class="size-40 rounded-full shadow-inner ring-1 ring-black/5 dark:ring-white/10"
                                :style="orderStatusPieStyle"
                                role="img"
                                aria-label="توزيع حالات الطلب"
                            />
                        </div>
                        <ul class="mt-4 flex flex-wrap justify-center gap-x-4 gap-y-2">
                            <li
                                v-for="row in sellerDashboardStats.order_status_breakdown"
                                :key="row.status"
                                class="flex items-center gap-2 text-sm text-gray-600 dark:text-muted-foreground"
                            >
                                <span
                                    class="size-3 shrink-0 rounded-full"
                                    :style="{ backgroundColor: row.color }"
                                    aria-hidden="true"
                                />
                                <span>{{ row.label }}:</span>
                                <span class="font-semibold tabular-nums text-gray-900 dark:text-foreground" dir="ltr">{{
                                    formatEnInteger(row.count)
                                }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-border dark:bg-card">
                    <h2 class="mb-2 text-lg font-bold text-gray-900 dark:text-foreground">نقاط على الطريق</h2>
                    <p class="mb-4 text-sm text-gray-500 dark:text-muted-foreground">
                        أرقام حسب مرحلة الطلب — بيانات مباشرة من آخر تحديث
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <div
                            v-for="row in sellerDashboardStats.order_status_breakdown"
                            :key="`road-${row.status}`"
                            class="min-w-[8.5rem] flex-1 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3 text-center dark:border-border dark:bg-muted/40"
                        >
                            <p class="text-xs text-gray-500 dark:text-muted-foreground">{{ row.label }}</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-gray-900 dark:text-foreground" dir="ltr">
                                {{ formatEnInteger(row.count) }}
                            </p>
                        </div>
                </div>
            </div>

                <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-border dark:bg-card">
                    <h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-foreground">إحصائيات الأداء</h2>
                    <p class="mb-4 text-sm text-gray-500 dark:text-muted-foreground">
                        عدد الطلبات المسجّلة شهرياً (آخر ٦ أشهر)
                    </p>
                    <div class="h-64 w-full overflow-x-auto">
                        <svg
                            class="min-w-[320px] text-indigo-600 dark:text-indigo-400"
                            :viewBox="`0 0 ${performanceLineChart.w} ${performanceLineChart.h}`"
                            role="img"
                            aria-label="منحنى الطلبات الشهرية"
                        >
                            <line
                                x1="36"
                                :y1="performanceLineChart.h - 28"
                                :x2="performanceLineChart.w - 36"
                                :y2="performanceLineChart.h - 28"
                                stroke="currentColor"
                                stroke-opacity="0.15"
                                stroke-width="1"
                            />
                            <polyline
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                :points="performanceLineChart.points"
                            />
                            <g
                                v-for="(d, i) in performanceLineChart.trend"
                                :key="`lbl-${i}`"
                            >
                                <text
                                    :x="36 + (performanceLineChart.trend.length <= 1 ? (performanceLineChart.w - 72) / 2 : (i / (performanceLineChart.trend.length - 1)) * (performanceLineChart.w - 72))"
                                    :y="performanceLineChart.h - 8"
                                    fill="currentColor"
                                    fill-opacity="0.55"
                                    font-size="11"
                                    text-anchor="middle"
                                >
                                    {{ d.label }}
                                </text>
                            </g>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isAdmin" class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            v-if="isAdmin"
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
        <div
            v-else-if="!isSeller"
            class="grid auto-rows-min gap-4 md:grid-cols-3"
        >
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            v-if="!isSeller"
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
