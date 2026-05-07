<script setup lang="ts">
import { useDark } from '@vueuse/core';
import { PieChart } from 'echarts/charts';
import { LegendComponent, TitleComponent, TooltipComponent } from 'echarts/components';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { computed } from 'vue';
import VChart from 'vue-echarts';

import type { CountriesRow } from './types';

use([CanvasRenderer, PieChart, LegendComponent, TooltipComponent, TitleComponent]);

const props = defineProps<{
    rows: CountriesRow[];
}>();

const isDark = useDark();

const total = computed(() => props.rows.reduce((a, r) => a + (r.visitors ?? 0), 0));

const option = computed(() => {
    const data = props.rows.map((r) => ({ name: r.country || 'Unknown', value: r.visitors }));
    const muted = isDark.value ? 'rgba(148,163,184,0.92)' : 'rgba(71,85,105,0.92)';
    const axisLine = isDark.value ? 'rgba(148,163,184,0.18)' : 'rgba(100,116,139,0.22)';

    return {
        tooltip: {
            trigger: 'item',
            backgroundColor: isDark.value ? 'rgba(2,6,23,0.92)' : 'rgba(255,255,255,0.96)',
            borderColor: axisLine,
            textStyle: { color: isDark.value ? '#e2e8f0' : '#0f172a', fontSize: 12 },
            formatter: (p: any) => {
                const name = String(p?.name ?? '');

                const value = Number(p?.value ?? 0);

                const percent = Number(p?.percent ?? 0);

                return `${name}<br/>${value} زائر (${percent}%)`;
            },
        },
        legend: {
            orient: 'vertical',
            right: 0,
            top: 'center',
            itemWidth: 10,
            itemHeight: 10,
            textStyle: { fontSize: 12, color: muted },
        },
        series: [
            {
                type: 'pie',
                radius: ['58%', '78%'],
                center: ['35%', '50%'],
                avoidLabelOverlap: true,
                itemStyle: { borderRadius: 10, borderColor: 'transparent', borderWidth: 2 },
                label: { show: false },
                emphasis: { scale: true, scaleSize: 8 },
                data,
            },
        ],
        color: ['#4f46e5', '#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#a855f7', '#64748b', '#14b8a6'],
    };
});
</script>

<template>
    <div class="h-full rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-foreground">توزيع الزوار حسب الدولة (30 يوم)</h2>
                <p class="mt-1 text-xs text-muted-foreground">إجمالي: <span class="font-semibold tabular-nums text-foreground" dir="ltr" lang="en">{{ total }}</span></p>
            </div>
        </div>

        <div v-if="rows.length === 0" class="mt-6 rounded-xl border border-dashed border-sidebar-border/70 bg-background/30 p-6 text-center">
            <p class="text-sm font-semibold text-muted-foreground">لا توجد بيانات بعد.</p>
        </div>

        <div v-else class="mt-4 h-64 w-full">
            <VChart :option="option" autoresize class="h-full w-full" />
        </div>
    </div>
</template>

