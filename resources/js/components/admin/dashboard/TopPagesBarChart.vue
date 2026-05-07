<script setup lang="ts">
import { useDark } from '@vueuse/core';
import { BarChart } from 'echarts/charts';
import { GridComponent, TooltipComponent } from 'echarts/components';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { computed } from 'vue';
import VChart from 'vue-echarts';

import type { TopPageRow } from './types';

use([CanvasRenderer, BarChart, GridComponent, TooltipComponent]);

const props = defineProps<{
    rows: TopPageRow[];
    formatDuration: (seconds: number) => string;
}>();

const isDark = useDark();

const option = computed(() => {
    const rows = props.rows.slice(0, 8);
    const cats = rows.map((r) => r.route || r.path || '/');
    const vals = rows.map((r) => r.views ?? 0);
    const muted = isDark.value ? 'rgba(148,163,184,0.92)' : 'rgba(71,85,105,0.92)';
    const axis = isDark.value ? 'rgba(148,163,184,0.18)' : 'rgba(100,116,139,0.22)';

    return {
        grid: { left: 6, right: 16, top: 10, bottom: 6, containLabel: true },
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'shadow' },
            backgroundColor: isDark.value ? 'rgba(2,6,23,0.92)' : 'rgba(255,255,255,0.96)',
            borderColor: axis,
            textStyle: { color: isDark.value ? '#e2e8f0' : '#0f172a', fontSize: 12 },
            formatter: (params: any) => {
                const p = Array.isArray(params) ? params[0] : params;

                const idx = Number(p?.dataIndex ?? 0);

                const row = rows[idx];

                if (!row) {
                    return '';
                }

                const route = row.route || row.path;
                const views = Number(row.views ?? 0);

                return `${route}<br/>${views} views<br/>متوسط: ${props.formatDuration(row.avg_seconds)}`;
            },
        },
        xAxis: {
            type: 'value',
            axisLabel: { fontSize: 11, color: muted },
            splitLine: { lineStyle: { color: axis } },
        },
        yAxis: {
            type: 'category',
            data: cats,
            axisLabel: {
                fontSize: 11,
                color: muted,
                width: 160,
                overflow: 'truncate',
            },
        },
        series: [
            {
                type: 'bar',
                data: vals,
                barWidth: 14,
                itemStyle: { borderRadius: [8, 8, 8, 8] },
            },
        ],
        color: ['#4f46e5'],
    };
});
</script>

<template>
    <div class="h-full rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-foreground">الصفحات الأكثر زيارة (30 يوم)</h2>
                <p class="mt-1 text-xs text-muted-foreground">عرض Views مع متوسط الوقت داخل الصفحة.</p>
            </div>
        </div>

        <div v-if="rows.length === 0" class="mt-6 rounded-xl border border-dashed border-sidebar-border/70 bg-background/30 p-6 text-center">
            <p class="text-sm font-semibold text-muted-foreground">لا توجد بيانات كافية بعد.</p>
        </div>

        <div v-else class="mt-4 h-72 w-full">
            <VChart :option="option" autoresize class="h-full w-full" />
        </div>
    </div>
</template>

