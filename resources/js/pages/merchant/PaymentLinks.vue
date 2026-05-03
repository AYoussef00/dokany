<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Copy, Link2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type LinkRow = {
    uuid: string;
    amount: string;
    currency_label_ar: string;
    created_at: string | null;
    pay_path: string | null;
};

const props = defineProps<{
    storeSlug: string | null;
    pathPrefix: string;
    canPublishLinks: boolean;
    links: LinkRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'الرئيسية', href: dashboard() },
            { title: 'رابط الدفع', href: '/merchant/payment-links' },
        ],
    },
});

const page = usePage<{ flash: { success?: string | null; error?: string | null } }>();
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashError = computed(() => page.props.flash?.error ?? null);

const form = useForm({ amount: '' });

const copiedUuid = ref<string | null>(null);
let copyTimer: ReturnType<typeof setTimeout> | null = null;

onBeforeUnmount(() => {
    if (copyTimer !== null) {
        clearTimeout(copyTimer);
    }
});

function formatMoney(amount: string, currency: string): string {
    const n = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(amount));
    return `${n} ${currency}`;
}

function fullPayUrl(payPath: string | null): string {
    if (!payPath) {
        return '';
    }
    if (typeof globalThis.location === 'undefined') {
        return payPath;
    }
    return `${globalThis.location.origin}${payPath}`;
}

async function copyLink(row: LinkRow): Promise<void> {
    const url = fullPayUrl(row.pay_path);
    if (!url) {
        return;
    }
    try {
        await navigator.clipboard.writeText(url);
        copiedUuid.value = row.uuid;
        if (copyTimer !== null) {
            clearTimeout(copyTimer);
        }
        copyTimer = setTimeout(() => {
            copiedUuid.value = null;
            copyTimer = null;
        }, 2000);
    } catch {
        copiedUuid.value = null;
    }
}

function submitAmount(): void {
    form.transform((d) => ({ amount: String(d.amount).trim() })).post('/merchant/payment-links', {
        preserveScroll: true,
        onSuccess: () => form.reset('amount'),
    });
}
</script>

<template>
    <Head title="رابط الدفع" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        dir="rtl"
        lang="ar"
    >
        <div class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
            <div class="flex flex-wrap items-start gap-3">
                <div
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-muted text-primary"
                >
                    <Link2 class="size-5" stroke-width="2" />
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-lg font-semibold tracking-tight">
                        رابط الدفع عبر InstaPay
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        حدّد المبلغ، ثم انسخ الرابط وأرسله لأي عميل. عند الدفع يظهر الطلب في «الطلبات» مثل
                        طلبات المتجر العادية.
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="flashSuccess"
            class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/30 dark:text-emerald-100"
            role="status"
        >
            {{ flashSuccess }}
        </div>
        <div
            v-if="flashError"
            class="rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 text-sm text-red-900 dark:border-red-500/30 dark:text-red-100"
            role="alert"
        >
            {{ flashError }}
        </div>

        <div
            v-if="!canPublishLinks"
            class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-950"
        >
            عيّن «رابط المتجر» (slug) من
            <a href="/merchant/store-settings" class="font-semibold underline underline-offset-2">الإعدادات</a>
            ليصبح رابط الدفع متاحاً.
        </div>

        <div
            v-else
            class="rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border"
        >
            <h2 class="text-sm font-semibold text-foreground">
                توليد رابط جديد
            </h2>
            <form class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end" @submit.prevent="submitAmount">
                <div class="min-w-0 flex-1 space-y-2">
                    <Label for="paylink-amount">المبلغ</Label>
                    <Input
                        id="paylink-amount"
                        v-model="form.amount"
                        type="text"
                        inputmode="decimal"
                        dir="ltr"
                        class="text-end tabular-nums"
                        placeholder="مثال: 350"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.amount" />
                </div>
                <Button
                    type="submit"
                    class="shrink-0 rounded-xl sm:h-9"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'جاري الإنشاء…' : 'توليد رابط دفع' }}
                </Button>
            </form>
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
            <div class="border-b border-border px-6 py-4">
                <h2 class="text-sm font-semibold">
                    الروابط الأخيرة
                </h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    نفس الرابط يمكن إرساله لعدة عملاء؛ كل عميل يُنشئ طلباً منفصلاً عند إدخال بياناته.
                </p>
            </div>
            <div v-if="links.length === 0" class="px-6 py-12 text-center text-sm text-muted-foreground">
                لم يُنشأ أي رابط بعد.
            </div>
            <ul v-else class="divide-y divide-border">
                <li
                    v-for="row in links"
                    :key="row.uuid"
                    class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0 text-start">
                        <p class="font-semibold tabular-nums text-foreground" dir="ltr" lang="en">
                            {{ formatMoney(row.amount, row.currency_label_ar) }}
                        </p>
                        <p class="mt-1 truncate text-xs text-muted-foreground dir-ltr" dir="ltr" :title="fullPayUrl(row.pay_path)">
                            {{ row.pay_path ? fullPayUrl(row.pay_path) : '—' }}
                        </p>
                    </div>
                    <Button
                        v-if="row.pay_path"
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-9 shrink-0 gap-1.5"
                        @click="copyLink(row)"
                    >
                        <Copy class="size-3.5" stroke-width="2" />
                        {{ copiedUuid === row.uuid ? 'تم النسخ' : 'نسخ الرابط' }}
                    </Button>
                </li>
            </ul>
        </div>
    </div>
</template>
