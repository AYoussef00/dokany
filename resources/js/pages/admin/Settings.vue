<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Settings', href: '/admin/settings' },
        ],
    },
});
</script>

<template>
    <Head title="Admin settings" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
            <h1 class="text-lg font-semibold tracking-tight">Admin settings</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Platform configuration (InstaPay wallet, subscription amount, and more).
            </p>
        </div>
        <div
            class="relative min-h-[240px] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
