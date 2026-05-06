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

const isSeller = computed(() => page.props.auth.user?.role === 'seller');
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
const isRtlDashboard = computed(() => isSeller.value || isAdmin.value);
const sidebarSide = computed<'left' | 'right'>(() => (isRtlDashboard.value ? 'right' : 'left'));
</script>

<template>
    <AppShell
        variant="sidebar"
        :provider-class="isSeller ? 'merchant-pro-shell' : undefined"
        :dir="isRtlDashboard ? 'rtl' : undefined"
        :lang="isRtlDashboard ? 'ar' : undefined"
    >
        <AppSidebar />
        <AppContent
            variant="sidebar"
            class="overflow-x-hidden"
            :class="isSeller ? 'merchant-pro-inset' : undefined"
            :inset-peer-side="sidebarSide"
        >
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
