<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    CircleUser,
    Copy,
    MessageCircle,
    Link2,
    Package,
    Star,
    Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { dashboard, login, register } from '@/routes';
import type { User as AuthUser } from '@/types';

const openFaq = ref<number | null>(null);
const copied = ref(false);
const page = usePage<{ auth: { user: AuthUser | null } }>();
const isAuthenticated = computed(() => page.props.auth.user !== null);
const year = computed(() => new Date().getFullYear());

const brandUrlExample = 'dokany.com/yourbrand';

const demoProducts = [
    {
        title: 'فستان صيفي',
        price: '499 ج.م',
        badge: 'الأكثر مبيعاً',
        imageUrl: '/Screenshot%201447-11-19%20at%2010.36.29%E2%80%AFAM.png',
    },
    {
        title: 'بلوزة ساتان',
        price: '259 ج.م',
        badge: 'جديد',
        imageUrl: '/Screenshot%201447-11-19%20at%2010.36.38%E2%80%AFAM.png',
    },
    {
        title: 'طقم عملي',
        price: '699 ج.م',
        badge: 'خصم 10%',
        imageUrl: '/Screenshot%201447-11-19%20at%2010.36.47%E2%80%AFAM.png',
    },
];

const features = [
    { icon: Zap, title: 'جاهز في دقايق', description: 'إنشئ متجرك الإلكتروني في أقل من 5 دقايق بدون أي خبرة تقنية' },
    { icon: Link2, title: 'لينك خاص بيك', description: 'احصل على رابط مخصص لمتجرك وشاركه مع عملائك في أي مكان' },
    { icon: Package, title: 'منتجات لا نهائية', description: 'ضيف عدد غير محدود من المنتجات والصور بكل سهولة' },
];

const pricingFeatures = [
    'متجر جاهز في دقايق معدودة',
    'لينك خاص ومميز بمتجرك',
    'منتجات لا نهائية بدون حدود',
    'إدارة الطلبات والعملاء',
    'دعم فني سريع ومجاني',
];

const reviews = [
    { name: 'أحمد سعيد', role: 'صاحب متجر ملابس', text: 'والله فعلاً عملت متجري في 3 دقايق وبدأت البيع من أول يوم، حاجة خرافية!', rating: 5 },
    { name: 'منى حسن', role: 'بائعة إكسسوارات', text: 'من أحسن القرارات اللي اتخذتها، اللينك بتاعي بوصل لكل عملائي بسهولة', rating: 5 },
    { name: 'كريم محمود', role: 'تاجر إلكترونيات', text: 'ضيفت أكتر من 200 منتج بكل سهولة، المنصة دي غيرت شغلي تماماً', rating: 5 },
];

const faqs = [
    { question: 'فعلاً هينشأ في دقايق؟', answer: 'أيوه طبعاً! في أقل من 5 دقايق هيكون عندك متجرك الخاص جاهز تماماً وتقدر تبدأ تضيف منتجاتك.' },
    { question: 'اللينك بتاع المتجر هيكون إزاي؟', answer: 'هتاخد لينك مخصوص ليك وتقدر تشاركه مع عملائك على الواتساب والفيسبوك وأي مكان.' },
    { question: 'في حد أقصى لعدد المنتجات؟', answer: 'لا خالص! ضيف عدد المنتجات اللي انت عايزه، مفيش أي حدود على الإطلاق.' },
    { question: 'لو محتاج مساعدة هعمل إيه؟', answer: 'فريق الدعم الفني موجود على طول، تواصل معنا وهنساعدك في أي حاجة.' },
];

function handleCopyLink(): void {
    const text = brandUrlExample;
    const done = () => {
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 1500);
    };

    if (typeof navigator !== 'undefined' && navigator.clipboard?.writeText) {
        void navigator.clipboard.writeText(text).then(done).catch(() => done());
        return;
    }

    // Fallback: best-effort without blocking UX.
    try {
        const el = document.createElement('textarea');
        el.value = text;
        el.setAttribute('readonly', 'true');
        el.style.position = 'fixed';
        el.style.top = '-9999px';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    } catch {
        // ignore
    }
    done();
}
</script>

