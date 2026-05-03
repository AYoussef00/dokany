<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Copy, ImagePlus, Loader2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import StorefrontDocumentHead from '@/components/StorefrontDocumentHead.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type OrderLine = {
    product_id: number;
    name: string;
    quantity: number;
    unit_price: string;
    line_total: string;
};

const STATUS_PENDING = 'pending_payment';

const props = defineProps<{
    seller: {
        name: string;
        logo_url: string | null;
        instapay_wallet: string | null;
        whatsapp_href: string | null;
    };
    order: {
        uuid: string;
        buyer_name: string;
        buyer_phone: string;
        buyer_maps_url: string | null;
        lines: OrderLine[];
        subtotal: string;
        currency_label_ar: string;
        currency_label_en: string;
        status: string;
    };
    storefrontUrl: string;
    orderPayPostPath: string;
    paymentInstructions: string;
}>();

defineOptions({ layout: null });

const copiedWallet = ref(false);
let copyTimeout: ReturnType<typeof setTimeout> | null = null;

const form = useForm({
    payment_receipt: null as File | null,
});

const hasWallet = computed(() => (props.seller.instapay_wallet ?? '').trim().length > 0);
const hasProof = computed(() => form.payment_receipt instanceof File);

const isPending = computed(() => props.order.status === STATUS_PENDING);

function formatSubtotal(): string {
    const n = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(props.order.subtotal));
    return `${n} ${props.order.currency_label_ar}`;
}

function formatLineTotal(total: string): string {
    const n = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(total));
    return `${n} ${props.order.currency_label_ar}`;
}

function onFile(e: Event): void {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0] ?? null;
    form.payment_receipt = f;
    if (f) {
        input.value = '';
    }
}

async function copyWallet(): Promise<void> {
    if (!hasWallet.value || !props.seller.instapay_wallet) {
        return;
    }
    try {
        await navigator.clipboard.writeText(props.seller.instapay_wallet.trim());
        copiedWallet.value = true;
        if (copyTimeout !== null) {
            clearTimeout(copyTimeout);
        }
        copyTimeout = setTimeout(() => {
            copiedWallet.value = false;
            copyTimeout = null;
        }, 2000);
    } catch {
        copiedWallet.value = false;
    }
}

function submitReceipt(): void {
    if (!hasProof.value) {
        return;
    }
    form.post(props.orderPayPostPath, {
        forceFormData: true,
        preserveScroll: true,
    });
}

onBeforeUnmount(() => {
    if (copyTimeout !== null) {
        clearTimeout(copyTimeout);
    }
});
</script>

