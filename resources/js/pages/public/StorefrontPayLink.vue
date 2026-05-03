<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, Loader2, MapPin } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import StorefrontDocumentHead from '@/components/StorefrontDocumentHead.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    seller: {
        name: string;
        logo_url: string | null;
        store_slug: string | null;
    };
    link: {
        uuid: string;
        amount: string;
        currency_label_ar: string;
        currency_label_en: string;
    };
    storefrontUrl: string;
    payLinkPostPath: string;
    productCurrencyAr: string;
}>();

defineOptions({ layout: false });

const geoLoading = ref(false);
const geoMessage = ref<string | null>(null);

const form = useForm({
    buyer_name: '',
    buyer_phone: '',
    buyer_address: '',
    buyer_maps_url: '',
});

function formatMoney(amount: string, currency: string): string {
    const n = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(amount));
    return `${n} ${currency}`;
}

const displayAmount = computed(() => formatMoney(props.link.amount, props.link.currency_label_ar));

function googleMapsLink(lat: number, lng: number): string {
    return `https://www.google.com/maps?q=${lat},${lng}&ll=${lat},${lng}&z=17`;
}

function captureGeolocation(): void {
    geoMessage.value = null;
    if (typeof navigator === 'undefined' || !navigator.geolocation) {
        geoMessage.value = 'المتصفح لا يدعم تحديد الموقع.';
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
            geoMessage.value = 'تعذّر تحديد الموقع. تأكد من صلاحية الموقع.';
        },
        { enableHighAccuracy: true, timeout: 18_000, maximumAge: 60_000 },
    );
}

function submitBuyer(): void {
    form.transform((data) => ({
        buyer_name: data.buyer_name.trim(),
        buyer_phone: data.buyer_phone.trim(),
        buyer_address: data.buyer_address.trim(),
        buyer_maps_url: data.buyer_maps_url.trim().length > 0 ? data.buyer_maps_url.trim() : null,
    })).post(props.payLinkPostPath);
}
</script>

