<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    ChevronLeft,
    Copy,
    Loader2,
    MapPin,
    UserRound,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import StorefrontDocumentHead from '@/components/StorefrontDocumentHead.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type CartLine = {
    productId: number;
    name: string;
    price: number;
    imageUrl: string | null;
    quantity: number;
};

const props = defineProps<{
    seller: {
        store_slug: string | null;
        name: string;
        logo_url: string | null;
        instapay_wallet: string | null;
        whatsapp_href: string | null;
    };
    storefrontUrl: string;
    checkoutPostPath: string;
    productCurrencyAr: string;
    paymentInstructions: string;
}>();

defineOptions({ layout: false as any });

const step = ref(1);
const cartLines = ref<CartLine[]>([]);
const geoLoading = ref(false);
const geoMessage = ref<string | null>(null);
const copiedWallet = ref(false);
let copyWalletTimeout: ReturnType<typeof setTimeout> | null = null;

const stepsMeta = [
    { n: 1, title: 'بياناتك', hint: 'الاسم والهاتف' },
    { n: 2, title: 'العنوان', hint: 'التوصيل' },
    { n: 3, title: 'المراجعة', hint: 'الدفع' },
] as const;

const hasWallet = computed(() => (props.seller.instapay_wallet ?? '').trim().length > 0);

const storageKey = computed(
    () => `dokany_store_cart_${props.seller.store_slug ?? 'unknown'}`,
);

const form = useForm({
    buyer_name: '',
    buyer_phone: '',
    buyer_address: '',
    buyer_maps_url: '',
});

function formatPrice(value: number): string {
    const n = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(value);
    return `${n} ${props.productCurrencyAr}`;
}

function lineTotal(line: CartLine): number {
    return Math.round(line.price * line.quantity * 100) / 100;
}

const cartSubtotal = computed(() =>
    Math.round(
        cartLines.value.reduce((s, l) => s + lineTotal(l), 0) * 100,
    ) / 100,
);

const stepHint = computed(() => `الخطوة ${step.value} من 3`);

async function copyWallet(): Promise<void> {
    const w = props.seller.instapay_wallet?.trim();
    if (!w) {
        return;
    }
    try {
        await navigator.clipboard.writeText(w);
        copiedWallet.value = true;
        if (copyWalletTimeout !== null) {
            clearTimeout(copyWalletTimeout);
        }
        copyWalletTimeout = setTimeout(() => {
            copiedWallet.value = false;
            copyWalletTimeout = null;
        }, 2000);
    } catch {
        copiedWallet.value = false;
    }
}

function canAdvanceFromStep1(): boolean {
    return form.buyer_name.trim().length > 0 && form.buyer_phone.trim().length > 0;
}

function canAdvanceFromStep2(): boolean {
    return form.buyer_address.trim().length > 0;
}

function googleMapsLink(lat: number, lng: number): string {
    return `https://www.google.com/maps?q=${lat},${lng}&ll=${lat},${lng}&z=17`;
}

function captureGeolocation(): void {
    geoMessage.value = null;

    if (typeof navigator === 'undefined' || !navigator.geolocation) {
        geoMessage.value = 'المتصفح لا يدعم تحديد الموقع. جرّب متصفحاً آخر أو أضف العنوان يدوياً.';
        return;
    }

    geoLoading.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            geoLoading.value = false;
            const { latitude, longitude } = pos.coords;
            form.buyer_maps_url = googleMapsLink(latitude, longitude);
            geoMessage.value = 'تم حفظ موقعك وسيُرسل مع الطلب.';
        },
        () => {
            geoLoading.value = false;
            geoMessage.value = 'تعذّر تحديد الموقع. تأكد من تفعيل صلاحية الموقع للموقع في إعدادات المتصفح.';
        },
        { enableHighAccuracy: true, timeout: 18_000, maximumAge: 60_000 },
    );
}

function nextStep(): void {
    if (step.value === 1 && !canAdvanceFromStep1()) {
        return;
    }
    if (step.value === 2 && !canAdvanceFromStep2()) {
        return;
    }
    if (step.value < 3) {
        step.value += 1;
    }
}

