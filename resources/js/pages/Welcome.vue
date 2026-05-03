<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    Check,
    ChevronDown,
    CircleUser,
    Facebook,
    FileText,
    Instagram,
    LayoutGrid,
    Package,
    Share2,
    ShoppingBag,
    Smartphone,
    Sparkles,
    Star,
    Store,
    Wallet,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { dashboard, login, register } from '@/routes';
import type { User } from '@/types';

const openFaq = ref<number | null>(0);

const page = usePage<{ auth: { user: User | null } }>();
const isAuthenticated = computed(() => page.props.auth.user !== null);

const faqs = [
    {
        q: 'هل أحتاج موقع إلكتروني أو خبرة تقنية؟',
        a: 'لا. دكاني يمنحك صفحة متجر جاهزة يمكن مشاركتها كرابط على إنستغرام أو واتساب. كل شيء من جهة واحدة.',
    },
    {
        q: 'كيف يعمل الدفع عبر إنستاباي؟',
        a: 'يعرض المتجر تعليمات الدفع للعميل، ويرفع إثبات التحويل. أنت تؤكد الاستلام من لوحة التحكم ويُحدَّث الطلب تلقائياً.',
    },
    {
        q: 'هل يمكن إضافة منتجات غير محدودة؟',
        a: 'نعم ضمن باقة الاشتراك الشهرية. يمكنك إدارة المخزون والأسعار والصور في أي وقت.',
    },
    {
        q: 'هل مناسب لمبيعات واتساب وإنستغرام؟',
        a: 'مصمم خصيصاً لذلك: رابط واحد، طلبات منظمة، فواتير، وتأكيد يدوي يناسب طريقة عمل البائعين في المنطقة.',
    },
];

const features = [
    {
        icon: Store,
        title: 'متجر خاص بك',
        desc: 'صفحة علامتك، رابط قصير، وهوية متسقة مع عملك.',
    },
    {
        icon: ShoppingBag,
        title: 'استقبال الطلبات',
        desc: 'استلام الطلبات من العملاء بتدفق واضح وتنبيهات.',
    },
    {
        icon: Wallet,
        title: 'الدفع عبر InstaPay',
        desc: 'تعليمات دفع واضحة ورفع إثباتات التحويل بسهولة.',
    },
    {
        icon: LayoutGrid,
        title: 'إدارة المنتجات',
        desc: 'صور، أسعار، وخيارات بسيطة دون تعقيد.',
    },
    {
        icon: FileText,
        title: 'فواتير ومدفوعات',
        desc: 'سجل مالي منظم للطلبات والمدفوعات المؤكدة.',
    },
    {
        icon: BadgeCheck,
        title: 'تأكيد الطلبات',
        desc: 'مراجعة الإثبات وقبول أو رفض الدفع بضغطة.',
    },
];

const steps = [
    { n: '١', title: 'أنشئ متجرك', desc: 'سجّل، ضع اسم المتجر والشعار، وأضف منتجاتك الأولى.', icon: Sparkles },
    { n: '٢', title: 'شارك الرابط', desc: 'أرسل الرابط في البايو، الحالة، أو جماعات واتساب.', icon: Share2 },
    { n: '٣', title: 'استقبل الطلبات والدفع', desc: 'تابع الطلبات، أكّد المدفوعات، وأصدر الفواتير.', icon: Package },
];

const testimonials = [
    {
        name: 'سارة محمود',
        role: 'متجر إكسسوارات • القاهرة',
        quote:
            'من ساعة ما استخدمنا دكاني، الطلبات ما بقتش تضيع في الشات. الشهر اللي فات ضاعفنا الطلبات تقريباً.',
    },
    {
        name: 'كريم عبد اللطيف',
        role: 'بيع منتجات عناية • الإسكندرية',
        quote:
            'إثبات إنستاباي رفع العميل وباقي الدنيا منظمة في لوحة واحدة. راحة نفسية للتيم كله.',
    },
    {
        name: 'نورا حامد',
        role: 'متجر هدايا handmade • المنصورة',
        quote:
            'الواجهة نظيفة والعميل يفهم يدفع إزاي بدون ما نكرر نفس الكلام في كل مرة.',
    },
];

function toggleFaq(i: number): void {
    openFaq.value = openFaq.value === i ? null : i;
}

