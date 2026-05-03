<script setup lang="ts">
import { computed } from 'vue';
import { SidebarInset } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
    class?: string;
    /** سايدبار على اليمين (لوحة التاجر العربية) — يعكس هوامش الـ inset */
    insetPeerSide?: 'left' | 'right';
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
    insetPeerSide: 'left',
});

const className = computed(() => props.class);

const insetPeerClasses = computed(() => {
    if (props.variant !== 'sidebar') {
        return '';
    }
    if (props.insetPeerSide === 'right') {
        return 'md:peer-data-[variant=inset]:m-2 md:peer-data-[variant=inset]:mr-0 md:peer-data-[variant=inset]:rounded-xl md:peer-data-[variant=inset]:shadow-sm md:peer-data-[variant=inset]:peer-data-[state=collapsed]:mr-2';
    }
    return 'md:peer-data-[variant=inset]:m-2 md:peer-data-[variant=inset]:ml-0 md:peer-data-[variant=inset]:rounded-xl md:peer-data-[variant=inset]:shadow-sm md:peer-data-[variant=inset]:peer-data-[state=collapsed]:ml-2';
});
</script>

<template>
    <SidebarInset
        v-if="props.variant === 'sidebar'"
        :class="cn(className, insetPeerClasses)"
    >
        <slot />
    </SidebarInset>
    <main
        v-else
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl"
        :class="className"
    >
        <slot />
    </main>
</template>
