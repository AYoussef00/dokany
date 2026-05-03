<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Check,
    ExternalLink,
    MapPin,
    MoreHorizontal,
    Trash2,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { dashboard } from '@/routes';

type OrderLine = {
    product_id: number;
    name: string;
    quantity: number;
    unit_price: string;
    line_total: string;
};

type OrderRow = {
    uuid: string;
    buyer_name: string;
    buyer_phone: string;
    buyer_address: string;
    buyer_maps_url: string | null;
    lines: OrderLine[];
    subtotal: string;
    currency_label_ar: string;
    status: string;
    payment_receipt_url: string | null;
    created_at: string | null;
    from_payment_link: boolean;
};

const props = defineProps<{
    orders: OrderRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'الرئيسية', href: dashboard() },
            { title: 'الطلبات', href: '/merchant/orders' },
        ],
    },
});

const page = usePage<{ flash: { success?: string | null; error?: string | null } }>();

const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashError = computed(() => page.props.flash?.error ?? null);

const processingKey = ref<string | null>(null);
const expandedUuids = ref<string[]>([]);

const STATUS_PENDING_PAYMENT = 'pending_payment';
const STATUS_PAYMENT_SUBMITTED = 'payment_submitted';
const STATUS_ACCEPTED = 'accepted';
const STATUS_REJECTED = 'rejected';

function statusLabel(status: string): string {
    switch (status) {
        case STATUS_PENDING_PAYMENT:
            return 'في انتظار الدفع';
        case STATUS_PAYMENT_SUBMITTED:
            return 'بانتظار قرارك';
        case STATUS_ACCEPTED:
            return 'مقبول';
        case STATUS_REJECTED:
            return 'مرفوض';
        default:
            return status;
    }
}

function statusBadgeClass(status: string): string {
    switch (status) {
        case STATUS_PENDING_PAYMENT:
            return 'border-amber-500/30 bg-amber-500/10 text-amber-950 dark:text-amber-100';
        case STATUS_PAYMENT_SUBMITTED:
            return 'border-[#7d623f]/30 bg-[#c8a97e]/15 text-[#3d2f1a] dark:border-[#c8a97e]/30 dark:bg-[#c8a97e]/10 dark:text-[#e8dcc8]';
        case STATUS_ACCEPTED:
            return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-950 dark:text-emerald-100';
        case STATUS_REJECTED:
            return 'border-red-500/30 bg-red-500/10 text-red-950 dark:text-red-100';
        default:
            return 'border-border bg-muted text-foreground';
    }
}

function formatMoney(amount: string, currency: string): string {
    const n = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(amount));
    return `${n} ${currency}`;
}

function formatLineTotal(line: OrderLine, currency: string): string {
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

/** سطر واحد قصير للجدول */
function formatDateRow(iso: string | null): string {
    const { date, time } = formatDateParts(iso);
    return time ? `${date} · ${time}` : date;
}

function canDecide(status: string): boolean {
    return status === STATUS_PAYMENT_SUBMITTED;
}

function canDeleteOrder(status: string): boolean {
    return status !== STATUS_ACCEPTED;
}

function deleteOrder(order: OrderRow): void {
    if (!canDeleteOrder(order.status)) {
        return;
    }
    if (
        !window.confirm(
            'حذف الطلب نهائياً؟ سيتم إزالة بياناته من القائمة ولا يمكن التراجع. الطلبات المقبولة لا يمكن حذفها.',
        )
    ) {
        return;
    }
    processingKey.value = `${order.uuid}-delete`;
    router.delete(`/merchant/orders/${order.uuid}`, {
        preserveScroll: true,
        onFinish: () => {
            processingKey.value = null;
        },
    });
}

function isDeleting(order: OrderRow): boolean {
    return processingKey.value === `${order.uuid}-delete`;
}

function postDecision(order: OrderRow, action: 'accept' | 'reject'): void {
    const path = action === 'accept' ? 'accept' : 'reject';
    processingKey.value = `${order.uuid}-${action}`;
    router.post(
        `/merchant/orders/${order.uuid}/${path}`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processingKey.value = null;
            },
        },
    );
}

