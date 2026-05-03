<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
    /** إضافة إلى غلاف SidebarProvider (مثلاً ثيم لوحة التاجر) */
    providerClass?: HTMLAttributes['class'];
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
    providerClass: undefined,
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider v-else :default-open="isOpen" :class="providerClass">
        <slot />
    </SidebarProvider>
</template>
