<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

type PaymentRow = {
    id: number;
    amount: string;
    currency_label_ar: string;
    payment_method: string;
    note: string | null;
    invoice_uuid: string | null;
    order_uuid: string | null;
    created_at: string | null;
};

defineProps<{
    payments: PaymentRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'الرئيسية', href: dashboard() },
            { title: 'المدفوعات', href: '/merchant/payments' },
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

function formatDateParts(iso: string | null): { date: string; time: string } {
    if (!iso) {
        return { date: '—', time: '' };
    }
    try {
        const d = new Date(iso);
        return {
            date: new Intl.DateTimeFormat('en-GB', { dateStyle: 'medium' }).format(d),
            time: new Intl.DateTimeFormat('en-GB', { timeStyle: 'short' }).format(d),
        };
    } catch {
        return { date: iso, time: '' };
    }
}

function formatDateRow(iso: string | null): string {
    const { date, time } = formatDateParts(iso);
    return time ? `${date} · ${time}` : date;
}

function shortUuid(uuid: string, keep: number = 10): string {
    if (uuid.length <= keep) {
        return uuid;
    }
    return `${uuid.slice(0, keep)}…`;
}
</script>

<template>
    <Head title="المدفوعات" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        dir="rtl"
        lang="ar"
    >
        <div class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
            <h1 class="text-lg font-semibold tracking-tight">المدفوعات</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                تُسجَّل مع الفاتورة عند قبول الطلب (بعد التحقق من إيصال InstaPay).
            </p>
        </div>

        <p
            v-if="payments.length === 0"
            class="rounded-xl border border-dashed border-sidebar-border/70 px-6 py-16 text-center text-sm text-muted-foreground dark:border-sidebar-border"
        >
            لا توجد مدفوعات مسجّلة بعد.
        </p>

        <div
            v-else
            class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[52rem] table-fixed border-collapse text-sm" dir="rtl">
                    <colgroup>
                        <col style="width: 15%;" />
                        <col style="width: 13%;" />
                        <col style="width: 12%;" />
                        <col style="width: 15%;" />
                        <col style="width: 15%;" />
                        <col style="width: 30%;" />
                    </colgroup>
                    <thead>
                        <tr class="border-b border-border bg-muted/40" dir="rtl">
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                التاريخ
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                المبلغ
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                الطريقة
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                الفاتورة
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                الطلب
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                ملاحظة
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="pay in payments"
                            :key="pay.id"
                            class="border-b border-border/40 transition-colors hover:bg-muted/30"
                        >
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate tabular-nums text-muted-foreground text-right"
                                    dir="ltr"
                                    :title="formatDateRow(pay.created_at)"
                                >
                                    {{ formatDateRow(pay.created_at) }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate font-semibold tabular-nums text-[#7d623f] dark:text-[#c8a97e] text-right"
                                    dir="ltr"
                                >
                                    {{ formatMoney(pay.amount, pay.currency_label_ar) }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate text-sm uppercase text-muted-foreground text-right"
                                    dir="ltr"
                                >
                                    {{ pay.payment_method }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    v-if="pay.invoice_uuid"
                                    class="block min-w-0 truncate font-mono text-[13px] text-muted-foreground"
                                    dir="ltr"
                                    :title="pay.invoice_uuid"
                                >
                                    {{ shortUuid(pay.invoice_uuid, 12) }}
                                </span>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    v-if="pay.order_uuid"
                                    class="block min-w-0 truncate font-mono text-[13px] text-muted-foreground"
                                    dir="ltr"
                                    :title="pay.order_uuid"
                                >
                                    {{ shortUuid(pay.order_uuid, 12) }}
                                </span>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate text-muted-foreground"
                                    dir="rtl"
                                    :title="pay.note ?? undefined"
                                >
                                    {{ pay.note ?? '—' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-center text-sm text-muted-foreground">
            <Link href="/merchant/invoices" class="font-medium text-primary underline-offset-4 hover:underline">
                عرض الفواتير
            </Link>
        </p>
    </div>
</template>
