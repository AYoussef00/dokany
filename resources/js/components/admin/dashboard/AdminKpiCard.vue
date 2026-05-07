<script setup lang="ts">
import { computed } from 'vue';

import type { AdminKpiTone } from './types';

const props = defineProps<{
    title: string;
    value: string;
    description?: string;
    tone?: AdminKpiTone;
    icon?: any;
}>();

const toneClass = computed(() => {
    switch (props.tone) {
        case 'primary':
            return 'bg-primary/10 text-primary ring-primary/15';
        case 'gold':
            return 'bg-[#C8A97E]/15 text-[#8B6B3D] ring-[#C8A97E]/25 dark:text-[#E7D4B3]';
        case 'slate':
        default:
            return 'bg-slate-500/10 text-slate-700 ring-slate-500/15 dark:text-slate-200';
    }
});
</script>

<template>
    <div class="group relative h-full overflow-hidden rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
        <div class="pointer-events-none absolute -left-10 -top-12 size-32 rounded-full bg-primary/8 blur-2xl" />
        <div class="pointer-events-none absolute -right-12 -bottom-12 size-32 rounded-full bg-primary/6 blur-2xl" />

        <div class="relative flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-muted-foreground">{{ title }}</p>
                <p class="mt-3 text-2xl font-bold tracking-tight tabular-nums text-foreground" dir="ltr" lang="en">
                    {{ value }}
                </p>
                <p v-if="description" class="mt-1 text-xs text-muted-foreground">
                    {{ description }}
                </p>
            </div>

            <div
                v-if="icon"
                class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl ring-1"
                :class="toneClass"
            >
                <component :is="icon" class="size-5" stroke-width="1.75" />
            </div>
        </div>
    </div>
</template>

