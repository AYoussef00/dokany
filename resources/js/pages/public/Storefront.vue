<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import {
    Facebook,
    Heart,
    Instagram,
    Menu,
    Minus,
    Plus,
    Share2,
    ShoppingBag,
    Trash2,
    Twitter,
    Youtube,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import StorefrontDocumentHead from '@/components/StorefrontDocumentHead.vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';

type PublicProduct = {
    id: number;
    name: string;
    description: string;
    price: number;
    storefront_category?: string;
    storefront_category_label?: string;
    images?: { id: number; url: string }[];
};

type CartLine = {
    productId: number;
    name: string;
    price: number;
    imageUrl: string | null;
    quantity: number;
};

type SocialKey = 'instagram' | 'tiktok' | 'facebook' | 'youtube' | 'x';

const socialOrder: SocialKey[] = ['instagram', 'tiktok', 'facebook', 'youtube', 'x'];

const socialAriaLabels: Record<SocialKey, string> = {
    instagram: 'إنستغرام',
    tiktok: 'تيك توك',
    facebook: 'فيسبوك',
    youtube: 'يوتيوب',
    x: 'إكس',
};

const props = defineProps<{
    seller: {
        store_slug: string | null;
        name: string;
        logo_url: string | null;
        whatsapp_phone: string;
        whatsapp_href: string | null;
        hero_primary: string | null;
        hero_secondary: string | null;
        hero_banner_urls: string[];
        social: Partial<Record<SocialKey, string>>;
    };
    products: PublicProduct[];
    categoryFilters: { value: string; label: string }[];
    productCurrencyEn: string;
    productCurrencyAr: string;
    checkoutPath: string;
}>();

const page = usePage<{
    flash: {
        success?: string | null;
        payment_receipt_received?: boolean;
    };
}>();

const socialEntries = computed(() => {
    const s = props.seller.social;
    return socialOrder
        .filter((k) => typeof s[k] === 'string' && s[k]!.length > 0)
        .map((k) => ({ key: k, url: s[k]!, label: socialAriaLabels[k] }));
});

const hasHeroPrimary = computed(() => props.seller.hero_primary != null && props.seller.hero_primary.trim() !== '');

const hasHeroSecondary = computed(() => props.seller.hero_secondary != null && props.seller.hero_secondary.trim() !== '');

const heroBannerUrls = computed(() => props.seller.hero_banner_urls ?? []);

const hasHeroBanners = computed(() => heroBannerUrls.value.length > 0);

const activeBannerIndex = ref(0);

const heroTitle = computed(() =>
    hasHeroPrimary.value ? props.seller.hero_primary!.trim() : props.seller.name,
);

const heroSubtitle = computed(() =>
    hasHeroSecondary.value
        ? props.seller.hero_secondary!.trim()
        : 'تشكيلة مختارة بعناية. جودة وتصميم يليق بك.',
);

const activeStorefrontCategory = ref<string | null>(null);

const filteredStorefrontProducts = computed(() => {
    const key = activeStorefrontCategory.value;
    if (key === null) {
        return props.products;
    }
    return props.products.filter((p) => (p.storefront_category ?? 'new_in') === key);
});

let bannerRotateTimer: ReturnType<typeof setInterval> | null = null;

function clearBannerTimer(): void {
    if (bannerRotateTimer !== null) {
        clearInterval(bannerRotateTimer);
        bannerRotateTimer = null;
    }
}

function stepBanner(delta: number): void {
    const n = heroBannerUrls.value.length;
    if (n <= 0) {
        return;
    }
    activeBannerIndex.value = (activeBannerIndex.value + delta + n) % n;
    startBannerRotation();
}

function goBanner(i: number): void {
    const n = heroBannerUrls.value.length;
    if (n <= 0 || i < 0 || i >= n) {
        return;
    }
    activeBannerIndex.value = i;
    startBannerRotation();
}

/** تمرير أفقي على الموبايل (الأسهم مخفية تحت sm) */
const heroSwipeStartX = ref<number | null>(null);

function onHeroBannerTouchStart(e: TouchEvent): void {
    if (heroBannerUrls.value.length <= 1) {
        return;
    }
    heroSwipeStartX.value = e.touches[0]?.clientX ?? null;
}

function onHeroBannerTouchEnd(e: TouchEvent): void {
    const start = heroSwipeStartX.value;
    heroSwipeStartX.value = null;
    if (start == null || heroBannerUrls.value.length <= 1) {
        return;
    }
    const end = e.changedTouches[0]?.clientX ?? start;
    const dx = end - start;
    if (Math.abs(dx) < 48) {
        return;
    }
    if (dx < 0) {
        stepBanner(1);
    } else {
        stepBanner(-1);
    }
}

function startBannerRotation(): void {
    clearBannerTimer();
    if (heroBannerUrls.value.length <= 1) {
        return;
    }
    bannerRotateTimer = setInterval(() => {
        stepBanner(1);
    }, 5500);
}

watch(heroBannerUrls, (urls) => {
    activeBannerIndex.value = 0;
    if (urls.length <= 1) {
        clearBannerTimer();
        return;
    }
    startBannerRotation();
});

const hasSocialLinks = computed(() => socialEntries.value.length > 0);

const storefrontHeadPreloadImages = computed(() => {
    const urls: string[] = [];
    const logo = props.seller.logo_url?.trim();
    if (logo) {
        urls.push(logo);
    }
    const firstBanner = heroBannerUrls.value[0]?.trim();
    if (firstBanner) {
        urls.push(firstBanner);
    }

    return urls;
});

defineOptions({ layout: false as any });

const cartOpen = ref(false);
const menuOpen = ref(false);
const cartLines = ref<CartLine[]>([]);

function scrollToProducts(): void {
    document.getElementById('products')?.scrollIntoView({ behavior: 'smooth' });
}

async function shareStore(): Promise<void> {
    const url = window.location.href;
    const title = props.seller.name;
    try {
        if (typeof navigator !== 'undefined' && navigator.share) {
            await navigator.share({ title, url });
            return;
        }
    } catch {
        /* dismissed or unavailable */
    }
    try {
        await navigator.clipboard.writeText(url);
        const [{ default: Swal }] = await Promise.all([
            import('sweetalert2'),
            import('sweetalert2/dist/sweetalert2.min.css'),
        ]);
        void Swal.fire({
            toast: true,
            position: 'top',
            icon: 'success',
            title: 'تم نسخ رابط المتجر',
            timer: 2000,
            showConfirmButton: false,
        });
    } catch {
        /* ignore */
    }
}

const storageKey = computed(
    () => `dokany_store_cart_${props.seller.store_slug ?? 'unknown'}`,
);

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

const cartCount = computed(() => cartLines.value.reduce((s, l) => s + l.quantity, 0));

const cartSubtotal = computed(() =>
    Math.round(
        cartLines.value.reduce((s, l) => s + lineTotal(l), 0) * 100,
    ) / 100,
);

const showWhatsApp = computed(() => props.seller.whatsapp_href != null && props.seller.whatsapp_href !== '');

function addToCart(product: PublicProduct): void {
    const found = cartLines.value.find((l) => l.productId === product.id);
    if (found) {
        found.quantity += 1;
        return;
    }
    cartLines.value.push({
        productId: product.id,
        name: product.name,
        price: product.price,
        imageUrl: product.images?.[0]?.url ?? null,
        quantity: 1,
    });
}

function bumpQuantity(productId: number, delta: number): void {
    const line = cartLines.value.find((l) => l.productId === productId);
    if (!line) {
        return;
    }
    line.quantity += delta;
    if (line.quantity < 1) {
        cartLines.value = cartLines.value.filter((l) => l.productId !== productId);
    }
}

function removeLine(productId: number): void {
    cartLines.value = cartLines.value.filter((l) => l.productId !== productId);
}

function clearCart(): void {
    cartLines.value = [];
}

async function showPaymentReceiptReceivedSwal(): Promise<void> {
    const [{ default: Swal }] = await Promise.all([
        import('sweetalert2'),
        import('sweetalert2/dist/sweetalert2.min.css'),
    ]);
    void Swal.fire({
        icon: 'success',
        title: 'تم استلام إيصال الدفع',
        html:
            '<div dir="rtl" class="swal-luxury-body" style="text-align:right;font-size:15px;line-height:1.75;color:#5c5650;margin:0.25rem 0 0">'
            + 'طلبك قيد المتابعة لدى المتجر. يمكنك إغلاق هذه الصفحة أو العودة للمتجر.'
            + '</div>',
        confirmButtonText: 'متابعة التسوق',
        reverseButtons: true,
        focusConfirm: true,
        customClass: {
            popup: 'swal-luxury-storefront',
            confirmButton: 'swal-luxury-confirm',
        },
        buttonsStyling: false,
        showClass: { popup: 'swal-luxury-show' },
        hideClass: { popup: 'swal-luxury-hide' },
        didOpen: (popup) => {
            popup.setAttribute('dir', 'rtl');
        },
    });
}

onMounted(() => {
    startBannerRotation();
    try {
        const raw = localStorage.getItem(storageKey.value);
        if (raw) {
            const parsed = JSON.parse(raw) as unknown;
            if (Array.isArray(parsed)) {
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
            }
        }
    } catch {
        /* ignore */
    }

    if (page.props.flash?.payment_receipt_received === true) {
        void showPaymentReceiptReceivedSwal();
    }
});

watch(
    cartLines,
    (v) => {
        try {
            localStorage.setItem(storageKey.value, JSON.stringify(v));
        } catch {
            /* ignore */
        }
    },
    { deep: true },
);

onUnmounted(() => {
    clearBannerTimer();
});

</script>

<template>
    <StorefrontDocumentHead
        :store-name="seller.name"
        :logo-url="seller.logo_url"
        :preload-image-urls="storefrontHeadPreloadImages"
    />

    <div class="storefront-root min-h-svh bg-neutral-50 antialiased" dir="rtl" lang="ar">
        <div class="mx-auto min-h-svh max-w-[390px] bg-white shadow-sm">
            <header
                class="sticky top-0 z-50 border-b border-neutral-100 bg-white px-4 py-3"
            >
                <div class="flex items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full"
                            :class="seller.logo_url ? 'bg-white ring-1 ring-neutral-200' : 'bg-black'"
                        >
                            <img
                                v-if="seller.logo_url"
                                :src="seller.logo_url"
                                alt=""
                                class="size-full object-cover"
                                fetchpriority="high"
                                decoding="async"
                            />
                            <ShoppingBag
                                v-else
                                class="size-5 text-white"
                                stroke-width="1.5"
                                aria-hidden="true"
                            />
                        </div>
                        <div class="min-w-0 text-start">
                            <h1
                                id="storefront-heading"
                                class="truncate text-sm font-medium tracking-tight text-neutral-900"
                            >
                                {{ seller.name }}
                            </h1>
                            <p class="text-[10px] tracking-wide text-neutral-500">
                                متجر إلكتروني
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <button
                            type="button"
                            class="rounded-full p-1.5 text-neutral-700 transition-colors hover:bg-neutral-50"
                            aria-label="مشاركة رابط المتجر"
                            @click="shareStore"
                        >
                            <Share2 class="size-5" stroke-width="1.5" />
                        </button>
                        <button
                            type="button"
                            class="relative rounded-full p-1.5 text-neutral-700 transition-colors hover:bg-neutral-50"
                            aria-label="عرض سلة التسوّق"
                            @click="cartOpen = true"
                        >
                            <ShoppingBag class="size-5" stroke-width="1.5" />
                            <span
                                v-if="cartCount > 0"
                                class="absolute -top-0.5 flex min-h-[1.1rem] min-w-[1.1rem] items-center justify-center rounded-full bg-black px-1 text-[9px] font-bold tabular-nums text-white"
                                dir="ltr"
                            >
                                {{ cartCount > 99 ? '99+' : cartCount }}
                            </span>
                        </button>
                        <button
                            type="button"
                            class="rounded-full p-1.5 text-neutral-700 transition-colors hover:bg-neutral-50"
                            aria-label="قائمة المتجر"
                            @click="menuOpen = true"
                        >
                            <Menu class="size-5" stroke-width="1.5" />
                        </button>
                    </div>
                </div>
            </header>

            <section class="relative overflow-hidden" aria-labelledby="storefront-hero-title">
                <div
                    class="relative isolate min-h-[220px]"
                    @touchstart.passive="onHeroBannerTouchStart"
                    @touchend.passive="onHeroBannerTouchEnd"
                >
                    <div v-if="hasHeroBanners" class="absolute inset-0">
                        <Transition
                            mode="out-in"
                            enter-active-class="transition-opacity duration-300 ease-out"
                            leave-active-class="transition-opacity duration-200 ease-in"
                            enter-from-class="opacity-0"
                            leave-to-class="opacity-0"
                        >
                            <img
                                v-if="heroBannerUrls[activeBannerIndex]"
                                :key="`${activeBannerIndex}-${heroBannerUrls[activeBannerIndex]}`"
                                :src="heroBannerUrls[activeBannerIndex]"
                                :alt="`بانر ${activeBannerIndex + 1}`"
                                class="absolute inset-0 size-full object-cover object-center"
                                fetchpriority="high"
                                decoding="async"
                            />
                        </Transition>
                    </div>
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-neutral-900/95 via-neutral-800/95 to-neutral-900/95"
                        aria-hidden="true"
                    />
                    <div class="relative z-[1] px-6 py-12 text-center">
                        <h2
                            id="storefront-hero-title"
                            class="mb-2 text-pretty text-2xl tracking-tight text-white"
                        >
                            {{ heroTitle }}
                        </h2>
                        <p
                            class="mx-auto mb-6 max-w-[280px] text-pretty text-sm leading-relaxed text-neutral-300"
                        >
                            {{ heroSubtitle }}
                        </p>
                        <button
                            type="button"
                            class="rounded-full bg-white px-8 py-3 text-sm font-medium text-black transition-all hover:bg-neutral-100 active:scale-95"
                            @click="scrollToProducts"
                        >
                            تسوق الآن
                        </button>
                    </div>
                    <div
                        v-if="heroBannerUrls.length > 1"
                        class="relative z-[2] flex justify-center pb-4"
                        role="tablist"
                        aria-label="اختيار شريحة البانر"
                    >
                        <div
                            class="flex gap-1.5 rounded-full bg-white/15 px-2.5 py-1.5 backdrop-blur-sm"
                        >
                            <button
                                v-for="(_, i) in heroBannerUrls"
                                :key="`hero-dot-${i}`"
                                type="button"
                                role="tab"
                                :aria-selected="i === activeBannerIndex"
                                class="h-1.5 rounded-full transition-all duration-300"
                                :class="
                                    i === activeBannerIndex
                                        ? 'w-5 bg-white shadow-sm'
                                        : 'w-1.5 bg-white/55 hover:bg-white/85'
                                "
                                :aria-label="`شريحة ${i + 1}`"
                                @click="goBanner(i)"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section class="overflow-x-auto px-4 py-6" aria-label="تصنيفات">
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="whitespace-nowrap rounded-full border px-4 py-2 text-sm transition-all active:scale-95"
                        :class="
                            activeStorefrontCategory === null
                                ? 'border-neutral-900 bg-neutral-900 text-white hover:bg-neutral-800'
                                : 'border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50'
                        "
                        @click="activeStorefrontCategory = null"
                    >
                        الكل
                    </button>
                    <button
                        v-for="cat in categoryFilters"
                        :key="cat.value"
                        type="button"
                        class="whitespace-nowrap rounded-full border px-4 py-2 text-sm transition-all active:scale-95"
                        :class="
                            activeStorefrontCategory === cat.value
                                ? 'border-neutral-900 bg-neutral-900 text-white hover:bg-neutral-800'
                                : 'border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50'
                        "
                        @click="
                            activeStorefrontCategory =
                                activeStorefrontCategory === cat.value ? null : cat.value
                        "
                    >
                        {{ cat.label }}
                    </button>
                </div>
            </section>

            <main class="px-4 pb-8">
                <section id="products" aria-label="قائمة المنتجات">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-medium tracking-tight text-neutral-900">
                            منتجات مميزة
                        </h3>
                        <span class="text-xs text-neutral-500 tabular-nums">
                            <template v-if="activeStorefrontCategory !== null">
                                {{ filteredStorefrontProducts.length }} من {{ products.length }}
                            </template>
                            <template v-else>{{ products.length }} صنف</template>
                        </span>
                    </div>

                    <p
                        v-if="products.length === 0"
                        class="py-16 text-center text-sm text-neutral-500"
                    >
                        لا توجد منتجات في هذا المتجر حالياً.
                    </p>

                    <p
                        v-else-if="filteredStorefrontProducts.length === 0"
                        class="py-16 text-center text-sm text-neutral-500"
                    >
                        لا توجد منتجات في هذا الصنف.
                    </p>

                    <ul v-else class="grid list-none grid-cols-2 gap-4">
                        <li v-for="(p, idx) in filteredStorefrontProducts" :key="p.id" class="group min-w-0">
                            <article class="cursor-pointer">
                                <div
                                    class="relative mb-3 aspect-[3/4] overflow-hidden rounded-2xl bg-neutral-100"
                                >
                                    <img
                                        v-if="p.images?.[0]?.url"
                                        :src="p.images?.[0]?.url"
                                        :alt="p.name"
                                        class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                                        :loading="idx < 2 ? 'eager' : 'lazy'"
                                        :fetchpriority="idx === 0 ? 'high' : undefined"
                                        decoding="async"
                                        sizes="(max-width: 390px) 50vw, 195px"
                                    />
                                    <div
                                        v-else
                                        class="flex size-full items-center justify-center px-2 text-center text-[10px] text-neutral-400"
                                    >
                                        بدون صورة
                                    </div>
                                    <span
                                        class="pointer-events-none absolute top-2 start-2 flex size-8 items-center justify-center rounded-full bg-white/90 opacity-0 shadow-sm backdrop-blur-sm transition-opacity group-hover:opacity-100"
                                        aria-hidden="true"
                                    >
                                        <Heart class="size-4 text-neutral-700" stroke-width="1.5" />
                                    </span>
                                </div>
                                <div class="px-1">
                                    <h4
                                        class="mb-1 line-clamp-1 text-sm font-medium text-neutral-900"
                                        dir="auto"
                                    >
                                        {{ p.name }}
                                    </h4>
                                    <p
                                        v-if="p.storefront_category_label"
                                        class="mb-1 line-clamp-1 text-[10px] text-neutral-500"
                                    >
                                        {{ p.storefront_category_label }}
                                    </p>
                                    <div class="flex items-center justify-between gap-2">
                                        <span
                                            class="text-sm font-medium tabular-nums text-neutral-900"
                                            dir="ltr"
                                        >
                                            {{ formatPrice(p.price) }}
                                        </span>
                                        <button
                                            type="button"
                                            class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-black text-white transition hover:bg-neutral-800 active:scale-95"
                                            aria-label="إضافة للسلة"
                                            @click.stop="addToCart(p)"
                                        >
                                            <Plus class="size-4" stroke-width="2" />
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </li>
                    </ul>
                </section>
            </main>

            <section
                class="border-t border-neutral-100 px-4 pb-10 pt-6"
                aria-label="مميزات التسوق"
            >
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="mb-1 text-lg" aria-hidden="true">🚚</div>
                        <p class="text-[10px] leading-tight text-neutral-600">
                            شحن سريع<br />إلى بابك
                        </p>
                    </div>
                    <div>
                        <div class="mb-1 text-lg" aria-hidden="true">↩️</div>
                        <p class="text-[10px] leading-tight text-neutral-600">
                            تواصل سهل<br />مع البائع
                        </p>
                    </div>
                    <div>
                        <div class="mb-1 text-lg" aria-hidden="true">✓</div>
                        <p class="text-[10px] leading-tight text-neutral-600">
                            دفع آمن<br />وموثوق
                        </p>
                    </div>
                </div>
            </section>

            <footer class="border-t border-neutral-100 px-4 py-6">
                <p
                    class="text-center text-[10px] font-medium uppercase tracking-widest text-neutral-400"
                    dir="auto"
                >
                    {{ seller.name }}
                </p>
            </footer>
        </div>

        <Sheet v-model:open="menuOpen">
            <SheetContent
                side="right"
                class="w-full border-0 bg-white sm:max-w-xs"
                dir="rtl"
            >
                <SheetHeader class="space-y-1 border-b border-neutral-100 pb-4 text-right">
                    <SheetTitle class="text-lg font-semibold text-neutral-900">
                        {{ seller.name }}
                    </SheetTitle>
                    <SheetDescription class="text-[13px] text-neutral-500">
                        روابط وخدمات المتجر
                    </SheetDescription>
                </SheetHeader>
                <nav class="mt-6 flex flex-col gap-2 text-right" aria-label="قائمة المتجر">
                    <button
                        type="button"
                        class="rounded-xl px-3 py-3 text-start text-sm font-medium text-neutral-800 transition hover:bg-neutral-50"
                        @click="
                            menuOpen = false;
                            scrollToProducts();
                        "
                    >
                        المنتجات
                    </button>
                    <button
                        type="button"
                        class="rounded-xl px-3 py-3 text-start text-sm font-medium text-neutral-800 transition hover:bg-neutral-50"
                        @click="
                            menuOpen = false;
                            shareStore();
                        "
                    >
                        مشاركة المتجر
                    </button>
                    <a
                        v-if="showWhatsApp"
                        :href="seller.whatsapp_href!"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-xl px-3 py-3 text-start text-sm font-medium text-neutral-800 transition hover:bg-neutral-50"
                        @click="menuOpen = false"
                    >
                        واتساب
                    </a>
                    <div v-if="hasSocialLinks" class="mt-4 border-t border-neutral-100 pt-4">
                        <p class="mb-3 text-[11px] font-medium uppercase tracking-wide text-neutral-400">
                            السوشيال
                        </p>
                        <div class="flex flex-wrap justify-end gap-2">
                            <template v-for="item in socialEntries" :key="item.key">
                                <a
                                    :href="item.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex size-10 items-center justify-center rounded-full bg-neutral-100 text-neutral-700 transition hover:bg-neutral-200"
                                >
                                    <span class="sr-only">{{ item.label }}</span>
                                    <Instagram
                                        v-if="item.key === 'instagram'"
                                        class="size-[1.1rem] shrink-0"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    />
                                    <Facebook
                                        v-else-if="item.key === 'facebook'"
                                        class="size-[1.1rem] shrink-0"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    />
                                    <Youtube
                                        v-else-if="item.key === 'youtube'"
                                        class="size-[1.1rem] shrink-0"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    />
                                    <Twitter
                                        v-else-if="item.key === 'x'"
                                        class="size-[1.1rem] shrink-0"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    />
                                    <svg
                                        v-else-if="item.key === 'tiktok'"
                                        class="size-[1.05rem] shrink-0"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"
                                        />
                                    </svg>
                                </a>
                            </template>
                        </div>
                    </div>
                </nav>
            </SheetContent>
        </Sheet>

        <Sheet v-model:open="cartOpen">
            <SheetContent
                side="bottom"
                class="max-h-[88vh] rounded-t-[1.25rem] border-0 bg-[#faf9f7] px-4 pb-8 pt-2 sm:px-6"
                dir="rtl"
            >
                <div class="mx-auto flex max-h-[calc(88vh-3rem)] w-full max-w-lg flex-col">
                    <SheetHeader class="space-y-1 border-b border-[#111111]/[0.06] px-0 pb-4 text-right">
                        <SheetTitle class="text-xl font-semibold text-[#111111]">
                            سلة التسوّق
                        </SheetTitle>
                        <SheetDescription class="text-[13px] leading-relaxed text-[#6b6560]">
                            راجع طلبك ثم أكمل بياناتك وخطوة الدفع — بخطوات بسيطة.
                        </SheetDescription>
                    </SheetHeader>

                    <div v-if="cartLines.length === 0" class="flex flex-1 flex-col items-center justify-center py-14 text-center">
                        <ShoppingBag class="mb-3 size-12 text-[#c4bfb8]" stroke-width="1.25" />
                        <p class="text-[15px] text-[#6b6560]">السلة فارغة.</p>
                    </div>

                    <ul v-else class="mt-4 flex-1 space-y-3 overflow-y-auto pe-0.5 ps-1">
                        <li
                            v-for="line in cartLines"
                            :key="line.productId"
                            class="flex gap-3 rounded-2xl bg-white p-3 shadow-[0_2px_12px_rgb(17_17_17_/_0.06)] ring-1 ring-[#111111]/[0.05]"
                        >
                            <div
                                class="size-[4.5rem] shrink-0 overflow-hidden rounded-xl bg-[#eceae6] ring-1 ring-[#111111]/[0.04]"
                            >
                                <img
                                    v-if="line.imageUrl"
                                    :src="line.imageUrl"
                                    alt=""
                                    class="size-full object-cover"
                                />
                            </div>
                            <div class="flex min-w-0 flex-1 flex-col gap-2 text-right">
                                <p
                                    class="text-[15px] font-semibold leading-snug text-[#111111] sm:text-base"
                                    dir="auto"
                                >
                                    {{ line.name }}
                                </p>

                                <div
                                    class="rounded-xl border border-[#c8a97e]/35 bg-gradient-to-br from-[#faf6f0] to-[#f3ebe0] px-3 py-2.5 shadow-[inset_0_1px_0_rgb(255_255_255_/_0.65)]"
                                >
                                    <p class="text-[11px] font-medium text-[#7d6f5c]">
                                        مجموع هذا الصنف
                                    </p>
                                    <p
                                        class="mt-0.5 text-lg font-bold leading-none tabular-nums tracking-tight text-[#111111] sm:text-xl"
                                        dir="ltr"
                                        lang="en"
                                    >
                                        {{ formatPrice(lineTotal(line)) }}
                                    </p>
                                </div>

                                <p class="text-[12px] leading-relaxed text-[#8a8278]" dir="rtl">
                                    <span class="text-[#9c9590]">سعر الوحدة:</span>
                                    <span
                                        class="ms-1 font-semibold tabular-nums text-[#6b5340]"
                                        dir="ltr"
                                        lang="en"
                                    >
                                        {{ formatPrice(line.price) }}
                                    </span>
                                </p>

                                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-[#111111]/[0.06] pt-2.5">
                                    <button
                                        type="button"
                                        class="inline-flex size-9 items-center justify-center rounded-full text-[#b4534a] transition hover:bg-[#fdf2f2]"
                                        aria-label="إزالة من السلة"
                                        @click="removeLine(line.productId)"
                                    >
                                        <Trash2 class="size-4" stroke-width="2" />
                                    </button>
                                    <div
                                        class="inline-flex items-center rounded-full bg-[#f0eeeb] p-0.5 ring-1 ring-[#111111]/[0.06]"
                                    >
                                        <button
                                            type="button"
                                            class="flex size-9 items-center justify-center rounded-full text-[#111111] transition hover:bg-white"
                                            aria-label="تقليل الكمية"
                                            @click="bumpQuantity(line.productId, -1)"
                                        >
                                            <Minus class="size-4" stroke-width="2.5" />
                                        </button>
                                        <span
                                            class="min-w-[2rem] px-1 text-center text-sm font-semibold tabular-nums text-[#111111]"
                                            dir="ltr"
                                        >
                                            {{ line.quantity }}
                                        </span>
                                        <button
                                            type="button"
                                            class="flex size-9 items-center justify-center rounded-full text-[#111111] transition hover:bg-white"
                                            aria-label="زيادة الكمية"
                                            @click="bumpQuantity(line.productId, 1)"
                                        >
                                            <Plus class="size-4" stroke-width="2.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <div
                        v-if="cartLines.length > 0"
                        class="mt-4 shrink-0 space-y-3 border-t border-[#111111]/[0.06] pt-4"
                    >
                        <div
                            class="flex items-center justify-between gap-3 rounded-xl border border-[#111111]/[0.08] bg-white px-4 py-3"
                        >
                            <span class="text-[15px] font-semibold text-[#111111]">الإجمالي</span>
                            <span
                                class="text-lg font-bold tabular-nums tracking-tight text-[#6b5340] sm:text-xl"
                                dir="ltr"
                                lang="en"
                            >{{
                                formatPrice(cartSubtotal)
                            }}</span>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            <Button
                                as-child
                                class="h-12 flex-1 rounded-xl bg-[#111111] text-[15px] font-medium text-white hover:bg-[#1f1f1f]"
                            >
                                <a
                                    :href="checkoutPath"
                                    class="inline-flex w-full items-center justify-center gap-2"
                                >
                                    متابعة لإتمام الطلب
                                </a>
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                class="h-10 text-[13px] text-[#6b6560] hover:text-[#111111]"
                                @click="clearCart"
                            >
                                تفريغ السلة
                            </Button>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>

        <!-- واتساب -->
        <a
            v-if="showWhatsApp"
            :href="seller.whatsapp_href!"
            target="_blank"
            rel="noopener noreferrer"
            class="fixed bottom-6 end-6 z-50 flex size-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-[0_8px_24px_rgb(37_211_102_/_0.45)] transition hover:scale-105 hover:bg-[#20BD5A] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#25D366]"
            aria-label="تواصل عبر واتساب"
        >
            <svg class="size-8" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path
                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"
                />
            </svg>
        </a>
    </div>
</template>

<style>
/* SweetAlert2 — mounted on body; boutique palette */
.swal2-container.swal2-shown {
    padding: 1rem;
}
.swal-luxury-storefront {
    border-radius: 1.25rem !important;
    border: 1px solid rgba(17, 17, 17, 0.08) !important;
    background: linear-gradient(180deg, #faf9f7 0%, #f5f3ef 100%) !important;
    box-shadow:
        0 25px 80px -12px rgba(17, 17, 17, 0.28),
        0 0 0 1px rgba(200, 169, 126, 0.12) !important;
    padding: 1.75rem 1.5rem 1.25rem !important;
    max-width: min(100vw - 2rem, 26rem) !important;
}
.swal-luxury-storefront .swal2-icon.swal2-success {
    margin: 0 auto 0.75rem !important;
    border-width: 2px !important;
    border-color: rgba(5, 150, 105, 0.35) !important;
    color: #059669 !important;
}
.swal-luxury-storefront .swal2-icon.swal2-success [class^='swal2-success-line'] {
    background-color: #059669 !important;
}
.swal-luxury-storefront .swal2-icon.swal2-success .swal2-success-ring {
    border-color: rgba(5, 150, 105, 0.28) !important;
}
.swal-luxury-storefront .swal2-title {
    color: #111111 !important;
    font-weight: 600 !important;
    font-size: 1.2rem !important;
    letter-spacing: -0.02em !important;
    padding: 0.35rem 0 0 !important;
    margin: 0 !important;
}
.swal-luxury-storefront .swal2-html-container {
    margin: 0.75rem 0 0 !important;
    padding: 0 !important;
}
.swal-luxury-storefront .swal2-actions {
    margin: 1.35rem 0 0 !important;
    width: 100% !important;
}
.swal-luxury-confirm {
    margin: 0 auto !important;
    width: 100% !important;
    max-width: 18rem !important;
    border-radius: 0.75rem !important;
    background: #111111 !important;
    color: #faf9f7 !important;
    font-size: 15px !important;
    font-weight: 500 !important;
    padding: 0.7rem 1.25rem !important;
    border: none !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08) !important;
    transition:
        background 0.15s ease,
        box-shadow 0.15s ease !important;
}
.swal-luxury-confirm:hover {
    background: #1f1f1f !important;
    box-shadow: 0 4px 14px rgba(17, 17, 17, 0.18) !important;
}
.swal-luxury-confirm:focus {
    outline: none !important;
    box-shadow:
        0 0 0 3px rgba(200, 169, 126, 0.45),
        0 4px 14px rgba(17, 17, 17, 0.12) !important;
}
.swal-luxury-show {
    animation: swal-luxury-in 0.35s cubic-bezier(0.22, 1, 0.36, 1) both;
}
.swal-luxury-hide {
    animation: swal-luxury-out 0.22s ease-in both;
}
@keyframes swal-luxury-in {
    from {
        opacity: 0;
        transform: translateY(12px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
@keyframes swal-luxury-out {
    from {
        opacity: 1;
        transform: scale(1);
    }
    to {
        opacity: 0;
        transform: scale(0.98);
    }
}
</style>