<template>
    <StorefrontDocumentHead
        :store-name="seller.name"
        :logo-url="seller.logo_url"
        title-suffix="دفع عبر رابط"
    />

    <div class="storefront-root min-h-svh antialiased" dir="rtl" lang="ar">
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[#f7f5f2]" aria-hidden="true" />
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
            <div class="mx-auto flex max-w-xl items-center justify-between gap-3 px-4 py-3.5 sm:px-6">
                <Link
                    :href="storefrontUrl"
                    class="inline-flex items-center gap-1 text-[13px] font-semibold text-[#7d623f] hover:text-[#5c4a32]"
                >
                    <ChevronLeft class="size-4" stroke-width="2" />
                    للمتجر
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-xl px-4 pb-16 pt-6 sm:px-6 sm:pb-20 sm:pt-8">
            <div
                class="mb-6 flex items-center gap-3 rounded-2xl border border-[#111111]/[0.06] bg-white/80 px-4 py-3 shadow-sm backdrop-blur-sm sm:px-5 sm:py-4"
            >
                <div
                    class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-[#111111]/[0.06] sm:size-14"
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
                        دفع عبر رابط
                    </p>
                    <h1 class="mt-0.5 truncate text-base font-bold text-[#111111] sm:text-lg">
                        {{ seller.name }}
                    </h1>
                </div>
            </div>

            <div
                class="mb-6 rounded-[1.25rem] border border-[#c8a97e]/40 bg-gradient-to-br from-[#faf6f0] to-[#f3ebe0] px-4 py-4 shadow-inner sm:px-5 sm:py-5"
            >
                <p class="text-[13px] font-medium text-[#7d6f5c]">
                    المبلغ المطلوب تحويله عبر InstaPay
                </p>
                <p
                    class="mt-1 text-2xl font-bold tabular-nums tracking-tight text-[#111111] sm:text-3xl"
                    dir="ltr"
                    lang="en"
                >
                    {{ displayAmount }}
                </p>
                <p class="mt-2 text-[12px] leading-relaxed text-[#6b6560]">
                    بعد إرسال بياناتك ستنتقل لصفحة رفع إيصال التحويل كأي طلب عادي من المتجر.
                </p>
            </div>

            <div
                class="overflow-hidden rounded-[1.35rem] bg-white shadow-[0_4px_32px_rgb(17_17_17_/_0.07)] ring-1 ring-[#111111]/[0.05]"
            >
                <div class="space-y-5 px-5 py-6 sm:px-7 sm:py-8">
                    <div class="flex items-start gap-3 border-b border-[#111111]/[0.06] pb-5">
                        <div class="min-w-0 flex-1 text-right">
                            <h2 class="text-base font-bold text-[#111111] sm:text-[17px]">
                                بياناتك للتواصل والتوصيل
                            </h2>
                            <p class="mt-1 text-[13px] leading-relaxed text-[#6b6560]">
                                مطلوبة ليتم تأكيد الدفع وتجهيز الطلب.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="pl_buyer_name" class="text-[13px] font-semibold text-[#3d3934]"
                            >الاسم بالكامل</Label
                        >
                        <Input
                            id="pl_buyer_name"
                            v-model="form.buyer_name"
                            type="text"
                            autocomplete="name"
                            class="h-12 rounded-xl border-[#111111]/10 bg-[#faf9f7] text-[15px]"
                            placeholder="اسمك"
                        />
                        <InputError :message="form.errors.buyer_name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="pl_buyer_phone" class="text-[13px] font-semibold text-[#3d3934]"
                            >رقم الهاتف</Label
                        >
                        <Input
                            id="pl_buyer_phone"
                            v-model="form.buyer_phone"
                            type="tel"
                            dir="ltr"
                            class="h-12 rounded-xl border-[#111111]/10 bg-[#faf9f7] text-end text-[15px]"
                            placeholder="01xxxxxxxxx"
                        />
                        <InputError :message="form.errors.buyer_phone" />
                    </div>
                    <div class="space-y-2">
                        <Label for="pl_buyer_address" class="text-[13px] font-semibold text-[#3d3934]"
                            >العنوان</Label
                        >
                        <textarea
                            id="pl_buyer_address"
                            v-model="form.buyer_address"
                            rows="3"
                            class="flex min-h-[6rem] w-full resize-y rounded-xl border border-[#111111]/10 bg-[#faf9f7] px-3.5 py-3 text-[15px] text-[#111111] placeholder:text-[#9c9590] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#c8a97e]/35"
                            placeholder="المنطقة، الشارع…"
                        />
                        <InputError :message="form.errors.buyer_address" />
                    </div>
                    <div class="space-y-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="h-12 w-full gap-2 rounded-xl border-[#111111]/12 bg-[#faf9f7]"
                            :disabled="geoLoading"
                            @click="captureGeolocation"
                        >
                            <Loader2 v-if="geoLoading" class="size-4 animate-spin" />
                            <MapPin v-else class="size-4 text-[#7d623f]" stroke-width="2" />
                            {{ geoLoading ? 'جاري تحديد الموقع…' : 'تحديد موقعي (اختياري)' }}
                        </Button>
                        <p
                            v-if="geoMessage"
                            class="text-[13px]"
                            :class="form.buyer_maps_url.trim() ? 'text-emerald-800' : 'text-[#b4534a]'"
                        >
                            {{ geoMessage }}
                        </p>
                        <InputError :message="form.errors.buyer_maps_url" />
                    </div>
                </div>
                <div class="border-t border-[#111111]/[0.06] bg-[#faf9f7]/80 px-5 py-5 sm:px-7 sm:py-6">
                    <Button
                        type="button"
                        class="h-12 w-full rounded-xl bg-[#111111] text-[15px] font-semibold text-white hover:bg-[#1f1f1f]"
                        :disabled="form.processing"
                        @click="submitBuyer"
                    >
                        {{ form.processing ? 'جاري المتابعة…' : 'متابعة لصفحة الدفع وInstaPay' }}
                    </Button>
                </div>
            </div>
        </main>
    </div>
</template>
