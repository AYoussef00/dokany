<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        storeName: string;
        logoUrl: string | null;
        /** مثل: إتمام الطلب أو الدفع — يُضاف قبل اسم المتجر */
        titleSuffix?: string | null;
    }>(),
    { titleSuffix: null },
);

const page = usePage<{ name: string }>();

const documentTitle = computed(() => {
    const name = props.storeName.trim() || 'متجر';
    const app = typeof page.props.name === 'string' ? page.props.name.trim() : '';
    const suffix = props.titleSuffix?.trim();
    if (suffix) {
        if (app !== '') {
            return `${suffix} — ${name} · ${app}`;
        }

        return `${suffix} — ${name}`;
    }
    if (app !== '') {
        return `${name} · ${app}`;
    }

    return name;
});

const metaDescription = computed(() => `متجر ${props.storeName.trim() || 'إلكتروني'}`);

const faviconHref = computed(() => {
    const logo = props.logoUrl?.trim();
    if (logo) {
        return logo;
    }

    return '/favicon.ico';
});
</script>

<template>
    <Head :title="documentTitle">
        <meta head-key="storefront-description" name="description" :content="metaDescription" />
        <link head-key="storefront-icon" rel="icon" :href="faviconHref" sizes="any" />
        <link head-key="storefront-apple-touch" rel="apple-touch-icon" :href="faviconHref" />
    </Head>
</template>
