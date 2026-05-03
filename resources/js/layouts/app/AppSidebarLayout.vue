<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<{ auth: { user: { role?: string } | null } }>();

const isMerchantPro = computed(() => page.props.auth.user?.role === 'seller');
</script>

<template>
    <AppShell variant="sidebar" :provider-class="isMerchantPro ? 'merchant-pro-shell' : undefined">
        <AppSidebar />
        <AppContent
            variant="sidebar"
            class="overflow-x-hidden"
            :class="isMerchantPro ? 'merchant-pro-inset' : undefined"
            :inset-peer-side="isMerchantPro ? 'right' : 'left'"
        >
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
