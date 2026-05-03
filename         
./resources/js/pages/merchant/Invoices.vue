<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

type InvoiceRow = {
    uuid: string;
    order_uuid: string | null;
    buyer_name: string;
    buyer_phone: string;
    subtotal: string;
    currency_label_ar: string;
    created_at: string | null;
};

defineProps<{
    invoices: InvoiceRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'الرئيسية', href: dashboard() },
            { title: 'الفواتير', href: '/merchant/invoices' },
        ],
    },
});

function formatMoney(amount: string, currency: string): string {
    const n = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(amount));
    return `${n} ${currency}`;
}

function formatDate(iso: string | null): string {
    if (!iso) {
        return '—';
    }
    try {
        return new Intl.DateTimeFormat('en-GB', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}
</script>

<template>
    <Head title="الفواتير" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        dir="rtl"
        lang="ar"
    >
        <div class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
            <h1 class="text-lg font-semibold tracking-tight">الفواتير</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                فواتير تُنشأ تلقائياً عند قبولك لطلب بعد استلام إيصال الدفع.
            </p>
        </div>

        <p
            v-if="invoices.length === 0"
            class="rounded-xl border border-dashed border-sidebar-border/70 px-6 py-16 text-center text-sm text-muted-foreground dark:border-sidebar-border"
        >
            لا توجد فواتير بعد. عند قبول طلب من صفحة الطلبات ستظهر الفاتورة هنا.
        </p>

        <div
            v-else
            class="rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border"
            dir="ltr"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[44rem] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/40 text-start">
                            <th class="whitespace-nowrap px-3 py-3 font-semibold">التاريخ</th>
                            <th class="whitespace-nowrap px-3 py-3 font-semibold">رقم الفاتورة</th>
                            <th class="whitespace-nowrap px-3 py-3 font-semibold">الطلب</th>
                            <th class="whitespace-nowrap px-3 py-3 font-semibold">العميل</th>
                            <th class="whitespace-nowrap px-3 py-3 font-semibold">الهاتف</th>
                            <th class="whitespace-nowrap px-3 py-3 font-semibold">المبلغ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="inv in invoices"
                            :key="inv.uuid"
                            class="border-b border-border/60 hover:bg-muted/20"
                        >
                            <td class="whitespace-nowrap px-3 py-3 text-muted-foreground" dir="ltr">
                                {{ formatDate(inv.created_at) }}
                            </td>
                            <td class="px-3 py-3 font-mono text-xs" dir="ltr">
                                {{ inv.uuid }}
                            </td>
                            <td class="px-3 py-3 font-mono text-xs text-muted-foreground" dir="ltr">
                                {{ inv.order_uuid ?? '—' }}
                            </td>
                            <td class="px-3 py-3 font-medium" dir="auto">
                                {{ inv.buyer_name }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 tabular-nums" dir="ltr">
                                {{ inv.buyer_phone }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 font-semibold tabular-nums text-[#7d623f]" dir="ltr">
                                {{ formatMoney(inv.subtotal, inv.currency_label_ar) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-center text-sm text-muted-foreground">
            <Link href="/merchant/orders" class="font-medium text-primary underline-offset-4 hover:underline">
                الانتقال إلى الطلبات
            </Link>
        </p>
    </div>
</template>
