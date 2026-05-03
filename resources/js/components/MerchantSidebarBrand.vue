<script setup lang="ts">
import { Store } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    storeName: string;
    logoUrl?: string | null;
}>();

const displayName = computed(() => props.storeName.trim() || 'متجاري');
</script>

<template>
    <span class="flex min-w-0 flex-1 items-center gap-2.5">
        <span
            class="relative flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-sidebar-border/80 bg-sidebar-accent/30 shadow-sm dark:bg-sidebar-accent/20"
        >
            <img
                v-if="logoUrl"
                :src="logoUrl"
                :alt="displayName"
                class="size-full object-cover"
                loading="lazy"
                decoding="async"
            />
            <span
                v-else
                class="flex size-full items-center justify-center bg-[#C8A97E]/15 text-[#9a7349] dark:bg-[#C8A97E]/20 dark:text-[#d4b896]"
                aria-hidden="true"
            >
                <Store class="size-[1.125rem]" stroke-width="1.75" />
            </span>
        </span>
        <span
            class="min-w-0 flex-1 text-start text-sm leading-tight group-data-[collapsible=icon]:hidden"
        >
            <span
                class="block truncate font-semibold tracking-tight text-sidebar-foreground"
                dir="auto"
                >{{ displayName }}</span
            >
        </span>
    </span>
</template>
