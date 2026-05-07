<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import type { JourneyRow } from './types';

const props = defineProps<{
    journeys: JourneyRow[];
    formatDuration: (seconds: number) => string;
}>();

const openSession = ref<string | null>(null);
const showAll = ref(false);

const visibleJourneys = computed(() => (showAll.value ? props.journeys : props.journeys.slice(0, 6)));
</script>

<template>
    <div class="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-foreground">رحلات المستخدمين (آخر 7 أيام)</h2>
                <p class="mt-1 text-xs text-muted-foreground">كل Session: ترتيب الصفحات + الوقت في كل صفحة.</p>
            </div>
        </div>

        <div v-if="journeys.length === 0" class="mt-6 rounded-xl border border-dashed border-sidebar-border/70 bg-background/30 p-6 text-center">
            <p class="text-sm font-semibold text-muted-foreground">لا توجد بيانات بعد.</p>
        </div>

        <div v-else class="mt-4 space-y-3">
            <div
                v-for="j in visibleJourneys"
                :key="j.session"
                class="overflow-hidden rounded-xl border border-sidebar-border/60 bg-background/40"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-4 px-4 py-3 text-right hover:bg-background/60"
                    @click="openSession = openSession === j.session ? null : j.session"
                >
                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        <span class="text-xs text-muted-foreground" dir="ltr" lang="en">
                            session:
                            <span class="font-mono font-semibold text-foreground">{{ j.session.slice(0, 10) }}</span>
                        </span>
                        <span v-if="j.user_id" class="text-xs text-muted-foreground" dir="ltr" lang="en">
                            user:
                            <span class="font-semibold text-foreground">#{{ j.user_id }}</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-muted-foreground">
                            الإجمالي:
                            <span class="font-semibold text-foreground">{{ formatDuration(j.total_seconds) }}</span>
                        </span>
                        <ChevronDown class="size-4 shrink-0 text-muted-foreground transition-transform" :class="{ 'rotate-180': openSession === j.session }" />
                    </div>
                </button>

                <div v-if="openSession === j.session" class="border-t border-sidebar-border/60 bg-background/60 px-4 py-3">
                    <div class="grid gap-2">
                        <div
                            v-for="(p, idx) in j.pages"
                            :key="`${j.session}:${idx}:${p.path}`"
                            class="flex items-center justify-between gap-3 rounded-lg bg-background/70 px-3 py-2"
                        >
                            <p class="min-w-0 truncate text-xs font-medium text-foreground" dir="ltr" lang="en">
                                {{ idx + 1 }}. {{ p.route }}
                            </p>
                            <div class="shrink-0 text-xs text-muted-foreground" dir="ltr" lang="en">
                                {{ formatDuration(p.seconds) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="journeys.length > 6" class="pt-2 text-center">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-sidebar-border/70 bg-background px-4 py-2 text-sm font-semibold text-foreground hover:bg-background/70"
                    @click="showAll = !showAll"
                >
                    {{ showAll ? 'إظهار أقل' : 'إظهار المزيد' }}
                </button>
            </div>
        </div>
    </div>
</template>

