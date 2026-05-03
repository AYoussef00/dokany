<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, Copy, CreditCard, ImagePlus, Loader2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import { dashboard } from '@/routes';
import { home } from '@/routes';
import { store as subscriptionPaymentStore } from '@/routes/onboarding/subscription-payment';

defineOptions({ layout: null });

const props = defineProps<{
    subscriptionAmount: number;
    subscriptionCurrency: string;
    platformInstapayNumber: string;
    paymentInstructions: string;
    merchantInstapay: string | null;
}>();

const copiedKey = ref<'platform' | 'merchant' | null>(null);
let copyFlashTimeout: ReturnType<typeof setTimeout> | null = null;
const form = useForm({
    payment_proof: null as File | null,
});

const hasPlatformNumber = computed(() => props.platformInstapayNumber.trim().length > 0);
const hasMerchantWallet = computed(() => (props.merchantInstapay ?? '').trim().length > 0);
const hasPaymentProof = computed(() => form.payment_proof instanceof File);

function flashCopied(key: 'platform' | 'merchant'): void {
    if (copyFlashTimeout !== null) {
        clearTimeout(copyFlashTimeout);
    }
    copiedKey.value = key;
    copyFlashTimeout = setTimeout(() => {
        copiedKey.value = null;
        copyFlashTimeout = null;
    }, 2000);
}

function onFile(e: Event): void {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0] ?? null;
    form.payment_proof = f;
    if (f) {
        input.value = '';
    }
}

async function copyNumber(): Promise<void> {
    if (!hasPlatformNumber.value) {
        return;
    }
    try {
        await navigator.clipboard.writeText(props.platformInstapayNumber.trim());
        flashCopied('platform');
    } catch {
        copiedKey.value = null;
    }
}

async function copyMerchantWallet(): Promise<void> {
    if (!hasMerchantWallet.value || !props.merchantInstapay) {
        return;
    }
    try {
        await navigator.clipboard.writeText(props.merchantInstapay.trim());
        flashCopied('merchant');
    } catch {
        copiedKey.value = null;
    }
}

function submitPaid(): void {
    if (!hasPaymentProof.value) {
        return;
    }
    form.post(subscriptionPaymentStore.url(), {
        preserveScroll: true,
    });
}

onBeforeUnmount(() => {
    if (copyFlashTimeout !== null) {
        clearTimeout(copyFlashTimeout);
    }
});
</script>

