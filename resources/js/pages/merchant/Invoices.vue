<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, Eye, MapPin } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { dashboard } from '@/routes';

type InvoiceLine = {
    product_id: number;
    name: string;
    quantity: number;
    unit_price: string;
    line_total: string;
};

type InvoiceRow = {
    uuid: string;
    order_uuid: string | null;
    buyer_name: string;
    buyer_phone: string;
    buyer_address: string;
    buyer_maps_url: string | null;
    lines: InvoiceLine[];
    subtotal: string;
    currency_label_ar: string;
    currency_label_en: string;
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

const selectedInvoice = ref<InvoiceRow | null>(null);

function formatMoney(amount: string, currency: string): string {
    const n = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(amount));
    return `${n} ${currency}`;
}

function formatLineTotal(line: InvoiceLine, currency: string): string {
    return formatMoney(line.line_total, currency);
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

/** للعرض في الجدول والترويسة داخل النافذة */
function formatDateFull(iso: string | null): string {
    if (!iso) {
        return '—';
    }
    try {
        return new Intl.DateTimeFormat('en-GB', {
            dateStyle: 'full',
            timeStyle: 'short',
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}

function shortUuid(uuid: string, keep: number = 10): string {
    if (uuid.length <= keep) {
        return uuid;
    }
    return `${uuid.slice(0, keep)}…`;
}

function openInvoiceView(inv: InvoiceRow): void {
    selectedInvoice.value = inv;
}

function onInvoiceDialogOpenChange(open: boolean): void {
    if (!open) {
        selectedInvoice.value = null;
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
                فواتير تُنشأ عند قبولك لطلب بعد استلام إيصال الدفع من العميل.
            </p>
        </div>

        <p
            v-if="invoices.length === 0"
            class="rounded-xl border border-dashed border-sidebar-border/70 px-6 py-16 text-center text-sm text-muted-foreground dark:border-sidebar-border"
        >
            لا توجد فواتير بعد. عند قبول طلب من صفحة الطلبات ستظهر الفاتورة هنا.
        </p>

        <Dialog :open="selectedInvoice !== null" @update:open="onInvoiceDialogOpenChange">
            <DialogContent
                class="max-h-[min(92vh,720px)] max-w-2xl gap-0 overflow-y-auto border-sidebar-border/80 p-0 sm:max-w-2xl"
                dir="rtl"
                lang="ar"
            >
                <template v-if="selectedInvoice">
                    <div class="border-b border-border bg-muted/45 px-6 py-4 dark:bg-muted/25">
                        <DialogHeader class="space-y-3 text-right">
                            <div class="flex items-start justify-between gap-3">
                                <DialogTitle class="text-xl font-semibold tracking-tight">
                                    تفاصيل الفاتورة
                                </DialogTitle>
                            </div>
                            <div class="flex flex-col gap-1 border border-border/60 rounded-xl bg-background/80 px-3 py-2.5 text-start shadow-sm">
                                <p class="text-xs font-medium text-muted-foreground">رقم الفاتورة</p>
                                <p class="break-all font-mono text-[13px] leading-snug text-foreground" dir="ltr">
                                    {{ selectedInvoice.uuid }}
                                </p>
                                <p class="mt-2 text-xs font-medium text-muted-foreground">تاريخ الإصدار</p>
                                <p class="text-sm text-foreground">
                                    {{ formatDateFull(selectedInvoice.created_at) }}
                                </p>
                                <p
                                    v-if="selectedInvoice.order_uuid"
                                    class="mt-2 text-xs font-medium text-muted-foreground"
                                >
                                    الطلب المرتبط
                                </p>
                                <p
                                    v-if="selectedInvoice.order_uuid"
                                    class="break-all font-mono text-[13px] text-muted-foreground"
                                    dir="ltr"
                                >
                                    {{ selectedInvoice.order_uuid }}
                                </p>
                            </div>
                        </DialogHeader>
                    </div>

                    <div class="space-y-5 px-6 py-5">
                        <section
                            class="rounded-xl border border-border/70 bg-card/50 px-4 py-3.5 shadow-sm dark:border-border/80"
                        >
                            <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                بيانات العميل
                            </h3>
                            <dl class="grid gap-3 text-sm">
                                <div>
                                    <dt class="text-xs text-muted-foreground">الاسم</dt>
                                    <dd class="mt-0.5 font-medium text-foreground" dir="auto">
                                        {{ selectedInvoice.buyer_name }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">الهاتف</dt>
                                    <dd class="mt-0.5 tabular-nums text-foreground" dir="ltr">
                                        {{ selectedInvoice.buyer_phone }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-muted-foreground">العنوان</dt>
                                    <dd class="mt-0.5 leading-relaxed text-foreground" dir="auto">
                                        {{ selectedInvoice.buyer_address }}
                                    </dd>
                                </div>
                                <div v-if="selectedInvoice.buyer_maps_url">
                                    <dt class="text-xs text-muted-foreground">الموقع</dt>
                                    <dd class="mt-1">
                                        <a
                                            :href="selectedInvoice.buyer_maps_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 text-sm font-medium text-primary underline-offset-4 hover:underline"
                                        >
                                            <MapPin class="size-3.5 shrink-0" stroke-width="2" />
                                            فتح على خرائط جوجل
                                            <ExternalLink class="size-3.5 shrink-0 opacity-70" stroke-width="2" />
                                        </a>
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section>
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                بنود الفاتورة
                            </h3>
                            <div
                                class="overflow-hidden rounded-xl border border-border/80 bg-background shadow-sm"
                            >
                                <table class="w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="border-b border-border bg-muted/50 text-start">
                                            <th class="px-3 py-2.5 font-medium text-muted-foreground">الصنف</th>
                                            <th class="w-[4.5rem] px-2 py-2.5 font-medium text-muted-foreground">
                                                الكمية
                                            </th>
                                            <th class="px-3 py-2.5 font-medium text-muted-foreground">سعر الوحدة</th>
                                            <th class="px-3 py-2.5 font-medium text-muted-foreground">الإجمالي</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="selectedInvoice.lines.length === 0">
                                            <td
                                                colspan="4"
                                                class="px-3 py-6 text-center text-sm text-muted-foreground"
                                            >
                                                لا توجد بنود في هذه الفاتورة.
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="line in selectedInvoice.lines"
                                            :key="`${selectedInvoice.uuid}-${line.product_id}`"
                                            class="border-b border-border/50 last:border-0"
                                        >
                                            <td class="px-3 py-2.5 font-medium text-foreground" dir="auto">
                                                {{ line.name }}
                                            </td>
                                            <td
                                                class="px-2 py-2.5 tabular-nums text-foreground"
                                                dir="ltr"
                                            >
                                                {{ line.quantity }}
                                            </td>
                                            <td
                                                class="px-3 py-2.5 tabular-nums text-muted-foreground"
                                                dir="ltr"
                                            >
                                                {{
                                                    formatMoney(line.unit_price, selectedInvoice.currency_label_ar)
                                                }}
                                            </td>
                                            <td
                                                class="px-3 py-2.5 font-medium tabular-nums text-[#7d623f] dark:text-[#c8a97e]"
                                                dir="ltr"
                                            >
                                                {{ formatLineTotal(line, selectedInvoice.currency_label_ar) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <div
                            class="flex items-center justify-between gap-4 rounded-xl border border-[#7d623f]/25 bg-[#c8a97e]/10 px-4 py-3 dark:border-[#c8a97e]/20 dark:bg-[#c8a97e]/5"
                        >
                            <span class="text-sm font-semibold text-foreground">إجمالي الفاتورة</span>
                            <span
                                class="text-lg font-bold tabular-nums text-[#7d623f] dark:text-[#d4b896]"
                                dir="ltr"
                            >
                                {{ formatMoney(selectedInvoice.subtotal, selectedInvoice.currency_label_ar) }}
                            </span>
                        </div>
                    </div>

                    <DialogFooter
                        class="gap-2 border-t border-border bg-muted/20 px-6 py-4 sm:justify-start dark:bg-muted/10"
                    >
                        <Button type="button" variant="outline" @click="onInvoiceDialogOpenChange(false)">
                            إغلاق
                        </Button>
                    </DialogFooter>
                </template>
            </DialogContent>
        </Dialog>

        <div
            v-if="invoices.length > 0"
            class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[56rem] table-fixed border-collapse text-sm" dir="rtl">
                    <colgroup>
                        <col style="width: 14%;" />
                        <col style="width: 14%;" />
                        <col style="width: 12%;" />
                        <col style="width: 18%;" />
                        <col style="width: 13%;" />
                        <col style="width: 15%;" />
                        <col style="width: 14%;" />
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
                                رقم الفاتورة
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
                                العميل
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                الهاتف
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                المبلغ
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-center text-sm font-medium text-muted-foreground"
                            >
                                عرض
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="inv in invoices"
                            :key="inv.uuid"
                            class="border-b border-border/40 transition-colors hover:bg-muted/30"
                        >
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate tabular-nums text-muted-foreground text-right"
                                    dir="ltr"
                                    :title="formatDateRow(inv.created_at)"
                                >
                                    {{ formatDateRow(inv.created_at) }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate font-mono text-[13px] text-foreground"
                                    dir="ltr"
                                    :title="inv.uuid"
                                >
                                    {{ shortUuid(inv.uuid, 12) }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    v-if="inv.order_uuid"
                                    class="block min-w-0 truncate font-mono text-[13px] text-muted-foreground"
                                    dir="ltr"
                                    :title="inv.order_uuid"
                                >
                                    {{ shortUuid(inv.order_uuid, 10) }}
                                </span>
                                <span v-else class="block text-muted-foreground">—</span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate font-medium text-foreground"
                                    dir="rtl"
                                    :title="inv.buyer_name"
                                >
                                {{ inv.buyer_name }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate tabular-nums text-foreground text-right"
                                    dir="ltr"
                                    :title="inv.buyer_phone"
                                >
                                {{ inv.buyer_phone }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate font-medium tabular-nums text-[#7d623f] dark:text-[#c8a97e] text-right"
                                    dir="ltr"
                                >
                                {{ formatMoney(inv.subtotal, inv.currency_label_ar) }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-center" dir="rtl">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="h-8 gap-1.5 px-3 text-xs font-medium"
                                    @click="openInvoiceView(inv)"
                                >
                                    <Eye class="size-3.5 shrink-0" stroke-width="2" />
                                    عرض
                                </Button>
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