function submitOrder(): void {
    form.transform((data) => ({
        buyer_name: data.buyer_name.trim(),
        buyer_phone: data.buyer_phone.trim(),
        buyer_address: data.buyer_address.trim(),
        buyer_maps_url:
            data.buyer_maps_url.trim().length > 0 ? data.buyer_maps_url.trim() : null,
        lines: cartLines.value.map((l) => ({
            product_id: l.productId,
            quantity: l.quantity,
        })),
    })).post(props.checkoutPostPath, {
        onSuccess: () => {
            try {
                localStorage.removeItem(storageKey.value);
            } catch {
                /* ignore */
            }
        },
    });
}

onBeforeUnmount(() => {
    if (copyWalletTimeout !== null) {
        clearTimeout(copyWalletTimeout);
    }
});

onMounted(() => {
    try {
        const raw = localStorage.getItem(storageKey.value);
        if (!raw) {
            router.visit(props.storefrontUrl);
            return;
        }
        const parsed = JSON.parse(raw) as unknown;
        if (!Array.isArray(parsed)) {
            router.visit(props.storefrontUrl);
            return;
        }
        cartLines.value = parsed.filter(
            (row): row is CartLine =>
                row != null
                && typeof row === 'object'
                && typeof (row as CartLine).productId === 'number'
                && typeof (row as CartLine).name === 'string'
                && typeof (row as CartLine).price === 'number'
                && typeof (row as CartLine).quantity === 'number'
                && (row as CartLine).quantity > 0,
        );
    } catch {
        router.visit(props.storefrontUrl);
        return;
    }
    if (cartLines.value.length === 0) {
        router.visit(props.storefrontUrl);
    }
});
</script>