function isProcessing(order: OrderRow, action: 'accept' | 'reject'): boolean {
    return processingKey.value === `${order.uuid}-${action}`;
}

function linesSummary(order: OrderRow): string {
    if (order.lines.length === 0) {
        return '—';
    }
    if (order.lines.length === 1) {
        return order.lines[0].name;
    }
    return `${order.lines[0].name} (+${order.lines.length - 1})`;
}

function linesDetailTitle(order: OrderRow): string {
    return order.lines.map((l) => `${l.name} ×${l.quantity}`).join('، ');
}

function toggleExpand(uuid: string): void {
    const i = expandedUuids.value.indexOf(uuid);
    if (i >= 0) {
        expandedUuids.value.splice(i, 1);
    } else {
        expandedUuids.value.push(uuid);
    }
}

function isExpanded(uuid: string): boolean {
    return expandedUuids.value.includes(uuid);
}
</script>

<template>
    <Head title="الطلبات" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        dir="rtl"
        lang="ar"
    >
        <div class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
            <h1 class="text-lg font-semibold tracking-tight">طلبات المتجر</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                طلبات العملاء الواردة من واجهة المتجر العامة — قبول أو رفض بعد مراجعة الإيصال، أو حذف الطلبات
                التي لم تُقبل بعد (بانتظار الدفع، بانتظار قرارك، أو المرفوضة).
            </p>
        </div>

        <div
            v-if="flashSuccess"
            class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100"
            role="status"
        >
            {{ flashSuccess }}
        </div>
        <div
            v-if="flashError"
            class="rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 text-sm text-red-900 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-100"
            role="alert"
        >
            {{ flashError }}
        </div>

        <p
            v-if="orders.length === 0"
            class="rounded-xl border border-dashed border-sidebar-border/70 px-6 py-16 text-center text-sm text-muted-foreground dark:border-sidebar-border"
        >
            لا توجد طلبات حتى الآن. ستظهر هنا عمليات الشراء من متجرك العام.
        </p>

        <div
            v-else
            class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border"
        >
            <div class="overflow-x-auto">
                <table
                    class="w-full min-w-[58rem] table-fixed border-collapse text-sm"
                    dir="rtl"
                >
                    <colgroup>
                        <col style="width: 11%;" />
                        <col style="width: 12%;" />
                        <col style="width: 10%;" />
                        <col style="width: 21%;" />
                        <col style="width: 17%;" />
                        <col style="width: 11%;" />
                        <col style="width: 11%;" />
                        <col style="width: 7%;" />
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
                                العنوان
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                المنتجات
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                الإجمالي
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                الحالة
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-center text-sm font-medium text-muted-foreground"
                            >
                                <span class="sr-only">إجراءات</span>
                                <span aria-hidden="true" class="text-muted-foreground/70">⋯</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="order in orders" :key="order.uuid">
                            <tr
                                class="border-b border-border/40 transition-colors hover:bg-muted/30"
                            >
                                <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                    <span
                                        class="block min-w-0 truncate tabular-nums text-muted-foreground text-right"
                                        dir="ltr"
                                        :title="formatDateRow(order.created_at)"
                                    >
                                        {{ formatDateRow(order.created_at) }}
                                    </span>
                                </td>
                                <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                    <span
                                        class="block min-w-0 truncate font-medium text-foreground"
                                        dir="rtl"
                                        :title="order.buyer_name"
                                    >
                                        {{ order.buyer_name }}
                                    </span>
                                </td>
                                <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                    <span
                                        class="block min-w-0 truncate tabular-nums text-foreground text-right"
                                        dir="ltr"
                                        :title="order.buyer_phone"
                                    >
                                        {{ order.buyer_phone }}
                                    </span>
                                </td>
                                <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                    <span
                                        class="block min-w-0 truncate text-foreground"
                                        dir="rtl"
                                        :title="order.buyer_address"
                                    >
                                        {{ order.buyer_address }}
                                    </span>
                                </td>
                                <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                    <span
                                        class="block min-w-0 truncate text-muted-foreground"
                                        dir="rtl"
                                        :title="linesDetailTitle(order)"
                                    >
                                        {{ linesSummary(order) }}<template v-if="order.from_payment_link">
                                            <span class="text-[#7d623f] dark:text-[#c8a97e]"> · رابط</span>
                                        </template>
                                    </span>
                                </td>
                                <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                    <span
                                        class="block min-w-0 truncate font-medium tabular-nums text-[#7d623f] dark:text-[#c8a97e] text-right"
                                        dir="ltr"
                                    >
                                        {{ formatMoney(order.subtotal, order.currency_label_ar) }}
                                    </span>
                                </td>
                                <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                    <span
                                        class="inline-flex max-w-full min-w-0 truncate rounded-md border px-2 py-0.5 text-xs font-medium"
                                        :class="statusBadgeClass(order.status)"
                                    >
                                        {{ statusLabel(order.status) }}
                                    </span>
                                </td>
                                <td class="box-border px-3 py-2 align-middle text-center" dir="rtl">
                                    <div class="flex justify-center">
                                        <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="size-8 shrink-0 text-muted-foreground hover:text-foreground"
                                                :disabled="processingKey !== null"
                                            >
                                                <MoreHorizontal class="size-4" stroke-width="2" />
                                                <span class="sr-only">إجراءات الطلب</span>
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="w-52" dir="rtl">
                                            <DropdownMenuItem
                                                class="cursor-pointer"
                                                @click="toggleExpand(order.uuid)"
                                            >
                                                {{ isExpanded(order.uuid) ? 'إخفاء التفاصيل' : 'عرض التفاصيل' }}
                                            </DropdownMenuItem>
                                            <template v-if="order.buyer_maps_url || order.payment_receipt_url">
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem v-if="order.buyer_maps_url" as-child>
                                                    <a
                                                        :href="order.buyer_maps_url"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="flex w-full cursor-pointer items-center gap-2"
                                                    >
                                                        <MapPin class="size-4 shrink-0" stroke-width="2" />
                                                        فتح الخريطة
                                                    </a>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem v-if="order.payment_receipt_url" as-child>
                                                    <a
                                                        :href="order.payment_receipt_url"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="flex w-full cursor-pointer items-center gap-2"
                                                    >
                                                        <ExternalLink class="size-4 shrink-0" stroke-width="2" />
                                                        عرض إيصال الدفع
                                                    </a>
                                                </DropdownMenuItem>
                                            </template>
                                            <template v-if="canDecide(order.status)">
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    class="cursor-pointer text-emerald-700 focus:text-emerald-700 dark:text-emerald-400"
                                                    :disabled="processingKey !== null"
                                                    @click="postDecision(order, 'accept')"
                                                >
                                                    <Check class="size-4 shrink-0" stroke-width="2.5" />
                                                    قبول الطلب
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    class="cursor-pointer text-red-600 focus:text-red-600 dark:text-red-400"
                                                    :disabled="processingKey !== null"
                                                    @click="postDecision(order, 'reject')"
                                                >
                                                    <XCircle class="size-4 shrink-0" stroke-width="2" />
                                                    رفض الطلب
                                                </DropdownMenuItem>
                                            </template>
                                            <template v-if="canDeleteOrder(order.status)">
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    variant="destructive"
                                                    class="cursor-pointer"
                                                    :disabled="processingKey !== null"
                                                    @click="deleteOrder(order)"
                                                >
                                                    <Trash2 class="size-4 shrink-0" stroke-width="2" />
                                                    {{ isDeleting(order) ? 'جاري الحذف…' : 'حذف الطلب' }}
                                                </DropdownMenuItem>
                                            </template>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-if="isExpanded(order.uuid)"
                                class="border-b border-border/50 bg-muted/25 dark:bg-muted/15"
                            >
                                <td colspan="8" class="box-border px-3 py-4">
                                    <div class="text-start" dir="rtl" lang="ar">
                                        <div class="grid gap-4 text-sm sm:grid-cols-2">
                                            <div>
                                                <p class="text-xs font-medium text-muted-foreground">
                                                    العنوان كاملاً
                                                </p>
                                                <p class="mt-1 leading-relaxed text-foreground">
                                                    {{ order.buyer_address }}
                                                </p>
                                            </div>
                                            <div v-if="order.buyer_maps_url">
                                                <p class="text-xs font-medium text-muted-foreground">الموقع</p>
                                                <a
                                                    :href="order.buyer_maps_url"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="mt-1 inline-flex items-center gap-1 font-medium text-primary underline-offset-4 hover:underline"
                                                >
                                                    <MapPin class="size-3.5" stroke-width="2" />
                                                    فتح على خرائط جوجل
                                                </a>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <p class="text-xs font-medium text-muted-foreground">تفصيل المنتجات</p>
                                            <ul
                                                class="mt-2 divide-y divide-border/60 rounded-lg border border-border/60 bg-background"
                                            >
                                                <li
                                                    v-for="line in order.lines"
                                                    :key="`${order.uuid}-${line.product_id}`"
                                                    class="flex justify-between gap-2 px-3 py-2"
                                                >
                                                    <span class="min-w-0 font-medium" dir="auto">{{ line.name }}</span>
                                                    <span class="shrink-0 tabular-nums text-muted-foreground" dir="ltr">
                                                        ×{{ line.quantity }} —
                                                        {{ formatLineTotal(line, order.currency_label_ar) }}
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div v-if="order.payment_receipt_url" class="mt-4">
                                            <a
                                                :href="order.payment_receipt_url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-2 font-medium text-primary underline-offset-4 hover:underline"
                                            >
                                                <ExternalLink class="size-4" stroke-width="2" />
                                                عرض إيصال الدفع
                                            </a>
                                        </div>
                                        <div
                                            v-if="canDecide(order.status) || canDeleteOrder(order.status)"
                                            class="mt-5 flex flex-wrap gap-2 border-t border-border/60 pt-4"
                                        >
                                            <template v-if="canDecide(order.status)">
                                                <Button
                                                    type="button"
                                                    class="h-10 gap-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700"
                                                    :disabled="processingKey !== null"
                                                    @click="postDecision(order, 'accept')"
                                                >
                                                    <Check class="size-4" stroke-width="2.5" />
                                                    {{
                                                        isProcessing(order, 'accept')
                                                            ? 'جاري القبول…'
                                                            : 'قبول الطلب'
                                                    }}
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    class="h-10 gap-2 rounded-xl border-red-500/40 text-red-700 dark:text-red-400"
                                                    :disabled="processingKey !== null"
                                                    @click="postDecision(order, 'reject')"
                                                >
                                                    <XCircle class="size-4" stroke-width="2" />
                                                    {{
                                                        isProcessing(order, 'reject') ? 'جاري الرفض…' : 'رفض الطلب'
                                                    }}
                                                </Button>
                                            </template>
                                            <Button
                                                v-if="canDeleteOrder(order.status)"
                                                type="button"
                                                variant="outline"
                                                class="h-10 gap-2 rounded-xl border-destructive/45 text-destructive hover:bg-destructive/10"
                                                :disabled="processingKey !== null"
                                                @click="deleteOrder(order)"
                                            >
                                                <Trash2 class="size-4" stroke-width="2" />
                                                {{ isDeleting(order) ? 'جاري الحذف…' : 'حذف الطلب' }}
                                            </Button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