<template>
    <StorefrontDocumentHead
        :store-name="seller.name"
        :logo-url="seller.logo_url"
        title-suffix="الدفع"
    />

    <div
        class="storefront-order-payment min-h-svh bg-[#f7f5f2] antialiased"
        dir="rtl"
        lang="ar"
    >
        <header
            class="sticky top-0 z-40 border-b border-[#111111]/[0.08] bg-[#faf8f5]/95 px-4 py-3 backdrop-blur-md sm:px-6"
        >
            <div class="mx-auto flex max-w-lg items-center justify-end">
                <Link
                    :href="storefrontUrl"
                    class="text-sm font-semibold text-[#7d623f] hover:text-[#5c4a32]"
                >
                    رجوع للمتجر
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-lg px-4 pb-16 pt-8 sm:px-6">
            <h1 class="text-xl font-semibold text-[#111111]">
                الدفع عبر InstaPay
            </h1>
            <p class="mt-2 text-[15px] leading-relaxed text-[#6b6560]">
                {{ paymentInstructions }}
            </p>

            <div
                v-if="isPending"
                class="mt-8 space-y-4"
            >
                <div
                    class="space-y-4 rounded-2xl bg-white p-5 shadow-[0_2px_24px_rgb(17_17_17_/_0.06)] ring-1 ring-[#111111]/[0.05]"
                >
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-[#9c9590]">
                            المبلغ المطلوب
                        </p>
                        <p class="mt-1 text-2xl font-semibold tabular-nums text-[#111111]" dir="ltr">
                            {{ formatSubtotal() }}
                        </p>
                    </div>

                    <div v-if="hasWallet" class="rounded-xl bg-[#faf9f7] p-4 ring-1 ring-[#111111]/[0.06]">
                        <p class="text-sm font-medium text-[#111111]">
                            رقم التاجر على InstaPay
                        </p>
                        <p
                            class="mt-2 break-all font-mono text-base font-semibold tabular-nums text-[#111111]"
                            dir="ltr"
                        >
                            {{ seller.instapay_wallet }}
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="mt-3 h-9 gap-1.5 rounded-lg border-[#111111]/12"
                            @click="copyWallet"
                        >
                            <Copy class="size-3.5" stroke-width="2" />
                            {{ copiedWallet ? 'تم النسخ' : 'نسخ الرقم' }}
                        </Button>
                    </div>
                    <div
                        v-else
                        class="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-950"
                    >
                        <p>لم يُضف رقم InstaPay لهذا المتجر بعد.</p>
                        <p v-if="seller.whatsapp_href" class="mt-2">
                            يمكنك
                            <a
                                :href="seller.whatsapp_href"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-semibold underline underline-offset-2"
                            >التواصل عبر واتساب</a>.
                        </p>
                        <p v-else class="mt-2">
                            يرجى التواصل مع المتجر لمعرفة طريقة الدفع.
                        </p>
                    </div>

                    <div>
                        <Label class="text-[#111111]">إرفاق إيصال التحويل</Label>
                        <label
                            class="mt-2 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-[#111111]/15 bg-[#faf9f7] px-4 py-8 transition hover:bg-[#f3f0eb]"
                        >
                            <ImagePlus class="size-8 text-[#9c9590]" stroke-width="1.25" />
                            <span class="text-sm font-medium text-[#5c5650]">اضغط لاختيار صورة الإيصال</span>
                            <input
                                type="file"
                                accept="image/*"
                                class="sr-only"
                                @change="onFile"
                            />
                        </label>
                        <InputError class="mt-2" :message="form.errors.payment_receipt" />
                    </div>

                    <Button
                        type="button"
                        class="h-12 w-full rounded-xl bg-[#111111] text-white hover:bg-[#1f1f1f]"
                        :disabled="!hasProof || form.processing"
                        @click="submitReceipt"
                    >
                        <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                        <span v-else>تأكيد إرسال الإيصال</span>
                    </Button>
                </div>

                <details class="rounded-xl bg-white p-4 text-sm shadow-sm ring-1 ring-[#111111]/[0.05]">
                    <summary class="cursor-pointer font-medium text-[#111111]">
                        ملخص الطلب
                    </summary>
                    <ul class="mt-3 space-y-2 border-t border-[#111111]/[0.08] pt-3">
                        <li
                            v-for="line in order.lines"
                            :key="line.product_id"
                            class="flex justify-between gap-2"
                        >
                            <span class="min-w-0 truncate" dir="auto">{{ line.name }}</span>
                            <span class="shrink-0 tabular-nums" dir="ltr">
                                ×{{ line.quantity }} — {{ formatLineTotal(line.line_total) }}
                            </span>
                        </li>
                    </ul>
                </details>
            </div>

            <div
                v-else
                class="mt-8 rounded-2xl border border-[#111111]/10 bg-white px-4 py-6 text-center text-sm text-[#6b6560]"
            >
                <p>حالة الطلب غير متاحة لهذه الصفحة.</p>
                <Button as-child variant="outline" class="mt-4 h-10 rounded-xl">
                    <Link :href="storefrontUrl">العودة للمتجر</Link>
                </Button>
            </div>
        </main>
    </div>
</template>
