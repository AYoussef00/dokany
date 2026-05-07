<script setup lang="ts">
import { Activity, ClipboardList, Globe, Package, ShoppingCart, Store, Users, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';

import AdminKpiCard from './AdminKpiCard.vue';
import CountriesDonutChart from './CountriesDonutChart.vue';
import JourneysList from './JourneysList.vue';
import type { DashboardStats } from './serverTypes';
import TopPagesBarChart from './TopPagesBarChart.vue';


const props = defineProps<{
    stats: DashboardStats;
    formatEnInteger: (value: number) => string;
    formatRevenue: (amount: number, currency: string) => string;
    formatDuration: (seconds: number) => string;
}>();

const kpis = computed(() => [
    {
        key: 'visitors_today',
        title: 'زوار اليوم',
        value: props.formatEnInteger(props.stats.visitors_today),
        description: 'زيارات فريدة (حسب Session) خلال آخر 24 ساعة',
        tone: 'primary',
        icon: Users,
    },
    {
        key: 'visitors_total',
        title: 'إجمالي الزوار',
        value: props.formatEnInteger(props.stats.visitors_total),
        description: 'زيارات فريدة منذ بداية التتبع',
        tone: 'primary',
        icon: Globe,
    },
    {
        key: 'total_revenue',
        title: 'إجمالي الإيرادات',
        value: props.formatRevenue(props.stats.total_revenue, props.stats.currency_en),
        description: 'من اشتراكات التجار النشطين (مبالغ مسجّلة + التحويلات المؤكدة)',
        tone: 'gold',
        icon: Wallet,
    },
    {
        key: 'active_merchants_count',
        title: 'عدد التجار النشطين',
        value: props.formatEnInteger(props.stats.active_merchants_count),
        description: 'تجار نشطون حالياً',
        tone: 'gold',
        icon: Store,
    },
    {
        key: 'pending_requests_count',
        title: 'طلبات قيد المراجعة',
        value: props.formatEnInteger(props.stats.pending_requests_count),
        description: 'في صفحة Requests للإدارة',
        tone: 'gold',
        icon: ClipboardList,
    },
    {
        key: 'total_products_all_sellers',
        title: 'إجمالي المنتجات',
        value: props.formatEnInteger(props.stats.total_products_all_sellers),
        description: 'كل المنتجات المضافة من التجار المسجّلين في المنصة',
        tone: 'slate',
        icon: Package,
    },
    {
        key: 'total_storefront_orders',
        title: 'إجمالي طلبات المتاجر',
        value: props.formatEnInteger(props.stats.total_storefront_orders),
        description: 'طلبات العملاء المسجّلة لجميع التجار (كل الحالات)',
        tone: 'slate',
        icon: ShoppingCart,
    },
]);
</script>

<template>
    <div dir="rtl" lang="ar" class="w-full space-y-6 px-4 py-4 md:px-6 md:py-6">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div class="flex flex-col gap-1">
                <h1 class="text-lg font-bold tracking-tight text-foreground md:text-xl">لوحة التحكم</h1>
                <p class="text-sm text-muted-foreground">نظرة منظمة على أداء المنصة خلال آخر 30 يوم.</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <Activity class="size-4 text-primary" stroke-width="2" />
                <span>يتم تحديث البيانات تلقائياً</span>
            </div>
        </div>

        <div class="grid auto-rows-fr gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
            <AdminKpiCard
                v-for="k in kpis"
                :key="k.key"
                :title="k.title"
                :value="k.value"
                :description="k.description"
                :tone="k.tone as any"
                :icon="k.icon"
            />
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <TopPagesBarChart :rows="stats.top_pages_30d" :format-duration="formatDuration" />
            <CountriesDonutChart :rows="stats.top_countries_30d" />
        </div>

        <JourneysList :journeys="stats.recent_journeys" :format-duration="formatDuration" />
    </div>
</template>