<template>
    <Head title="إتمام الاشتراك — دكاني">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link
            href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Inter:wght@400;600;700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        dir="rtl"
        lang="ar"
        class="dokany-landing min-h-screen bg-[#F8F8F7] text-[#111111] antialiased selection:bg-[#C8A97E]/25"
    >
        <div
            class="relative z-10 mx-auto min-h-screen max-w-[430px] bg-white shadow-[0_4px_40px_-12px_rgba(17,17,17,0.06)] sm:my-8 sm:min-h-[calc(100vh-4rem)] sm:rounded-2xl sm:ring-1 sm:ring-[#E6E5E2]"
        >
            <header class="border-b border-[#E6E5E2] px-6 py-4 sm:rounded-t-2xl">
                <div class="flex items-center justify-between gap-3">
                    <Link
                        :href="dashboard()"
                        class="flex items-center gap-1 text-[14px] font-semibold text-[#6B7280] transition hover:text-[#111111]"
                    >
                        <ArrowLeft class="h-4 w-4" stroke-width="2" />
                        لاحقاً
                    </Link>
                    <span class="text-[16px] font-bold">دكاني</span>
                    <Link
                        :href="home()"
                        class="text-[14px] font-semibold text-[#C8A97E] transition hover:text-[#B89367]"
                    >
                        الرئيسية
                    </Link>
                </div>
                <p class="mt-4 text-center text-[12px] uppercase tracking-[0.08em] text-[#6B7280]">الخطوة ٤ — الدفع</p>
            </header>

            <main class="px-6 pb-14 pt-8">
                <div class="mb-8 text-center">
                    <div
                        class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F3EFE6] text-[#C8A97E]"
                    >
                        <CreditCard class="h-6 w-6" stroke-width="1.5" />
                    </div>
                    <h1 class="text-[26px] font-bold leading-[1.2] tracking-[-0.02em]">إتمام الاشتراك</h1>
                    <p class="mt-2 text-[15px] text-[#6B7280]">سدّد الباقة ثم أبلغنا لنفعّل حسابك.</p>
                </div>

                <div class="rounded-2xl border border-[#E6E5E2] bg-[#F8F8F7] p-6">
                    <p class="text-center text-[12px] font-semibold uppercase tracking-[0.06em] text-[#6B7280]">
                        الاشتراك الشهري
                    </p>
                    <div class="mt-1 flex items-baseline justify-center gap-1.5">
                        <span class="font-inter text-[40px] font-semibold tracking-[-0.03em]">{{ subscriptionAmount }}</span>
                        <span class="text-[17px] font-medium text-[#6B7280]">{{ subscriptionCurrency }}</span>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    <div class="rounded-2xl border border-[#E6E5E2] bg-white p-5">
                        <p class="mb-3 text-[14px] font-semibold text-[#111111]">رقم InstaPay للتحويل</p>
                        <p class="text-[14px] leading-relaxed text-[#6B7280]">{{ paymentInstructions }}</p>

                        <div
                            v-if="hasPlatformNumber"
                            class="mt-4 flex flex-wrap items-center gap-2 rounded-xl bg-[#F8F8F7] px-4 py-3 ring-1 ring-[#E6E5E2]"
                        >
                            <span
                                class="min-w-0 flex-1 font-inter text-[17px] font-semibold tracking-tight text-[#111111] break-all"
                                dir="ltr"
                            >{{ platformInstapayNumber }}</span>
                            <button
                                type="button"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-[#111111] px-3 py-2 text-[13px] font-semibold text-white transition hover:bg-[#222222]"
                                @click="copyNumber"
                            >
                                <Check v-if="copiedKey === 'platform'" class="h-3.5 w-3.5" stroke-width="2.5" />
                                <Copy v-else class="h-3.5 w-3.5" stroke-width="2" />
                                {{ copiedKey === 'platform' ? 'تم النسخ' : 'نسخ الرقم' }}
                            </button>
                        </div>
                        <p v-else class="mt-4 rounded-xl bg-[#FEF3C7] px-4 py-3 text-[13px] text-[#92400E]">
                            رقم الاستقبال غير مضبوط بعد. راجع الإدارة أو تواصل مع الدعم لإكمال الدفع.
                        </p>

                        <div v-if="merchantInstapay" class="mt-4 space-y-2">
                            <p class="text-[13px] text-[#6B7280]">المحفظة المسجلة لدينا كبائع:</p>
                            <div
                                class="flex flex-wrap items-center gap-2 rounded-xl bg-[#F8F8F7] px-4 py-3 ring-1 ring-[#E6E5E2]"
                            >
                                <span
                                    class="min-w-0 flex-1 font-inter text-[17px] font-semibold tracking-tight text-[#111111] break-all"
                                    dir="ltr"
                                >{{ merchantInstapay }}</span>
                                <button
                                    type="button"
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-[#111111] px-3 py-2 text-[13px] font-semibold text-white transition hover:bg-[#222222]"
                                    @click="copyMerchantWallet"
                                >
                                    <Check v-if="copiedKey === 'merchant'" class="h-3.5 w-3.5" stroke-width="2.5" />
                                    <Copy v-else class="h-3.5 w-3.5" stroke-width="2" />
                                    {{ copiedKey === 'merchant' ? 'تم النسخ' : 'نسخ الرقم' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-[#E6E5E2] bg-white p-5">
                        <p class="mb-3 text-[14px] font-semibold">إثبات التحويل</p>
                        <input
                            id="dokany-payment-proof"
                            type="file"
                            name="payment_proof"
                            accept="image/*"
                            class="sr-only"
                            @change="onFile"
                        />
                        <label
                            for="dokany-payment-proof"
                            class="flex w-full cursor-pointer select-none items-center justify-center gap-2 rounded-xl border border-dashed border-[#E6E5E2] bg-[#F8F8F7] py-8 transition active:scale-[0.99] hover:border-[#C8A97E]/45 hover:bg-[#F3EFE6]/40"
                        >
                            <ImagePlus class="h-5 w-5 shrink-0 text-[#C8A97E]" stroke-width="1.75" />
                            <span class="text-center text-[15px] font-medium text-[#4B5563]">
                                {{ form.payment_proof ? form.payment_proof.name : 'رفع Screenshot للإيصال' }}
                            </span>
                        </label>
                        <p class="mt-2 text-[12px] text-[#6B7280]">مطلوب رفع صورة الإيصال لتفعيل زر «لقد قمت بالدفع».</p>
                        <p class="mt-2 text-[12px] leading-relaxed text-[#6B7280]">
                            بعد الإرسال سيتم تسجيل خروجك تلقائياً حتى تتم مراجعة الطلب من الإدارة. يمكنك تسجيل الدخول مرة أخرى بعد الموافقة على الاشتراك.
                        </p>
                        <p v-if="form.errors.payment_proof" class="mt-2 text-[13px] text-red-600">
                            {{ form.errors.payment_proof }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="btn-primary-dark flex w-full items-center justify-center gap-2 rounded-xl py-4 text-[15px] font-semibold text-white transition enabled:hover:bg-[#222222] disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="!hasPaymentProof || form.processing"
                        @click="submitPaid"
                    >
                        <Loader2 v-if="form.processing" class="h-5 w-5 animate-spin" stroke-width="2" />
                        <span v-else>لقد قمت بالدفع</span>
                    </button>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.dokany-landing {
    -webkit-tap-highlight-color: transparent;
    font-family:
        'IBM Plex Sans Arabic',
        'Inter',
        -apple-system,
        BlinkMacSystemFont,
        'Segoe UI',
        sans-serif;
    font-size: 16px;
    line-height: 1.5;
}

.dokany-landing .font-inter {
    font-family: 'Inter', 'IBM Plex Sans Arabic', sans-serif;
}

.btn-primary-dark {
    background-color: #111111;
    box-shadow:
        0 1px 2px rgba(17, 17, 17, 0.06),
        0 6px 16px -4px rgba(17, 17, 17, 0.1);
}
</style>