function scrollToId(id: string): void {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const testimonialSliderRef = ref<HTMLElement | null>(null);
const activeTestimonialIndex = ref(0);
let testimonialObserver: IntersectionObserver | null = null;

function scrollToTestimonial(i: number): void {
    const root = testimonialSliderRef.value;
    const el = root?.querySelector<HTMLElement>(`[data-testimonial-index="${i}"]`);
    el?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
}

onMounted(() => {
    const root = testimonialSliderRef.value;
    if (!root) {
        return;
    }
    const slides = root.querySelectorAll<HTMLElement>('[data-testimonial-index]');
    testimonialObserver = new IntersectionObserver(
        (entries) => {
            let best: { idx: number; ratio: number } | null = null;
            for (const entry of entries) {
                if (!entry.isIntersecting) {
                    continue;
                }
                const idxAttr = entry.target.getAttribute('data-testimonial-index');
                if (idxAttr === null) {
                    continue;
                }
                const idx = Number.parseInt(idxAttr, 10);
                const ratio = entry.intersectionRatio;
                if (!best || ratio > best.ratio) {
                    best = { idx, ratio };
                }
            }
            if (best) {
                activeTestimonialIndex.value = best.idx;
            }
        },
        { root, threshold: [0.45, 0.55, 0.65, 0.75], rootMargin: '-8px 0px' },
    );
    slides.forEach((slide) => testimonialObserver?.observe(slide));
});

onBeforeUnmount(() => {
    testimonialObserver?.disconnect();
    testimonialObserver = null;
});
</script>

<template>
    <Head title="دكاني — متجرك على إنستغرام وواتساب">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link
            href="https://fonts.googleapis.com/css2?family=Alexandria:wght@100..900&family=Arimo:ital,wght@0,400..700;1,400..700&family=Changa:wght@200..800&display=swap"
            rel="stylesheet"
        />
        <meta name="description" content="منصة تجارة اجتماعية للبائعين على إنستغرام وواتساب — متجر، طلبات، وإنستاباي في مكان واحد." />
    </Head>

    <div
        dir="rtl"
        lang="ar"
        class="dokany-landing min-h-screen bg-[#F8F8F7] text-[#111111] antialiased selection:bg-[#C8A97E]/25"
    >
        <div
            class="relative z-10 mx-auto min-h-screen max-w-[430px] bg-white shadow-[0_4px_40px_-12px_rgba(17,17,17,0.06)] sm:my-8 sm:min-h-[calc(100vh-4rem)] sm:rounded-2xl sm:ring-1 sm:ring-[#E6E5E2]"
        >
            <main>
                <div
                    class="overflow-hidden bg-[#1f0433] text-white sm:rounded-t-2xl"
                >
                    <header
                        class="sticky top-0 z-20 border-b border-white/15 bg-[#1f0433]/90 backdrop-blur-xl backdrop-saturate-150"
                    >
                        <div class="flex items-center justify-between px-6 py-4">
                            <Link
                                v-if="isAuthenticated"
                                :href="dashboard()"
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-white/25 bg-white/10 text-white transition hover:border-white/40 hover:bg-white/15 active:scale-[0.97]"
                                aria-label="لوحة التحكم"
                            >
                                <CircleUser class="h-[22px] w-[22px]" stroke-width="1.75" />
                            </Link>
                            <Link
                                v-else
                                :href="login()"
                                class="text-[15px] font-semibold text-[#E8D4B5] transition hover:text-[#f5ead8] hover:underline"
                            >
                                تسجيل الدخول
                            </Link>
                            <span class="text-[16px] font-extrabold tracking-[-0.02em] text-white">دكاني</span>
                        </div>
                    </header>

                    <!-- Hero -->
                    <section class="relative px-6 pb-14 pt-10">
                        <h1
                            class="mb-5 flex flex-col items-center gap-4 text-center text-[36px] font-extrabold leading-[1.4] tracking-[-0.02em] text-white"
                        >
                            <span class="block max-w-[20ch]">كل اللي تحتاجه للبيع أونلاين…</span>
                            <span class="block max-w-[16ch]">في مكان واحد</span>
                        </h1>
                        <p
                            class="mx-auto mb-10 max-w-[28ch] text-center text-[16px] font-normal leading-[1.85] text-white/80"
                        >
                            أنشئ متجرك، اعرض منتجاتك، واستقبل طلباتك ومدفوعاتك بسهولة من خلال إنستاباي.
                        </p>

                        <div class="mt-10">
                            <Link
                                :href="isAuthenticated ? dashboard() : register()"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-white py-4 text-[15px] font-semibold text-[#1f0433] shadow-[0_1px_2px_rgba(0,0,0,0.12),0_6px_20px_-4px_rgba(0,0,0,0.25)] transition hover:bg-white/90 active:scale-[0.98]"
                            >
                            <template v-if="isAuthenticated">
                                <CircleUser class="h-[18px] w-[18px] shrink-0" stroke-width="2" />
                                حسابي
                            </template>
                            <template v-else>
                                ابدأ الآن
                                <ArrowLeft class="h-[18px] w-[18px] opacity-95" stroke-width="2.5" />
                            </template>
                        </Link>
                    </div>

                    <div class="mt-10 flex items-center justify-center gap-3" role="list" aria-label="قنوات التواصل">
                        <a
                            href="#"
                            role="listitem"
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-white shadow-[0_2px_8px_rgba(37,211,102,0.35)] ring-2 ring-[#25D366]/20 transition hover:scale-105 hover:ring-[#25D366]/40 active:scale-95"
                            aria-label="واتساب"
                            @click.prevent
                        >
                            <svg class="h-[22px] w-[22px] text-[#25D366]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"
                                />
                            </svg>
                        </a>
                        <a
                            href="#"
                            role="listitem"
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-[#f9ce34] via-[#ee2a7b] to-[#6228dc] text-white shadow-[0_2px_10px_rgba(221,42,123,0.35)] transition hover:scale-105 active:scale-95"
                            aria-label="إنستغرام"
                            @click.prevent
                        >
                            <Instagram class="h-5 w-5" stroke-width="2" aria-hidden="true" />
                        </a>
                        <a
                            href="#"
                            role="listitem"
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-white text-black shadow-md ring-2 ring-[#25F4EE]/35 transition hover:scale-105 active:scale-95"
                            aria-label="تيك توك"
                            @click.prevent
                        >
                            <svg
                                class="h-[19px] w-[19px]"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                aria-hidden="true"
                                    >
                                        <path
                                    d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.3-1.86.1-3.72-.47-5.21-1.58-1.56-1.18-2.63-2.95-3.07-4.9-.37-1.61-.35-3.3-.02-4.9.43-2.08 1.68-3.98 3.52-5.22 1.58-1.07 3.51-1.6 5.43-1.53.02 1.62.01 3.24.01 4.86-1.05-.09-2.16.25-2.92 1.02-.76.78-1.01 1.95-.67 2.97.33.92 1.17 1.62 2.13 1.75.75.09 1.52-.07 2.11-.5.68-.5 1.08-1.27 1.09-2.09.05-2.5-.01-5 .02-7.5z"
                                        />
                                    </svg>
                                </a>
                        <a
                            href="#"
                            role="listitem"
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-white shadow-[0_2px_8px_rgba(24,119,242,0.35)] ring-2 ring-[#1877F2]/25 transition hover:scale-105 hover:ring-[#1877F2]/45 active:scale-95"
                            aria-label="فيسبوك"
                            @click.prevent
                        >
                            <Facebook class="h-[22px] w-[22px] text-[#1877F2]" :stroke-width="2" aria-hidden="true" />
                        </a>
                    </div>

                    <div class="mt-8 flex items-center justify-center gap-1">
                        <Star
                            v-for="si in 5"
                            :key="si"
                            class="h-3.5 w-3.5 fill-[#C8A97E] text-[#C8A97E]"
                        />
                        <span class="mr-2 text-[13px] text-white/75">موثوق من بائعين في مصر</span>
                    </div>
                    </section>
                </div>

                <!-- Features -->
                <section id="features" class="border-t border-[#E6E5E2] bg-[#F8F8F7] px-6 py-16">
                    <h2 class="mb-2 text-center text-[28px] font-extrabold tracking-[-0.02em] text-[#111111]">
                        كل ما تحتاجه
                    </h2>
                    <p class="mx-auto mb-12 max-w-[26ch] text-center text-[16px] leading-relaxed text-[#6B7280]">
                        بساطة في الاستخدام. قوة في النتيجة.
                    </p>
                    <div class="flex flex-col gap-3">
                        <div
                            v-for="(f, i) in features"
                            :key="f.title"
                            class="landing-card rounded-[20px] border border-[#E6E5E2] bg-white p-6 shadow-[0_1px_3px_rgba(0,0,0,0.04)]"
                            :style="{ animationDelay: `${i * 40}ms` }"
                        >
                            <div
                                class="mb-5 flex h-11 w-11 items-center justify-center rounded-full bg-[#F3EFE6] text-[#111111]"
                            >
                                <component :is="f.icon" class="h-5 w-5" stroke-width="1.75" />
                            </div>
                            <h3 class="mb-2 text-[19px] font-semibold tracking-[-0.01em] text-[#111111]">
                                {{ f.title }}
                            </h3>
                            <p class="text-[16px] font-normal leading-relaxed text-[#6B7280]">{{ f.desc }}</p>
                        </div>
                    </div>
                </section>

                <!-- How it works -->
                <section id="how-it-works" class="bg-white px-6 py-16">
                    <h2 class="mb-2 text-center text-[28px] font-extrabold tracking-[-0.02em] text-[#111111]">كيف يعمل</h2>
                    <p class="mx-auto mb-14 max-w-[26ch] text-center text-[16px] leading-relaxed text-[#6B7280]">
                        ثلاث خطوات. بدون تعقيد.
                    </p>
                    <div class="relative space-y-0">
                        <div
                            class="absolute right-[22px] top-6 bottom-6 w-px bg-gradient-to-b from-[#C8A97E]/28 via-[#C8A97E]/10 to-transparent"
                            aria-hidden="true"
                        />
                        <div v-for="s in steps" :key="s.n" class="relative flex gap-6 pb-12 last:pb-0">
                            <div
                                class="relative z-10 flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#111111] text-[15px] font-semibold text-white shadow-[0_2px_10px_rgba(17,17,17,0.18)]"
                            >
                                {{ s.n }}
                            </div>
                            <div class="min-w-0 pt-0.5">
                                <div class="mb-1 flex items-center gap-2">
                                    <component :is="s.icon" class="h-4 w-4 text-[#6B7280]" stroke-width="1.75" />
                                    <h3 class="text-[16px] font-semibold text-[#111111]">{{ s.title }}</h3>
                                </div>
                                <p class="text-[16px] leading-relaxed text-[#6B7280]">{{ s.desc }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Pricing -->
                <section id="pricing" class="bg-white px-6 py-16">
                    <h2 class="mb-2 text-center text-[28px] font-extrabold tracking-[-0.02em] text-[#111111]">السعر</h2>
                    <p class="mb-10 text-center text-[16px] text-[#6B7280]">شفاف. بدون مفاجآت.</p>

                    <div
                        class="rounded-2xl border border-[#E6E5E2] bg-white p-8 shadow-[0_2px_12px_-4px_rgba(17,17,17,0.05)]"
                    >
                        <p class="text-center text-[13px] font-medium uppercase tracking-[0.06em] text-[#6B7280]">
                            شهرياً
                        </p>
                        <div class="mt-1 flex items-baseline justify-center gap-1">
                            <span class="font-inter text-[48px] font-semibold tracking-[-0.03em] text-[#111111]">500</span>
                            <span class="text-[19px] font-medium text-[#6B7280]">ج.م</span>
                        </div>

                        <ul class="mt-8 space-y-3 border-t border-[#E6E5E2] pt-8">
                            <li
                                v-for="item in [
                                    'متجر كامل',
                                    'منتجات غير محدودة',
                                    'إدارة الطلبات',
                                    'صفحة دفع',
                                    'فواتير',
                                    'دعم واتساب',
                                ]"
                                :key="item"
                                class="flex items-start gap-3 text-[15px] text-[#4B5563]"
                            >
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#111111]">
                                    <Check class="h-2.5 w-2.5 text-white" stroke-width="3" />
                                </span>
                                {{ item }}
                        </li>
                    </ul>

                        <Link
                            :href="isAuthenticated ? dashboard() : register()"
                            class="btn-primary-dark mt-8 flex w-full items-center justify-center gap-2 rounded-xl py-4 text-[15px] font-semibold text-white transition hover:bg-[#222222] active:scale-[0.98]"
                        >
                            <template v-if="isAuthenticated">
                                <CircleUser class="h-[18px] w-[18px] shrink-0" stroke-width="2" />
                                حسابي
                            </template>
                            <template v-else>
                                اشترك كتاجر
                                <ArrowLeft class="h-[18px] w-[18px]" stroke-width="2" />
                            </template>
                        </Link>
                    </div>
                </section>

                <!-- Testimonials slider -->
                <section class="border-t border-[#E6E5E2] bg-[#F8F8F7] py-16">
                    <div class="mb-10 px-6 text-center">
                        <h2 class="mb-2 text-[28px] font-extrabold tracking-[-0.02em] text-[#111111]">
                            آراء التجار
                        </h2>
                        <p class="text-[16px] text-[#6B7280]">قصص من أرض الواقع — اسحب لرؤية المزيد</p>
                </div>

                    <div
                        ref="testimonialSliderRef"
                        class="testimonial-slider flex snap-x snap-mandatory gap-3 overflow-x-auto overflow-y-visible scroll-smooth px-6 pb-1 [-webkit-overflow-scrolling:touch] [scroll-padding-inline:24px]"
                        role="region"
                        aria-roledescription="carousel"
                        aria-label="آراء التجار"
                    >
                        <blockquote
                            v-for="(t, i) in testimonials"
                            :key="t.name"
                            :data-testimonial-index="i"
                            class="testimonial-card snap-center snap-always shrink-0 rounded-[22px] border border-[#E6E5E2] bg-white p-6 shadow-[0_4px_24px_-4px_rgba(17,17,17,0.05)] ring-1 ring-[#E6E5E2]/80 first:ms-0 last:me-0"
                            style="width: min(296px, calc(100vw - 4.5rem)); max-width: 100%"
                        >
                            <div
                                class="mb-4 inline-flex rounded-full border border-[#E6E5E2] bg-[#F8F8F7] px-3 py-1 text-[11px] font-medium uppercase tracking-wide text-[#6B7280]"
                            >
                                شهادة
                            </div>
                            <p class="mb-6 text-[16px] font-normal leading-[1.65] text-[#111111]">
                                «{{ t.quote }}»
                            </p>
                            <footer class="flex items-center gap-3 border-t border-[#E6E5E2] pt-5">
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-[#F3EFE6] to-[#E8DCC8] text-[15px] font-bold text-[#111111]"
                                >
                                    {{ t.name.charAt(0) }}
                                </div>
                                <div class="min-w-0">
                                    <cite class="not-italic text-[15px] font-semibold text-[#111111]">{{ t.name }}</cite>
                                    <p class="truncate text-[13px] text-[#6B7280]">{{ t.role }}</p>
                                </div>
                            </footer>
                        </blockquote>
                    </div>

                    <div class="mt-8 flex items-center justify-center gap-2 px-6" role="tablist" aria-label="اختيار الشهادة">
                        <button
                            v-for="(_, i) in testimonials"
                            :key="i"
                            type="button"
                            role="tab"
                            class="h-2 rounded-full transition-all duration-300 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-[#C8A97E]/35 focus-visible:ring-offset-2"
                            :class="
                                i === activeTestimonialIndex
                                    ? 'w-7 bg-[#C8A97E]'
                                    : 'w-2 bg-[#E6E5E2] hover:bg-[#D8D6D1]'
                            "
                            :aria-selected="i === activeTestimonialIndex"
                            :aria-label="`الشهادة ${i + 1}`"
                            @click="scrollToTestimonial(i)"
                        />
                    </div>
                </section>

                <!-- FAQ -->
                <section id="faq" class="bg-white px-6 py-16">
                    <h2 class="mb-2 text-center text-[28px] font-extrabold tracking-[-0.02em] text-[#111111]">
                        الأسئلة
                    </h2>
                    <p class="mb-10 text-center text-[16px] text-[#6B7280]">قبل ما تبدأ</p>
                    <div class="divide-y divide-[#E6E5E2] overflow-hidden rounded-[20px] border border-[#E6E5E2] bg-[#F8F8F7]">
                        <div v-for="(item, i) in faqs" :key="i" class="bg-white first:rounded-t-[19px] last:rounded-b-[19px]">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-4 px-5 py-5 text-start text-[15px] font-semibold text-[#111111] transition hover:bg-[#F8F8F7]"
                                :aria-expanded="openFaq === i"
                                @click="toggleFaq(i)"
                            >
                                {{ item.q }}
                                <ChevronDown
                                    class="h-5 w-5 shrink-0 text-[#6B7280] transition-transform duration-300 ease-out"
                                    :class="{ '-rotate-180': openFaq === i }"
                                    stroke-width="2"
                                />
                            </button>
                            <div
                                v-show="openFaq === i"
                                class="border-t border-[#E6E5E2] px-5 pb-5 pt-0 text-[16px] font-normal leading-relaxed text-[#6B7280]"
                            >
                                <p class="pt-4">
                                    {{ item.a }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Final CTA -->
                <section class="px-6 pb-6 pt-4">
                    <div
                        class="relative overflow-hidden rounded-2xl border border-[#E6E5E2] px-6 py-12 text-center shadow-[0_8px_32px_-14px_rgba(17,17,17,0.08)] [background:linear-gradient(160deg,#FFFFFF_0%,#F8F8F7_52%,#FFFFFF_100%)]"
                    >
                        <div
                            class="pointer-events-none absolute inset-0 opacity-60 [background:radial-gradient(ellipse_90%_55%_at_50%_0%,rgba(200,169,126,0.09),transparent_62%)]"
                        />
                        <Smartphone class="relative z-10 mx-auto mb-5 h-9 w-9 text-[#C8A97E]" stroke-width="1.25" />
                        <h2 class="relative z-10 mb-3 text-[28px] font-extrabold leading-[1.2] tracking-[-0.02em] text-[#111111]">
                            ابدأ بيع منتجاتك بشكل احترافي اليوم
                        </h2>
                        <p class="relative z-10 mb-8 text-[16px] font-normal leading-relaxed text-[#6B7280]">
                            انضم لآلاف البائعين الذين ينظمون عملهم بدكاني.
                        </p>
                        <Link
                            :href="isAuthenticated ? dashboard() : register()"
                            class="btn-primary-dark relative z-10 inline-flex w-full items-center justify-center gap-2 rounded-xl py-4 text-[15px] font-semibold text-white transition hover:bg-[#222222] active:scale-[0.98]"
                        >
                            <template v-if="isAuthenticated">
                                <CircleUser class="h-[18px] w-[18px] shrink-0" stroke-width="2" />
                                حسابي
                            </template>
                            <template v-else>
                                ابدأ الآن
                                <ArrowLeft class="h-[18px] w-[18px]" stroke-width="2.5" />
                            </template>
                        </Link>
                    </div>
                </section>

                <!-- Footer -->
                <footer class="border-t border-[#E6E5E2] px-6 py-10">
                    <div class="flex flex-col items-center gap-5 text-center">
                        <span class="text-[15px] font-semibold text-[#111111]">دكاني</span>
                        <p class="max-w-[260px] text-[12px] leading-relaxed text-[#6B7280]">
                            منصة للبيع على إنستغرام وواتساب — بواجهة هادئة وواضحة.
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-3 text-[13px]">
                            <a
                                href="#faq"
                                class="font-medium text-[#C8A97E] transition hover:text-[#B89367] hover:underline"
                                @click.prevent="scrollToId('faq')"
                            >الأسئلة</a>
                            <span class="text-[#E6E5E2]">|</span>
                            <Link
                                v-if="isAuthenticated"
                                :href="dashboard()"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[#E6E5E2] bg-[#F8F8F7] text-[#374151] transition hover:border-[#C8A97E]/55 hover:text-[#111111]"
                                aria-label="لوحة التحكم"
                            >
                                <CircleUser class="h-[18px] w-[18px]" stroke-width="1.75" />
                            </Link>
                            <Link
                                v-else
                                :href="login()"
                                class="font-medium text-[#C8A97E] transition hover:text-[#B89367] hover:underline"
                            >تسجيل الدخول</Link>
                        </div>
                        <p class="text-[11px] text-[#9CA3AF]">
                            © {{ new Date().getFullYear() }} دكاني
                        </p>
                </div>
                </footer>
            </main>
        </div>
    </div>
</template>

<style scoped>
.dokany-landing {
    -webkit-tap-highlight-color: transparent;
    font-family:
        'Alexandria',
        'Changa',
        'Arimo',
        -apple-system,
        BlinkMacSystemFont,
        'Segoe UI',
        sans-serif;
    font-size: 16px;
    line-height: 1.5;
}

.dokany-landing .font-inter {
    font-family: 'Arimo', 'Alexandria', 'Changa', sans-serif;
}

.dokany-landing [lang='en'] {
    font-family: 'Arimo', 'Alexandria', 'Changa', sans-serif;
}

.dokany-landing :is(h1, h2) {
    font-weight: 800;
}

.btn-primary-dark {
    background-color: #111111;
    box-shadow:
        0 1px 2px rgba(17, 17, 17, 0.06),
        0 6px 16px -4px rgba(17, 17, 17, 0.1);
}

.landing-card {
    animation: rise 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.testimonial-slider {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.testimonial-slider::-webkit-scrollbar {
    display: none;
}

.testimonial-card {
    animation: rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.testimonial-card:nth-child(1) {
    animation-delay: 0ms;
}
.testimonial-card:nth-child(2) {
    animation-delay: 45ms;
}
.testimonial-card:nth-child(3) {
    animation-delay: 90ms;
}

@keyframes rise {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