<template>
    <Head title="إنشئ متجرك في دقايق">
        <meta name="description" content="إنشئ متجرك الإلكتروني في أقل من 5 دقايق، شارك لينك متجرك وابدأ البيع فورًا." />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link
            href="https://fonts.googleapis.com/css2?family=Alexandria:wght@100..900&family=Arimo:ital,wght@0,400..700;1,400..700&family=Changa:wght@200..800&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div dir="rtl" lang="ar" class="dokany-landing min-h-screen bg-white text-slate-900">
        <!-- Header -->
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                <span dir="ltr" class="text-lg font-black tracking-tight text-slate-900">Dokany</span>
                <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 md:flex">
                    <a href="#features" class="hover:text-slate-900">المميزات</a>
                    <a href="#how" class="hover:text-slate-900">كيف تعمل</a>
                    <a href="#pricing" class="hover:text-slate-900">التسعير</a>
                    <a href="#faq" class="hover:text-slate-900">الأسئلة</a>
                </nav>
                <div class="flex items-center gap-3">
                    <Link
                        v-if="isAuthenticated"
                        :href="dashboard()"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50"
                        aria-label="الذهاب إلى لوحة التحكم"
                    >
                        <CircleUser class="h-4 w-4" />
                        لوحة التحكم
                    </Link>
                    <template v-else>
                        <Link :href="login()" class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 md:inline-flex">
                            تسجيل الدخول
                        </Link>
                        <Link
                            :href="login()"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-800 shadow-sm hover:bg-slate-50 md:hidden"
                        >
                            تسجيل دخول
                        </Link>
                        <Link
                            :href="register()"
                            class="hidden items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-bold text-primary-foreground shadow-sm shadow-indigo-600/15 hover:bg-primary/90 md:inline-flex"
                        >
                            ابدأ الآن
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(110%_70%_at_50%_0%,rgba(79,70,229,0.14),transparent_60%),radial-gradient(90%_60%_at_10%_20%,rgba(99,102,241,0.10),transparent_55%),linear-gradient(180deg,#ffffff_0%,#f8fafc_55%,#ffffff_100%)]" />
            <div class="relative mx-auto max-w-6xl px-6 py-16 md:py-20">
                <div class="mx-auto max-w-3xl text-center">
                    <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm">
                        <span class="inline-flex size-5 items-center justify-center rounded-full bg-indigo-50 text-indigo-700">✓</span>
                        جاهز في دقايق — بدون خبرة تقنية
                    </div>
                    <h1 class="text-4xl font-black leading-tight tracking-tight text-slate-900 md:text-6xl">
                        أنشئ متجر احترافي
                        <span class="text-primary inline-flex items-center gap-2">
                            يشبه Shopify
                            <svg
                                aria-hidden="true"
                                viewBox="0 0 256 256"
                                class="size-7 md:size-8"
                            >
                                <path
                                    fill="currentColor"
                                    d="M210.8 48.4 167.9 35l-13.4-24.7a6 6 0 0 0-5.2-3.1c-.2 0-4.2.1-9.8 1.7-5.2-7.4-12.7-8.7-16.8-8.7h-.3c-12.6.4-25 9.4-35 25.3-7 11.3-12.4 25.8-14 37.2L43 71a6 6 0 0 0-4.2 5.1l-16 130.7a6 6 0 0 0 6 6.8h179.6a6 6 0 0 0 6-5.2l15.9-152.7a6 6 0 0 0-4.5-6.3Zm-75-34.5c2.1 4.6 3.8 10.8 4.7 19l-26.1 8.2c5.1-19.5 14.6-31 21.4-27.2ZM122.7 12c2.7 0 5.6.8 8.2 4.8-10.1 5.1-17.7 18.1-22.7 33.3l-22 6.9C89.4 38.6 106.6 12.8 122.7 12Zm-44 48.6 20.8-6.5a140 140 0 0 0-2.5 27.3l-32.1 10.1c3.1-12 8.1-23.8 13.8-30.9ZM192 202H44.3L58.9 82.9l38.6-12.1v13.1a6 6 0 1 0 12 0V67l24.3-7.6v24.4a6 6 0 1 0 12 0V55.7l20.9-6.6 24.2 7.5L192 202Zm-41.6-62.6c0 18.8-15.4 32.1-38.4 32.1-12.2 0-21.5-3.2-26.7-6.3a2 2 0 0 1-1-2l3.6-16.9a2 2 0 0 1 3-1.3c6.1 3.6 14.3 6.1 22.5 6.1 7.5 0 12.7-3.1 12.7-8.7 0-4.1-3.4-6.9-12-11.3-12.6-6.2-20.9-15-20.9-26.3 0-18.2 15.9-31.8 40.4-31.8 10.4 0 18.4 2.2 22.9 4.6a2 2 0 0 1 1.1 2.2l-3.5 16.2a2 2 0 0 1-2.9 1.3c-3.3-1.8-9.7-4.4-18.2-4.4-8.3.2-12.2 3.8-12.2 7.8 0 4.1 4.2 7 13.2 11.4 13.2 6.6 19.7 15.7 19.7 26.5Z"
                                />
                            </svg>
                        </span>
                        <br class="hidden md:block" />
                        وابدأ البيع فورًا
                    </h1>
                    <p class="mt-6 text-lg leading-relaxed text-slate-600 md:text-xl">
                        تصميم راقي، رابط متجر سهل المشاركة، وإدارة طلبات ومنتجات بشكل منظم — كل ده من لوحة تحكم واحدة.
                    </p>
                    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                        <Link
                            :href="isAuthenticated ? dashboard() : register()"
                            class="inline-flex items-center justify-center rounded-lg bg-primary px-6 py-3 text-base font-bold text-primary-foreground shadow-sm shadow-indigo-600/20 hover:bg-primary/90"
                        >
                            {{ isAuthenticated ? 'اذهب للوحة التحكم' : 'ابدأ الآن' }}
                        </Link>
                        <a
                            href="#pricing"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3 text-base font-bold text-slate-800 shadow-sm hover:bg-slate-50"
                        >
                            شاهد الأسعار
                        </a>
                    </div>

                    <!-- Social proof -->
                    <div class="mt-10 rounded-2xl border border-slate-200 bg-white/70 p-5 shadow-sm">
                        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                            <div class="text-sm font-semibold text-slate-700">
                                تقييم تجارنا
                                <span class="mx-2 text-slate-300">—</span>
                                <span class="text-slate-900">4.9</span>
                                <span class="text-slate-500">/5</span>
                            </div>
                            <div class="flex items-center gap-1 text-amber-500">
                                <Star class="h-5 w-5 fill-amber-400 text-amber-400" />
                                <Star class="h-5 w-5 fill-amber-400 text-amber-400" />
                                <Star class="h-5 w-5 fill-amber-400 text-amber-400" />
                                <Star class="h-5 w-5 fill-amber-400 text-amber-400" />
                                <Star class="h-5 w-5 fill-amber-400 text-amber-400" />
                            </div>
                            <p class="text-sm text-slate-600">متاجر نشطة — دعم سريع — لوحة عربية</p>
                        </div>
                    </div>
                </div>

                <!-- Product preview placeholder -->
                <div class="mt-12 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-5 py-3">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-rose-400" />
                            <span class="size-2 rounded-full bg-amber-400" />
                            <span class="size-2 rounded-full bg-emerald-400" />
                        </div>
                        <div class="text-xs font-semibold text-slate-500">Preview</div>
                    </div>
                    <div class="grid gap-4 p-4 sm:gap-6 sm:p-6 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <div
                                class="rounded-2xl border border-slate-200 bg-[linear-gradient(135deg,rgba(79,70,229,0.12),rgba(255,255,255,0.0))] p-4 sm:p-6 md:h-64"
                            >
                                <p class="text-sm font-semibold text-slate-700">واجهة متجر أنيقة</p>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                    صفحة منتجات واضحة، صور، سعر، زر شراء — تجربة قريبة جداً من Shopify.
                                </p>
                                <div class="mt-4 grid grid-cols-3 gap-2 sm:mt-6 sm:gap-3">
                                    <div
                                        v-for="p in demoProducts"
                                        :key="p.title"
                                        class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                                    >
                                        <div class="relative h-16 sm:h-20">
                                            <img
                                                :src="p.imageUrl"
                                                :alt="p.title"
                                                loading="lazy"
                                                class="h-full w-full object-cover"
                                            />
                                            <div
                                                class="absolute end-2 top-2 rounded-full border border-slate-200 bg-white/85 px-2 py-0.5 text-[10px] font-black text-slate-700 backdrop-blur"
                                            >
                                                {{ p.badge }}
                                            </div>
                                        </div>
                                        <div class="p-2 sm:p-3">
                                            <div class="flex items-center justify-center">
                                                <span class="rounded-lg bg-indigo-50 px-3 py-1.5 text-[10px] font-black text-indigo-700">
                                                    شراء
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <p class="text-sm font-semibold text-slate-700">لوحة تحكم منظمة</p>
                                <p class="mt-1 text-sm text-slate-600">طلبات، منتجات، فواتير — كله في مكانه.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <p class="text-sm font-semibold text-slate-700">سرعة ووضوح</p>
                                <p class="mt-1 text-sm text-slate-600">مسافات مريحة، ألوان هادئة، وإحساس احترافي.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                                <p class="text-sm font-semibold text-slate-700">يدعم العربية</p>
                                <p class="mt-1 text-sm text-slate-600">RTL جاهز، خطوط عربية مريحة، وتجربة سلسة.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How -->
        <section id="how" class="bg-white py-16">
            <div class="mx-auto max-w-6xl px-6">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 md:text-4xl">ابدأ في 3 خطوات</h2>
                    <p class="mt-3 text-lg text-slate-600">نفس فكرة Shopify — بس أبسط وأسرع وبالعربي.</p>
                </div>
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-5 inline-flex size-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                            <span class="text-xl font-black">1</span>
                        </div>
                        <h3 class="text-lg font-black text-slate-900">سجل حسابك</h3>
                        <p class="mt-2 leading-relaxed text-slate-600">بيانات بسيطة وخلاص — تقدر تدخل لوحة التحكم فوراً.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-5 inline-flex size-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                            <span class="text-xl font-black">2</span>
                        </div>
                        <h3 class="text-lg font-black text-slate-900">أضف منتجاتك</h3>
                        <p class="mt-2 leading-relaxed text-slate-600">صور، سعر، وصف — تجربة منظمة وسريعة.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-5 inline-flex size-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                            <span class="text-xl font-black">3</span>
                        </div>
                        <h3 class="text-lg font-black text-slate-900">شارك رابطك</h3>
                        <p class="mt-2 leading-relaxed text-slate-600">لينك ثابت لمتجرك — شاركه على واتساب وفيسبوك.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="bg-slate-50 py-16">
            <div class="mx-auto max-w-6xl px-6">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 md:text-4xl">ليه Dokany؟</h2>
                    <p class="mt-3 text-lg text-slate-600">
                        كل اللي محتاجه عشان تبدأ وتكبر — بتجربة نظيفة واحترافية.
                    </p>
                </div>
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    <div
                        v-for="(feature, index) in features"
                        :key="index"
                        class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="mb-5 inline-flex size-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                            <component :is="feature.icon" class="h-6 w-6" stroke-width="2" />
                        </div>
                        <h3 class="text-lg font-black text-slate-900">{{ feature.title }}</h3>
                        <p class="mt-2 leading-relaxed text-slate-600">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Link section -->
        <section class="bg-white py-16">
            <div class="mx-auto max-w-4xl px-6 text-center">
                <h2 class="text-3xl font-black tracking-tight text-slate-900 md:text-4xl">رابط متجر سهل المشاركة</h2>
                <p class="mt-3 text-lg text-slate-600">انسخه وأرسله — عميلك يوصل لمتجرك في ثانية.</p>
                <div class="mx-auto mt-8 flex w-full max-w-[720px] items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 shadow-sm sm:gap-4 sm:px-6">
                    <button
                        type="button"
                        class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm shadow-indigo-600/20 hover:bg-primary/90"
                        @click="handleCopyLink"
                        aria-label="نسخ رابط المتجر"
                    >
                        <Check v-if="copied" class="h-5 w-5" />
                        <Copy v-else class="h-5 w-5" />
                    </button>
                    <div class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-4 py-3 text-center">
                        <span class="block truncate text-base font-black text-slate-900 sm:text-lg" dir="ltr">
                            {{ brandUrlExample }}
                        </span>
                    </div>
                    <div class="hidden shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-3 text-slate-700 sm:flex">
                        <Link2 class="h-5 w-5" />
                    </div>
                </div>
                <p class="mt-3 text-sm text-slate-500">
                    {{ copied ? 'تم النسخ بنجاح' : 'الرابط فريد وسهل المشاركة' }}
                </p>
            </div>
        </section>

        <!-- Pricing -->
        <section id="pricing" class="bg-slate-50 py-16">
            <div class="mx-auto max-w-6xl px-6">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 md:text-4xl">التسعير</h2>
                    <p class="mt-3 text-lg text-slate-600">خطة بسيطة وواضحة — بدون تعقيد.</p>
                </div>
                <div class="mx-auto mt-10 max-w-2xl">
                    <div class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-10 shadow-sm">
                        <div class="pointer-events-none absolute -right-20 -top-24 size-72 rounded-full bg-indigo-100 blur-3xl" />
                        <div class="pointer-events-none absolute -left-24 -bottom-24 size-72 rounded-full bg-indigo-50 blur-3xl" />

                        <div class="relative">
                            <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                                <div class="text-center md:text-right">
                                    <p class="text-sm font-semibold text-slate-500">الخطة الشهرية</p>
                                    <h3 class="mt-2 text-2xl font-black text-slate-900">Dokany Pro</h3>
                                    <p class="mt-2 text-slate-600">كل شيء تحتاجه للانطلاق بسرعة.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-5 text-center shadow-sm">
                                    <div class="text-5xl font-black text-slate-900">500</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-500">ج.م / شهرياً</div>
                                </div>
                            </div>

                            <div class="mt-8 grid gap-3 md:grid-cols-2">
                                <div
                                    v-for="feature in pricingFeatures"
                                    :key="feature"
                                    class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3"
                                >
                                    <span class="inline-flex size-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                                        <Check class="h-5 w-5" />
                                    </span>
                                    <span class="font-semibold text-slate-800">{{ feature }}</span>
                                </div>
                            </div>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <Link
                                    :href="isAuthenticated ? dashboard() : register()"
                                    class="inline-flex flex-1 items-center justify-center rounded-lg bg-primary px-6 py-3 text-base font-black text-primary-foreground shadow-sm shadow-indigo-600/20 hover:bg-primary/90"
                                >
                                    ابدأ الآن
                                </Link>
                                <a
                                    href="#faq"
                                    class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3 text-base font-black text-slate-800 shadow-sm hover:bg-slate-50"
                                >
                                    اقرأ الأسئلة الشائعة
                                </a>
                            </div>
                            <p class="mt-4 text-center text-sm text-slate-500">إلغاء في أي وقت — دعم سريع</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Reviews -->
        <section class="bg-white py-16">
            <div class="mx-auto max-w-6xl px-6">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 md:text-4xl">آراء العملاء</h2>
                    <p class="mt-3 text-lg text-slate-600">تجربة حقيقية من تجار بدأوا وحققوا نتائج.</p>
                </div>
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    <div v-for="(review, index) in reviews" :key="index" class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm">
                        <div class="mb-4 flex gap-1 text-amber-500">
                            <Star v-for="i in review.rating" :key="i" class="h-5 w-5 fill-amber-400 text-amber-400" />
                        </div>
                        <p class="mb-5 leading-relaxed text-slate-700">"{{ review.text }}"</p>
                        <div class="border-t border-slate-200 pt-4">
                            <div class="font-black text-slate-900">{{ review.name }}</div>
                            <div class="text-sm font-semibold text-slate-500">{{ review.role }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="bg-slate-50 py-16">
            <div class="mx-auto max-w-3xl px-6">
                <div class="text-center">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 md:text-4xl">أسئلة شائعة</h2>
                    <p class="mt-3 text-lg text-slate-600">إجابات مختصرة وواضحة قبل ما تبدأ.</p>
                </div>
                <div class="mt-10 space-y-3">
                    <div v-for="(faq, index) in faqs" :key="index" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-4 px-6 py-5 text-right hover:bg-slate-50"
                            @click="openFaq = openFaq === index ? null : index"
                        >
                            <span class="text-base font-black text-slate-900">{{ faq.question }}</span>
                            <ChevronDown class="h-5 w-5 shrink-0 text-slate-500 transition-transform" :class="{ 'rotate-180': openFaq === index }" />
                        </button>
                        <div v-if="openFaq === index" class="border-t border-slate-200 bg-slate-50 px-6 py-5">
                            <p class="leading-relaxed text-slate-700">{{ faq.answer }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="bg-white py-16">
            <div class="mx-auto max-w-4xl px-6 text-center">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-[radial-gradient(120%_80%_at_50%_0%,rgba(79,70,229,0.18),transparent_62%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] p-10 shadow-sm">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 md:text-4xl">جاهز تبدأ النهارده؟</h2>
                    <p class="mt-3 text-lg text-slate-600">اعمل متجر احترافي وابدأ البيع خلال دقائق.</p>
                    <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                        <Link
                            :href="isAuthenticated ? dashboard() : register()"
                            class="inline-flex items-center justify-center rounded-lg bg-primary px-8 py-3 text-base font-black text-primary-foreground shadow-sm shadow-indigo-600/20 hover:bg-primary/90"
                        >
                            {{ isAuthenticated ? 'اذهب للوحة التحكم' : 'ابدأ الآن' }}
                        </Link>
                        <a
                            href="#features"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-8 py-3 text-base font-black text-slate-800 shadow-sm hover:bg-slate-50"
                        >
                            استعرض المميزات
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <footer class="border-t border-slate-200 bg-white py-12 text-slate-600">
            <div class="mx-auto max-w-6xl px-6">
                <div class="grid gap-8 md:grid-cols-4">
                    <div>
                        <div dir="ltr" class="text-lg font-black tracking-tight text-slate-900">Dokany</div>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">
                            أسهل طريقة لإنشاء متجرك الإلكتروني والبدء في البيع أونلاين — بتصميم احترافي قريب من Shopify.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">روابط</h3>
                        <ul class="mt-4 space-y-2 text-sm font-semibold">
                            <li><Link :href="register()" class="hover:text-slate-900">ابدأ الآن</Link></li>
                            <li><a href="#features" class="hover:text-slate-900">المميزات</a></li>
                            <li><a href="#pricing" class="hover:text-slate-900">التسعير</a></li>
                            <li><a href="#faq" class="hover:text-slate-900">الأسئلة الشائعة</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">منتج</h3>
                        <ul class="mt-4 space-y-2 text-sm font-semibold">
                            <li><a href="#how" class="hover:text-slate-900">كيف تعمل</a></li>
                            <li><Link :href="login()" class="hover:text-slate-900">تسجيل الدخول</Link></li>
                            <li><Link :href="dashboard()" class="hover:text-slate-900">لوحة التحكم</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">ملاحظة</h3>
                        <p class="mt-4 text-sm leading-relaxed text-slate-600">
                            لو تحب نضيف صور/سكرين شوت حقيقية من متجرك أو من لوحة التحكم هنا بدل الـpreview placeholder، ابعتهالي وأنا أركّبها بشكل احترافي.
                        </p>
                    </div>
                </div>
                <div class="mt-10 border-t border-slate-200 pt-8 text-center text-sm text-slate-500">
                    <p>© {{ year }} جميع الحقوق محفوظة</p>
                </div>
            </div>
        </footer>

        <a
            href="https://wa.me/966535815072"
            target="_blank"
            rel="noopener noreferrer"
            class="fixed bottom-5 right-5 z-50 inline-flex size-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-black/10 ring-1 ring-black/5 transition hover:brightness-95 focus:outline-none focus:ring-4 focus:ring-[#25D366]/25"
            aria-label="WhatsApp"
            title="WhatsApp"
            style="bottom: max(1.25rem, env(safe-area-inset-bottom, 0px)); right: max(1.25rem, env(safe-area-inset-right, 0px))"
        >
            <MessageCircle class="size-6" stroke-width="2.25" />
        </a>
    </div>
</template>

<style scoped>
.dokany-landing {
    font-family:
        'Alexandria',
        'Changa',
        'Arimo',
        -apple-system,
        BlinkMacSystemFont,
        'Segoe UI',
        sans-serif;
}
</style>
