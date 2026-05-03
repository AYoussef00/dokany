<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ClipboardList,
    Clock,
    Eye,
    Package,
    ShoppingCart,
    Store,
    Wallet,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted } from 'vue';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { dashboard } from '@/routes';

const ADMIN_STATS_POLL_MS = 15_000;
const SEVEN_DAYS_MS = 7 * 24 * 60 * 60 * 1000;
const RENEWAL_ALERT_SESSION_PREFIX = 'dokany_seller_renewal_swal';

type DashboardStats = {
    total_revenue: number;
    currency_en: string;
    active_merchants_count: number;
    pending_requests_count: number;
};

type SellerDashboardStats = {
    total_revenue: number;
    currency_en: string;
    products_count: number;
    confirmed_orders_count: number;
    new_orders_count: number;
    invoices_count: number;
    storefront_visits_count: number;
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

const sellerDashboardStats = computed(() => page.props.sellerDashboardStats);

const sellerOrdersDenominator = computed(() => {
    const s = sellerDashboardStats.value;
    if (!s) {
        return 1;
    }
    return Math.max(1, s.confirmed_orders_count + s.new_orders_count);
});

const ordersPieStyle = computed(() => {
    const s = sellerDashboardStats.value;
    if (!s) {
        return {};
    }
    const t = sellerOrdersDenominator.value;
    const p = (s.confirmed_orders_count / t) * 100;
    return {
        background: `conic-gradient(hsl(198 62% 40%) 0% ${p}%, hsl(198 45% 68%) ${p}% 100%)`,
    };
});

const confirmedOrdersBarPct = computed(() => {
    const s = sellerDashboardStats.value;
    if (!s) {
        return 0;
    }
    const t = sellerOrdersDenominator.value;
    return Math.round((s.confirmed_orders_count / t) * 100);
});

const newOrdersBarPct = computed(() => Math.max(0, 100 - confirmedOrdersBarPct.value));

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
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        :dir="isSeller ? 'rtl' : undefined"
        :lang="isSeller ? 'ar' : undefined"
    >
        <div
            v-if="isAdmin && dashboardStats"
            dir="rtl"
            lang="ar"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
            aria-live="polite"
            aria-atomic="true"
        >
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
            class="flex flex-1 flex-col gap-5 p-4 md:gap-6 md:p-6"
        >
            <!-- صف المؤشرات الأساسية -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    class="flex items-center gap-4 rounded-2xl border border-white/60 bg-white p-5 shadow-[0_8px_30px_rgb(17_17_17_/_0.06)] dark:border-border dark:bg-card"
                >
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-700 dark:text-emerald-400"
                    >
                        <Package class="size-6" stroke-width="1.75" />
                    </div>
                    <div class="min-w-0 flex-1 text-right">
                        <p class="text-sm font-medium text-muted-foreground">المنتجات</p>
                        <p
                            class="mt-1 text-2xl font-bold tracking-tight tabular-nums text-foreground"
                            dir="ltr"
                            lang="en"
                        >
                            {{ formatEnInteger(sellerDashboardStats.products_count) }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">في المتجر</p>
                    </div>
                </div>
                <div
                    class="flex items-center gap-4 rounded-2xl border border-white/60 bg-white p-5 shadow-[0_8px_30px_rgb(17_17_17_/_0.06)] dark:border-border dark:bg-card"
                >
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-sky-500/15 text-sky-700 dark:text-sky-400"
                    >
                        <ShoppingCart class="size-6" stroke-width="1.75" />
                    </div>
                    <div class="min-w-0 flex-1 text-right">
                        <p class="text-sm font-medium text-muted-foreground">إجمالي الطلبات</p>
                        <p
                            class="mt-1 text-2xl font-bold tracking-tight tabular-nums text-foreground"
                            dir="ltr"
                            lang="en"
                        >
                            {{
                                formatEnInteger(
                                    sellerDashboardStats.confirmed_orders_count
                                        + sellerDashboardStats.new_orders_count,
                                )
                            }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">مؤكدة + قيد الانتظار</p>
                    </div>
                </div>
                <div
                    class="flex items-center gap-4 rounded-2xl border border-white/60 bg-white p-5 shadow-[0_8px_30px_rgb(17_17_17_/_0.06)] dark:border-border dark:bg-card"
                >
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-teal-600/15 text-teal-800 dark:text-teal-400"
                    >
                        <Wallet class="size-6" stroke-width="1.75" />
                    </div>
                    <div class="min-w-0 flex-1 text-right">
                        <p class="text-sm font-medium text-muted-foreground">إجمالي الإيرادات</p>
                        <p
                            class="mt-1 text-2xl font-bold leading-snug tracking-tight tabular-nums text-foreground"
                            dir="ltr"
                            lang="en"
                        >
                            {{ formatRevenue(sellerDashboardStats.total_revenue, sellerDashboardStats.currency_en) }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">من الطلبات المؤكدة</p>
                    </div>
                </div>
                <div
                    class="flex items-center gap-4 rounded-2xl border bg-white p-5 shadow-[0_8px_30px_rgb(17_17_17_/_0.06)] dark:bg-card"
                    :class="
                        sellerDashboardStats.new_orders_count > 0
                            ? 'border-amber-400/70 ring-1 ring-amber-400/30'
                            : 'border-white/60 dark:border-border'
                    "
                >
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-800 dark:text-amber-400"
                    >
                        <Clock class="size-6" stroke-width="1.75" />
                    </div>
                    <div class="min-w-0 flex-1 text-right">
                        <p class="text-sm font-medium text-muted-foreground">تحتاج إجراء</p>
                        <p
                            class="mt-1 text-2xl font-bold tracking-tight tabular-nums text-foreground"
                            dir="ltr"
                            lang="en"
                        >
                            {{ formatEnInteger(sellerDashboardStats.new_orders_count) }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">طلبات بانتظارك</p>
                    </div>
                </div>
            </div>

            <!-- تحليلات مصغّرة -->
            <div class="grid gap-4 lg:grid-cols-3">
                <div
                    class="flex items-center gap-4 rounded-2xl border border-white/60 bg-white p-5 shadow-[0_8px_30px_rgb(17_17_17_/_0.06)] dark:border-border dark:bg-card"
                >
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-violet-500/15 text-violet-700 dark:text-violet-400"
                    >
                        <Eye class="size-6" stroke-width="1.75" />
                    </div>
                    <div class="min-w-0 flex-1 text-right">
                        <p class="text-sm font-semibold text-foreground">زيارات المتجر</p>
                        <p
                            class="mt-1 text-3xl font-bold tracking-tight tabular-nums text-foreground"
                            dir="ltr"
                            lang="en"
                        >
                            {{ formatEnInteger(sellerDashboardStats.storefront_visits_count) }}
                        </p>
                        <p class="mt-2 text-xs leading-relaxed text-muted-foreground">
                            تقدير حسب الجلسة — مؤشر ظهور الرابط العام
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-white/60 bg-white p-5 shadow-[0_8px_30px_rgb(17_17_17_/_0.06)] dark:border-border dark:bg-card"
                >
                    <p class="text-sm font-semibold text-foreground">حالة الطلبات</p>
                    <p class="mt-1 text-xs text-muted-foreground">نسبة مؤكدة مقابل بانتظار الإجراء</p>
                    <div class="mt-4 flex items-center gap-5">
                        <div
                            class="relative size-28 shrink-0 rounded-full shadow-inner"
                            :style="ordersPieStyle"
                            role="img"
                            :aria-label="
                                `مؤكدة ${confirmedOrdersBarPct}٪، جديدة ${newOrdersBarPct}٪`
                            "
                        />
                        <ul class="min-w-0 flex-1 space-y-2 text-sm">
                            <li class="flex items-center gap-2">
                                <span
                                    class="size-2.5 shrink-0 rounded-full bg-[hsl(198_62%_40%)]"
                                    aria-hidden="true"
                                />
                                <span class="text-muted-foreground">مؤكدة</span>
                                <span class="ms-auto font-semibold tabular-nums" dir="ltr">{{
                                    formatEnInteger(sellerDashboardStats.confirmed_orders_count)
                                }}</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span
                                    class="size-2.5 shrink-0 rounded-full bg-[hsl(198_45%_68%)]"
                                    aria-hidden="true"
                                />
                                <span class="text-muted-foreground">قيد الانتظار</span>
                                <span class="ms-auto font-semibold tabular-nums" dir="ltr">{{
                                    formatEnInteger(sellerDashboardStats.new_orders_count)
                                }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-white/60 bg-white p-5 shadow-[0_8px_30px_rgb(17_17_17_/_0.06)] dark:border-border dark:bg-card"
                >
                        <p class="text-sm font-semibold text-foreground">توزيع الطلبات</p>
                    <p class="mt-1 text-xs text-muted-foreground">شريط نسبي من إجمالي الطلبات</p>
                    <div class="mt-5 space-y-4">
                        <div>
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="text-muted-foreground">مؤكدة</span>
                                <span class="tabular-nums font-medium" dir="ltr"
                                    >{{ confirmedOrdersBarPct }}٪</span
                                >
                            </div>
                            <div
                                class="h-2.5 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full bg-[hsl(198_62%_42%)] transition-all"
                                    :style="{ width: `${confirmedOrdersBarPct}%` }"
                                />
                            </div>
                        </div>
                        <div>
                            <div class="mb-1 flex justify-between text-xs">
                                <span class="text-muted-foreground">قيد الانتظار</span>
                                <span class="tabular-nums font-medium" dir="ltr"
                                    >{{ newOrdersBarPct }}٪</span
                                >
                            </div>
                            <div
                                class="h-2.5 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full bg-[hsl(198_45%_58%)] transition-all"
                                    :style="{ width: `${newOrdersBarPct}%` }"
                                />
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-muted-foreground">
                        الفواتير:
                        <span class="font-semibold tabular-nums text-foreground" dir="ltr">{{
                            formatEnInteger(sellerDashboardStats.invoices_count)
                        }}</span>
                    </p>
                </div>
            </div>

            <!-- صف تحليلي日后ي -->
            <div
                class="rounded-2xl border border-white/60 bg-gradient-to-br from-white to-[hsl(198_40%_96%)] p-6 shadow-[0_8px_30px_rgb(17_17_17_/_0.06)] dark:border-border dark:from-card dark:to-card"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-foreground">نظرة على الأداء</p>
                        <p class="mt-1 max-w-xl text-sm text-muted-foreground">
                            تفاصيل زمنية وتقارير مفصّلة ستتوفر لاحقاً؛ حالياً ركّز على الطلبات الجديدة وتحديث
                            المنتجات.
                        </p>
                    </div>
                    <div
                        class="hidden h-24 w-40 shrink-0 rounded-xl border border-[hsl(198_45%_85%)] bg-white/80 sm:block dark:border-border dark:bg-card"
                    >
                        <svg
                            class="size-full text-[hsl(198_55%_45%)]"
                            viewBox="0 0 120 60"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 48 L24 32 L44 38 L64 18 L84 28 L104 12 L116 8"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                opacity="0.85"
                            />
                            <path
                                d="M4 48 L24 36 L44 40 L64 26 L84 34 L104 22 L116 20"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                stroke-dasharray="4 3"
                                opacity="0.45"
                            />
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