<template>
    <StorefrontDocumentHead
        :store-name="seller.name"
        :logo-url="seller.logo_url"
        title-suffix="إتمام الطلب"
    />

    <div
        class="storefront-root min-h-svh antialiased"
        dir="rtl"
        lang="ar"
    >
        <div
            class="pointer-events-none fixed inset-0 -z-10 bg-[#f7f5f2]"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none fixed inset-0 -z-10 opacity-[0.4]"
            style="
                background-image:
                    radial-gradient(ellipse 75% 45% at 100% 0%, rgb(200 169 126 / 0.14), transparent),
                    radial-gradient(ellipse 55% 35% at 0% 100%, rgb(17 17 17 / 0.04), transparent);
            "
            aria-hidden="true"
        />

        <header
            class="sticky top-0 z-40 border-b border-[#111111]/[0.07] bg-[#faf8f5]/[0.92] backdrop-blur-md"
        >
            <div class="mx-auto max-w-xl px-4 py-3.5 sm:px-6">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[13px] font-medium tabular-nums text-[#6b6560]">
                        {{ stepHint }}
                    </p>
                    <Link
                        :href="storefrontUrl"
                        class="inline-flex items-center gap-1 text-[13px] font-semibold text-[#7d623f] transition hover:text-[#5c4a32]"
                    >
                        <ChevronLeft class="size-4" stroke-width="2" />
                        للمتجر
                    </Link>
                </div>
                <nav
                    class="mt-4 flex items-center justify-center"
                    aria-label="مراحل إتمام الطلب"
                >
                    <ol class="flex items-center gap-0 sm:gap-1">
                        <template v-for="(s, idx) in stepsMeta" :key="s.n">
                            <li class="flex items-center">
                                <div class="flex flex-col items-center gap-1.5 px-1 sm:px-2">
                                    <div
                                        class="flex size-9 items-center justify-center rounded-full text-xs font-bold shadow-sm transition sm:size-10"
                                        :class="
                                            step === s.n
                                                ? 'bg-[#111111] text-white ring-2 ring-[#c8a97e] ring-offset-2 ring-offset-[#faf8f5]'
                                                : step > s.n
                                                  ? 'bg-[#c8a97e] text-[#111111]'
                                                  : 'bg-[#ebe7e1] text-[#9c9590]'
                                        "
                                    >
                                        <Check
                                            v-if="step > s.n"
                                            class="size-[1.05rem] sm:size-[1.1rem]"
                                            stroke-width="2.5"
                                            aria-hidden="true"
                                        />
                                        <span v-else class="tabular-nums">{{ s.n }}</span>
                                    </div>
                                    <span
                                        class="max-w-[4.25rem] text-center text-[10px] font-semibold leading-tight tracking-tight sm:max-w-none sm:text-[11px]"
                                        :class="step === s.n ? 'text-[#111111]' : 'text-[#8a8278]'"
                                    >
                                        {{ s.title }}
                                    </span>
                                    <span class="hidden text-[9px] text-[#ada59a] sm:block">{{ s.hint }}</span>
                                </div>
                            </li>
                            <li
                                v-if="idx < stepsMeta.length - 1"
                                class="mx-0.5 mb-6 hidden h-0.5 w-5 shrink-0 rounded-full sm:mx-1 sm:block sm:w-12"
                                :class="step > s.n ? 'bg-[#c8a97e]/75' : 'bg-[#111111]/10'"
                                aria-hidden="true"
                            />
                        </template>
                    </ol>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-xl px-4 pb-20 pt-6 sm:px-6 sm:pb-24 sm:pt-8">
            <div
                class="mb-6 flex items-center gap-3 rounded-2xl border border-[#111111]/[0.06] bg-white/80 px-4 py-3 shadow-[0_2px_20px_rgb(17_17_17_/_0.05)] backdrop-blur-sm sm:mb-8 sm:px-5 sm:py-4"
            >
                <div
                    class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-[0_4px_18px_rgb(17_17_17_/_0.07)] ring-1 ring-[#111111]/[0.06] sm:size-[3.25rem]"
                >
                    <img
                        v-if="seller.logo_url"
                        :src="seller.logo_url"
                        alt=""
                        class="size-full object-cover"
                    />
                    <span v-else class="text-xl font-semibold text-[#8a6d4a]">
                        {{ seller.name.trim().charAt(0) || '·' }}
                    </span>
                </div>
                <div class="min-w-0 flex-1 text-right">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[#b0a99f]">
                        إتمام الطلب
                    </p>
                    <h1 class="mt-0.5 truncate text-base font-bold tracking-tight text-[#111111] sm:text-lg">
                        {{ seller.name }}
                    </h1>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-[1.35rem] bg-white shadow-[0_4px_32px_rgb(17_17_17_/_0.07)] ring-1 ring-[#111111]/[0.05]"
            >
                <!-- Step 1 -->
                <div v-show="step === 1" class="space-y-5 px-5 py-6 sm:px-7 sm:py-8">
                    <div class="flex items-start gap-3 border-b border-[#111111]/[0.06] pb-5">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#f5f1eb] text-[#6b5340] ring-1 ring-[#c8a97e]/30"
                        >
                            <UserRound class="size-5" stroke-width="2" aria-hidden="true" />
                        </div>
                        <div class="min-w-0 text-right">
                            <h2 class="text-base font-bold text-[#111111] sm:text-[17px]">
                                بيانات التواصل
                            </h2>
                            <p class="mt-1 text-[13px] leading-relaxed text-[#6b6560]">
                                نستخدمها للتواصل معك بخصوص الطلب والتوصيل.
                            </p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="buyer_name" class="text-[13px] font-semibold text-[#3d3934]"
                            >الاسم بالكامل</Label
                        >
                        <Input
                            id="buyer_name"
                            v-model="form.buyer_name"
                            type="text"
                            autocomplete="name"
                            class="h-12 rounded-xl border-[#111111]/10 bg-[#faf9f7] text-[15px] shadow-none transition focus-visible:border-[#c8a97e]/50 focus-visible:ring-[#c8a97e]/25"
                            placeholder="مثال: أحمد محمد"
                        />
                        <InputError :message="form.errors.buyer_name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="buyer_phone" class="text-[13px] font-semibold text-[#3d3934]"
                            >رقم الهاتف</Label
                        >
                        <Input
                            id="buyer_phone"
                            v-model="form.buyer_phone"
                            type="tel"
                            dir="ltr"
                            class="h-12 rounded-xl border-[#111111]/10 bg-[#faf9f7] text-end text-[15px] shadow-none focus-visible:border-[#c8a97e]/50 focus-visible:ring-[#c8a97e]/25"
                            placeholder="01xxxxxxxxx"
                        />
                        <InputError :message="form.errors.buyer_phone" />
                    </div>
                </div>

                <!-- Step 2 -->
                <div v-show="step === 2" class="space-y-5 px-5 py-6 sm:px-7 sm:py-8">
                    <div class="flex items-start gap-3 border-b border-[#111111]/[0.06] pb-5">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#f5f1eb] text-[#6b5340] ring-1 ring-[#c8a97e]/30"
                        >
                            <MapPin class="size-5" stroke-width="2" aria-hidden="true" />
                        </div>
                        <div class="min-w-0 text-right">
                            <h2 class="text-base font-bold text-[#111111] sm:text-[17px]">
                                عنوان التوصيل
                            </h2>
                            <p class="mt-1 text-[13px] leading-relaxed text-[#6b6560]">
                                اكتب العنوان بدقة. يمكنك إرفاق موقعك من الخريطة (اختياري).
                            </p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="buyer_address" class="text-[13px] font-semibold text-[#3d3934]"
                            >العنوان التفصيلي</Label
                        >
                        <textarea
                            id="buyer_address"
                            v-model="form.buyer_address"
                            rows="4"
                            class="flex min-h-[7.5rem] w-full resize-y rounded-xl border border-[#111111]/10 bg-[#faf9f7] px-3.5 py-3 text-[15px] text-[#111111] placeholder:text-[#9c9590] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#c8a97e]/35"
                            placeholder="المنطقة، الشارع، الدور، علامة مميّزة قريبة…"
                        />
                        <InputError :message="form.errors.buyer_address" />
                    </div>
                    <div class="space-y-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="h-12 w-full gap-2 rounded-xl border-[#111111]/12 bg-[#faf9f7] text-[15px] font-medium text-[#111111] hover:bg-[#f0eeeb]"
                            :disabled="geoLoading"
                            @click="captureGeolocation"
                        >
                            <Loader2
                                v-if="geoLoading"
                                class="size-4 animate-spin"
                                stroke-width="2"
                            />
                            <MapPin v-else class="size-4 text-[#7d623f]" stroke-width="2" />
                            {{ geoLoading ? 'جاري تحديد الموقع…' : 'تحديد موقعي على الخريطة' }}
                        </Button>
                        <p
                            v-if="geoMessage"
                            class="text-[13px] leading-relaxed"
                            :class="form.buyer_maps_url.trim() ? 'text-emerald-800' : 'text-[#b4534a]'"
                        >
                            {{ geoMessage }}
                        </p>
                        <InputError :message="form.errors.buyer_maps_url" />
                    </div>
                </div>

                <!-- Step 3 -->
                <div v-show="step === 3" class="space-y-5 px-5 py-6 sm:px-7 sm:py-8">
                    <div class="flex items-start gap-3 border-b border-[#111111]/[0.06] pb-5">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#f5f1eb] text-[#6b5340] ring-1 ring-[#c8a97e]/30"
                        >
                            <Check class="size-5" stroke-width="2.5" aria-hidden="true" />
                        </div>
                        <div class="min-w-0 text-right">
                            <h2 class="text-base font-bold text-[#111111] sm:text-[17px]">
                                مراجعة الطلب والدفع
                            </h2>
                            <p class="mt-1 text-[13px] leading-relaxed text-[#6b6560]">
                                تأكد من البيانات. بعد التأكيد ستنتقل لصفحة رفع إيصال التحويل.
                            </p>
                        </div>
                    </div>

                    <section aria-label="عناصر السلة" class="space-y-2">
                        <h3 class="text-right text-[12px] font-semibold uppercase tracking-[0.18em] text-[#ada59a]">
                            المنتجات
                        </h3>
                        <ul class="space-y-2 rounded-xl bg-[#faf9f7] p-3 ring-1 ring-[#111111]/[0.06] sm:p-4">
                            <li
                                v-for="line in cartLines"
                                :key="line.productId"
                                class="flex gap-3 rounded-lg bg-white/90 px-2.5 py-2 ring-1 ring-[#111111]/[0.04] sm:px-3 sm:py-2.5"
                            >
                                <div
                                    class="size-12 shrink-0 overflow-hidden rounded-lg bg-[#eceae6] ring-1 ring-[#111111]/[0.05] sm:size-14"
                                >
                                    <img
                                        v-if="line.imageUrl"
                                        :src="line.imageUrl"
                                        alt=""
                                        class="size-full object-cover"
                                    />
                                </div>
                                <div class="min-w-0 flex-1 text-right">
                                    <p
                                        class="text-[13px] font-semibold leading-snug text-[#111111] sm:text-sm"
                                        dir="auto"
                                    >
                                        {{ line.name }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-[#8a8278]">
                                        الكمية:
                                        <span class="font-semibold tabular-nums text-[#5c5650]" dir="ltr">{{
                                            line.quantity
                                        }}</span>
                                    </p>
                                </div>
                                <p
                                    class="shrink-0 self-center text-[13px] font-bold tabular-nums text-[#6b5340] sm:text-sm"
                                    dir="ltr"
                                >
                                    {{ formatPrice(lineTotal(line)) }}
                                </p>
                            </li>
                        </ul>
                    </section>

                    <div
                        class="rounded-xl border border-[#c8a97e]/40 bg-gradient-to-br from-[#faf6f0] to-[#f3ebe0] px-4 py-3.5 shadow-[inset_0_1px_0_rgb(255_255_255_/_0.65)]"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-[14px] font-semibold text-[#111111]">المبلغ المستحق</span>
                            <span
                                class="text-xl font-bold tabular-nums tracking-tight text-[#111111] sm:text-2xl"
                                dir="ltr"
                                lang="en"
                            >{{ formatPrice(cartSubtotal) }}</span>
                        </div>
                        <p class="mt-2 text-right text-[11px] leading-relaxed text-[#7d6f5c]">
                            حوّل هذا المبلغ بالضبط عبر InstaPay في الخطوة التالية.
                        </p>
                    </div>

                    <section class="rounded-xl border border-[#111111]/[0.08] bg-white p-4 text-right ring-1 ring-[#111111]/[0.04] sm:p-5">
                        <h3 class="text-sm font-bold text-[#111111]">
                            خطوات تحويل المبلغ (InstaPay)
                        </h3>
                        <ol class="mt-3 space-y-3 text-[13px] leading-relaxed text-[#4a4540]">
                            <li class="flex gap-3">
                                <span
                                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-[#111111] text-[11px] font-bold text-white"
                                    >1</span
                                >
                                <span>بعد الضغط على «تأكيد الطلب والدفع» يُنشأ طلبك وتفتح صفحة لرفع إيصال التحويل.</span>
                            </li>
                            <li class="flex gap-3">
                                <span
                                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-[#111111] text-[11px] font-bold text-white"
                                    >2</span
                                >
                                <span>من تطبيق InstaPay أو تطبيق بنكك، اختر تحويل المبلغ إلى الهاتف أو المحفظة المعروضة.</span>
                            </li>
                            <li class="flex gap-3">
                                <span
                                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-[#111111] text-[11px] font-bold text-white"
                                    >3</span
                                >
                                <span>أرسل <strong class="font-semibold text-[#111111]">نفس المبلغ أعلاه بالضبط من غير تغيير</strong> حتى لا يتأخر تأكيد الطلب.</span>
                            </li>
                            <li class="flex gap-3">
                                <span
                                    class="flex size-6 shrink-0 items-center justify-center rounded-full bg-[#111111] text-[11px] font-bold text-white"
                                    >4</span
                                >
                                <span>احفظ لقطة شاشة واضحة للإيصال وارفعها في الصفحة التالية.</span>
                            </li>
                        </ol>
                        <p
                            class="mt-4 rounded-lg border border-[#111111]/[0.06] bg-[#faf9f7] px-3 py-2.5 text-[12px] leading-relaxed text-[#6b6560]"
                        >
                            {{ paymentInstructions }}
                        </p>
                    </section>

                    <div
                        v-if="hasWallet"
                        class="rounded-xl bg-[#faf9f7] p-4 ring-1 ring-[#111111]/[0.06] sm:p-5"
                    >
                        <p class="text-[13px] font-semibold text-[#111111]">
                            رقم المحفظة / InstaPay للمتجر
                        </p>
                        <p
                            class="mt-2 break-all font-mono text-[15px] font-semibold tabular-nums text-[#111111] sm:text-base"
                            dir="ltr"
                        >
                            {{ seller.instapay_wallet }}
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="mt-3 h-9 gap-1.5 rounded-lg border-[#111111]/12 text-[13px]"
                            @click="copyWallet"
                        >
                            <Copy class="size-3.5" stroke-width="2" />
                            {{ copiedWallet ? 'تم النسخ' : 'نسخ الرقم' }}
                        </Button>
                    </div>
                    <div
                        v-else
                        class="rounded-xl border border-amber-500/35 bg-amber-50/90 px-4 py-3 text-[13px] leading-relaxed text-amber-950"
                    >
                        <p>لم يُضف رقم InstaPay لهذا المتجر بعد.</p>
                        <p v-if="seller.whatsapp_href" class="mt-2">
                            يمكنك
                            <a
                                :href="seller.whatsapp_href"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-semibold underline underline-offset-2"
                            >التواصل عبر واتساب</a>
                            لمعرفة طريقة الدفع.
                        </p>
                    </div>

                    <dl
                        class="space-y-3 rounded-xl bg-[#faf9f7] p-4 text-[13px] ring-1 ring-[#111111]/[0.06] sm:p-5"
                    >
                        <div class="flex flex-col gap-0.5 border-b border-[#111111]/[0.06] pb-3 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
                            <dt class="shrink-0 font-medium text-[#8a8278]">الاسم</dt>
                            <dd class="text-right font-semibold text-[#111111] sm:max-w-[60%]" dir="auto">
                                {{ form.buyer_name.trim() || '—' }}
                            </dd>
                        </div>
                        <div class="flex flex-col gap-0.5 border-b border-[#111111]/[0.06] pb-3 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
                            <dt class="shrink-0 font-medium text-[#8a8278]">الهاتف</dt>
                            <dd class="text-left font-semibold tabular-nums sm:text-end" dir="ltr">
                                {{ form.buyer_phone.trim() || '—' }}
                            </dd>
                        </div>
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                            <dt class="shrink-0 font-medium text-[#8a8278]">العنوان</dt>
                            <dd class="text-right font-semibold leading-relaxed text-[#111111] sm:max-w-[75%]">
                                {{ form.buyer_address.trim() || '—' }}
                            </dd>
                        </div>
                        <div
                            v-if="form.buyer_maps_url.trim()"
                            class="flex flex-col gap-0.5 pt-1 sm:flex-row sm:items-baseline sm:justify-between"
                        >
                            <dt class="shrink-0 font-medium text-[#8a8278]">الخريطة</dt>
                            <dd class="font-semibold text-[#111111]">مُرفقة مع الطلب</dd>
                        </div>
                    </dl>
                    <InputError :message="(form.errors as any).lines" />
                </div>

                <div
                    class="border-t border-[#111111]/[0.06] bg-[#faf9f7]/80 px-5 py-5 sm:px-7 sm:py-6"
                >
                    <Button
                        v-if="step < 3"
                        type="button"
                        class="h-12 w-full rounded-xl bg-[#111111] text-[15px] font-semibold text-white shadow-none hover:bg-[#1f1f1f]"
                        :disabled="step === 1 && !canAdvanceFromStep1"
                        @click="nextStep"
                    >
                        التالي
                        <ArrowRight class="me-1 size-4" stroke-width="2" />
                    </Button>
                    <Button
                        v-else
                        type="button"
                        class="h-12 w-full rounded-xl bg-[#111111] text-[15px] font-semibold text-white shadow-none hover:bg-[#1f1f1f]"
                        :disabled="form.processing"
                        @click="submitOrder"
                    >
                        {{ form.processing ? 'جاري الإرسال…' : 'تأكيد الطلب والانتقال للدفع' }}
                    </Button>
                </div>
            </div>
        </main>
    </div>
</template>
