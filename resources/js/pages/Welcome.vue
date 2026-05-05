<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    CircleUser,
    Copy,
    Facebook,
    Linkedin,
    Link2,
    Mail,
    Lock,
    Package,
    Phone,
    Star,
    Twitter,
    User,
    Youtube,
    Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { dashboard, login, register } from '@/routes';
import type { User as AuthUser } from '@/types';

const openFaq = ref<number | null>(null);
const copied = ref(false);
const page = usePage<{ auth: { user: AuthUser | null } }>();
const isAuthenticated = computed(() => page.props.auth.user !== null);

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
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
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

    <div dir="rtl" lang="ar" class="dokany-landing min-h-screen bg-gray-50 text-gray-900">
        <section
            class="relative isolate overflow-hidden bg-[#040b27] text-white"
        >
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(120%_80%_at_88%_88%,rgba(119,61,255,0.45),transparent_55%),radial-gradient(90%_70%_at_10%_12%,rgba(58,105,255,0.22),transparent_55%),linear-gradient(135deg,#030614_0%,#070d2c_42%,#05081f_100%)]" />
            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(0deg,rgba(1,4,20,0.72),rgba(1,4,20,0.45))]" />
            <div class="pointer-events-none absolute -left-24 top-20 h-72 w-72 rounded-full bg-black/30 blur-3xl" />
            <div class="pointer-events-none absolute -right-20 bottom-0 h-72 w-72 rounded-full bg-violet-600/25 blur-3xl" />

            <div class="relative mx-auto max-w-6xl px-6 py-20 text-center">
                <div class="mb-8 flex items-center justify-between">
                    <Link
                        v-if="isAuthenticated"
                        :href="dashboard()"
                        class="flex h-10 w-10 items-center justify-center rounded-lg border border-white/20 hover:bg-white/10"
                        aria-label="الذهاب إلى لوحة التحكم"
                    >
                        <CircleUser class="h-5 w-5" />
                    </Link>
                    <Link v-else :href="login()" class="rounded-lg border border-white/20 px-4 py-2 text-sm hover:bg-white/10">
                        تسجيل الدخول
                    </Link>
                    <span dir="ltr" class="text-xl font-black tracking-wide text-white">Dokany</span>
                </div>
                <h1 class="mb-6 text-4xl font-bold leading-tight md:text-5xl">
                    إنشئ متجرك الإلكتروني
                    <br />
                    في أقل من 5 دقايق
                </h1>
                <p class="mb-8 text-lg text-white/80">
                    لينك خاص بيك، منتجات لا نهائية، وعملاء أكتر - كل ده بدون تعقيدات
                </p>
                <div class="mb-12 flex justify-center gap-4">
                    <a href="#" class="flex h-12 w-12 items-center justify-center rounded-full border border-white/10 bg-white/10 backdrop-blur hover:bg-white/20"><Facebook class="h-5 w-5" /></a>
                    <a href="#" class="flex h-12 w-12 items-center justify-center rounded-full border border-white/10 bg-white/10 backdrop-blur hover:bg-white/20"><Twitter class="h-5 w-5" /></a>
                    <a href="#" class="flex h-12 w-12 items-center justify-center rounded-full border border-white/10 bg-white/10 backdrop-blur hover:bg-white/20"><Linkedin class="h-5 w-5" /></a>
                    <a href="#" class="flex h-12 w-12 items-center justify-center rounded-full border border-white/10 bg-white/10 backdrop-blur hover:bg-white/20"><Youtube class="h-5 w-5" /></a>
                </div>
                <Link :href="isAuthenticated ? dashboard() : register()" class="inline-flex rounded-lg bg-white px-8 py-3 font-semibold text-purple-900 hover:bg-purple-50">
                    {{ isAuthenticated ? 'الذهاب للوحة التحكم' : 'إنشئ متجرك مجاناً' }}
                </Link>
            </div>
        </section>

        <section class="bg-white py-16">
            <div class="mx-auto max-w-6xl px-6">
                <h2 class="mb-12 text-center text-3xl font-bold">إزاي تبدأ في 3 خطوات بس؟</h2>
                <div class="grid gap-8 md:grid-cols-3">
                    <div class="text-center"><div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-purple-900 text-3xl font-bold text-white">1</div><h3 class="mb-3 text-xl font-bold">سجل حسابك</h3><p class="text-gray-600">اكتب بياناتك البسيطة وخلاص</p></div>
                    <div class="text-center"><div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-purple-900 text-3xl font-bold text-white">2</div><h3 class="mb-3 text-xl font-bold">ضيف منتجاتك</h3><p class="text-gray-600">حط صور منتجاتك والأسعار بكل سهولة</p></div>
                    <div class="text-center"><div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-purple-900 text-3xl font-bold text-white">3</div><h3 class="mb-3 text-xl font-bold">شارك لينكك</h3><p class="text-gray-600">وزع اللينك بتاع متجرك وابدأ البيع</p></div>
                </div>
            </div>
        </section>

        <section class="bg-gradient-to-br from-purple-50 to-white py-16">
            <div class="mx-auto max-w-4xl px-6 text-center">
                <h2 class="mb-4 text-3xl font-bold">هيكون عندك لينك خاص زي ده</h2>
                <p class="mb-8 text-gray-600">شاركه مع عملائك على الواتساب والفيسبوك وأي مكان</p>
                <div
                    class="mx-auto flex w-full max-w-[680px] items-center justify-between gap-3 rounded-2xl border-2 border-purple-200 bg-white px-4 py-4 shadow-lg sm:gap-4 sm:px-6"
                >
                    <button class="rounded-lg bg-purple-900 p-3 text-white hover:bg-purple-800" @click="handleCopyLink">
                        <Check v-if="copied" class="h-5 w-5" />
                        <Copy v-else class="h-5 w-5" />
                    </button>
                    <span
                        class="min-w-0 flex-1 overflow-hidden text-ellipsis whitespace-nowrap text-center text-lg font-bold text-purple-900 sm:text-2xl"
                        dir="ltr"
                    >
                        Dokany.com/yourbrand
                    </span>
                    <Link2 class="h-8 w-8 flex-shrink-0 text-purple-900" />
                </div>
                <p class="mt-4 text-sm text-gray-500">{{ copied ? 'تم النسخ! 🎉' : 'اللينك بتاعك هيكون فريد وسهل في المشاركة' }}</p>
            </div>
        </section>

        <section class="bg-gray-50 py-16">
            <div class="mx-auto max-w-6xl px-6">
                <h2 class="mb-12 text-center text-3xl font-bold">ليه تختارنا؟</h2>
                <div class="grid gap-8 md:grid-cols-3">
                    <div v-for="(feature, index) in features" :key="index" class="rounded-xl bg-white p-8 shadow-sm transition hover:shadow-md">
                        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-lg bg-purple-100"><component :is="feature.icon" class="h-7 w-7 text-purple-900" /></div>
                        <h3 class="mb-3 text-xl font-bold">{{ feature.title }}</h3>
                        <p class="leading-relaxed text-gray-600">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white py-16">
            <div class="mx-auto max-w-4xl px-6">
                <h2 class="mb-12 text-center text-3xl font-bold">التسعير</h2>
                <div class="mx-auto max-w-lg rounded-2xl bg-gradient-to-br from-purple-900 to-purple-800 p-10 text-center text-white">
                    <div class="mb-8"><div class="mb-2 text-6xl font-bold">500</div><div class="text-purple-200">ج.م / شهرياً</div></div>
                    <div class="mb-8 space-y-4 text-right">
                        <div v-for="feature in pricingFeatures" :key="feature" class="flex items-center gap-3"><Check class="h-5 w-5 flex-shrink-0 text-purple-300" /><span class="text-purple-50">{{ feature }}</span></div>
                    </div>
                    <Link :href="isAuthenticated ? dashboard() : register()" class="block w-full rounded-lg bg-white py-4 font-bold text-purple-900 hover:bg-purple-50">ابدأ الآن</Link>
                </div>
            </div>
        </section>

        <section class="bg-gray-50 py-16">
            <div class="mx-auto max-w-6xl px-6">
                <h2 class="mb-12 text-center text-3xl font-bold">آراء العملاء</h2>
                <div class="grid gap-6 md:grid-cols-3">
                    <div v-for="(review, index) in reviews" :key="index" class="rounded-xl bg-white p-6 shadow-sm">
                        <div class="mb-4 flex gap-1"><Star v-for="i in review.rating" :key="i" class="h-5 w-5 fill-purple-900 text-purple-900" /></div>
                        <p class="mb-4 leading-relaxed text-gray-600">"{{ review.text }}"</p>
                        <div><div class="font-bold">{{ review.name }}</div><div class="text-sm text-gray-500">{{ review.role }}</div></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white py-16">
            <div class="mx-auto max-w-3xl px-6">
                <h2 class="mb-12 text-center text-3xl font-bold">الأسئلة الشائعة</h2>
                <div class="space-y-3">
                    <div v-for="(faq, index) in faqs" :key="index" class="overflow-hidden rounded-lg border border-gray-200">
                        <button class="flex w-full items-center justify-between px-6 py-4 text-right hover:bg-gray-50" @click="openFaq = openFaq === index ? null : index">
                            <span class="font-semibold">{{ faq.question }}</span>
                            <ChevronDown class="h-5 w-5 text-gray-500 transition-transform" :class="{ 'rotate-180': openFaq === index }" />
                        </button>
                        <div v-if="openFaq === index" class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                            <p class="leading-relaxed text-gray-600">{{ faq.answer }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-gradient-to-br from-purple-900 via-purple-800 to-purple-900 py-16 text-white">
            <div class="mx-auto max-w-4xl px-6 text-center">
                <h2 class="mb-4 text-4xl font-bold">جاهز تبدأ متجرك النهارده؟</h2>
                <p class="mb-8 text-xl text-purple-100">انضم لآلاف التجار اللي بدأوا البيع أونلاين بنجاح</p>
                <Link :href="isAuthenticated ? dashboard() : register()" class="rounded-lg bg-white px-10 py-4 text-lg font-bold text-purple-900 hover:bg-purple-50">
                    إنشئ متجرك مجاناً
                </Link>
            </div>
        </section>

        <footer class="bg-gray-900 py-12 text-gray-300">
            <div class="mx-auto max-w-6xl px-6">
                <div class="mb-8 grid gap-8 md:grid-cols-4">
                    <div><h3 class="mb-4 font-bold text-white">عن المنصة</h3><p class="text-sm leading-relaxed">أسهل طريقة لإنشاء متجرك الإلكتروني والبدء في البيع أونلاين في دقايق.</p></div>
                    <div><h3 class="mb-4 font-bold text-white">روابط مهمة</h3><ul class="space-y-2 text-sm"><li><Link :href="register()" class="hover:text-white">إنشئ متجرك</Link></li><li><a href="#" class="hover:text-white">المميزات</a></li><li><a href="#" class="hover:text-white">الأسعار</a></li><li><a href="#" class="hover:text-white">تواصل معانا</a></li></ul></div>
                    <div><h3 class="mb-4 font-bold text-white">المساعدة</h3><ul class="space-y-2 text-sm"><li><a href="#" class="hover:text-white">مركز الدعم</a></li><li><a href="#" class="hover:text-white">أسئلة شائعة</a></li><li><a href="#" class="hover:text-white">سياسة الاستخدام</a></li></ul></div>
                    <div><h3 class="mb-4 font-bold text-white">تابعنا</h3><div class="flex gap-3"><a href="#" class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-800 hover:bg-gray-700"><Facebook class="h-5 w-5" /></a><a href="#" class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-800 hover:bg-gray-700"><Twitter class="h-5 w-5" /></a><a href="#" class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-800 hover:bg-gray-700"><Linkedin class="h-5 w-5" /></a></div></div>
                </div>
                <div class="border-t border-gray-800 pt-8 text-center text-sm"><p>© {{ new Date().getFullYear() }} جميع الحقوق محفوظة - إنشئ متجرك في دقايق</p></div>
            </div>
        </footer>
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
