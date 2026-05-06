<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, ChevronRight, Lock, Mail, MapPin, Store, Upload, UserCircle2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { home, login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({ layout: null as any });

const step = ref(1);
const totalSteps = 3;
const logoInputRef = ref<HTMLInputElement | null>(null);

const form = useForm({
    name: '',
    store_logo: null as File | null,
    email: '',
    instapay_wallet: '',
    whatsapp_phone: '',
    phone: '',
    password: '',
    password_confirmation: '',
    address: '',
});

const logoPreview = ref<string | null>(null);

function onLogoChange(e: Event): void {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.store_logo = file;
    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
    }
    logoPreview.value = file ? URL.createObjectURL(file) : null;
}

const stepValid = computed(() => {
    if (step.value === 1) {
        return form.name.trim().length >= 2;
    }
    if (step.value === 2) {
        return (
            form.instapay_wallet.trim().length >= 3
            && form.whatsapp_phone.trim().length >= 3
            && form.phone.trim().length >= 3
            && form.email.includes('@')
        );
    }
    if (step.value === 3) {
        return (
            form.password.length >= 8
            && form.password === form.password_confirmation
            && form.address.trim().length >= 4
        );
    }
    return false;
});

function nextStep(): void {
    if (step.value < totalSteps && stepValid.value) {
        step.value += 1;
    }
}

function prevStep(): void {
    if (step.value > 1) {
        step.value -= 1;
    }
}

function submitRegistration(): void {
    if (!stepValid.value) {
        return;
    }
    form.post(store.url(), {
        preserveScroll: true,
        onError: () => {
            if (Object.keys(form.errors).some((k) => ['name', 'store_logo'].includes(k))) {
                step.value = 1;
            } else if (
                Object.keys(form.errors).some((k) =>
                    ['email', 'instapay_wallet', 'whatsapp_phone', 'phone'].includes(k),
                )
            ) {
                step.value = 2;
            } else {
                step.value = 3;
            }
    },
});
}
</script>

<template>
    <Head title="إنشاء حساب — دكاني">
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
        class="min-h-screen bg-white text-slate-900 antialiased selection:bg-indigo-500/15"
    >
        <main class="relative min-h-screen">
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(110%_70%_at_50%_0%,rgba(79,70,229,0.14),transparent_60%),linear-gradient(180deg,#ffffff_0%,#f8fafc_55%,#ffffff_100%)]"
            />

            <!-- Minimal header (matches login) -->
            <header class="relative z-10 mx-auto flex h-14 max-w-6xl items-center justify-between px-6">
                <Link
                    v-if="step > 1"
                    href="#"
                    class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    @click.prevent="prevStep"
                >
                    <ArrowLeft class="h-4 w-4" stroke-width="2" />
                    رجوع
                </Link>
                <Link
                    v-else
                    :href="home()"
                    class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                >
                    <ArrowLeft class="h-4 w-4" stroke-width="2" />
                    الرئيسية
                </Link>

                <Link
                    :href="home()"
                    dir="ltr"
                    class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-slate-900 hover:bg-slate-100"
                    aria-label="Dokany"
                >
                    <span class="text-base font-black tracking-tight">Dokany</span>
                </Link>
            </header>

            <div class="relative mx-auto flex min-h-[calc(100vh-3.5rem)] max-w-6xl items-center justify-center px-6 py-6">
                <div class="w-full max-w-md">
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-6 py-5 sm:px-8">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h1 class="text-2xl font-black tracking-tight text-slate-900">إنشاء حساب</h1>
                                    <p class="mt-1 text-sm font-semibold text-slate-600">الخطوة {{ step }} من {{ totalSteps }}</p>
                                </div>
                                <div class="inline-flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                                    <Store v-if="step === 1" class="h-6 w-6" stroke-width="2" />
                                    <UserCircle2 v-else-if="step === 2" class="h-6 w-6" stroke-width="2" />
                                    <Lock v-else class="h-6 w-6" stroke-width="2" />
                                </div>
                            </div>

                            <div class="mt-5 flex gap-1.5">
                                <div
                                    v-for="n in totalSteps"
                                    :key="n"
                                    class="h-1 flex-1 rounded-full transition-colors duration-300"
                                    :class="n <= step ? 'bg-primary' : 'bg-slate-200'"
                                />
                            </div>
                        </div>

                        <div class="px-6 py-6 sm:px-8">
                            <form class="space-y-5" @submit.prevent="step < 3 ? nextStep() : submitRegistration()">
                    <!-- Step 1 -->
                    <div v-show="step === 1" class="space-y-4">
                        <div class="text-center">
                            <h2 class="text-xl font-black tracking-tight text-slate-900">ابدأ بإنشاء متجرك</h2>
                            <p class="mt-1 text-sm font-semibold text-slate-600">اسم واضح ولوجو يعكس علامتك.</p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-black text-slate-900" for="store_name">اسم المتجر</label>
                            <input
                                id="store_name"
                                v-model="form.name"
                    type="text"
                    name="name"
                                autocomplete="organization"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-[15px] font-semibold text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                                placeholder="مثال: دكاني للأزياء"
                            />
                            <p v-if="form.errors.name" class="text-[13px] font-semibold text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <span class="block text-sm font-black text-slate-900">لوجو المتجر (اختياري)</span>
                            <input
                                ref="logoInputRef"
                                type="file"
                                name="store_logo"
                                accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                class="sr-only"
                                @change="onLogoChange"
                            />
                            <button
                                type="button"
                                class="flex w-full items-center justify-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 py-8 transition hover:bg-slate-100"
                                @click="logoInputRef?.click()"
                            >
                                <template v-if="logoPreview">
                                    <img
                                        :src="logoPreview"
                                        alt=""
                                        class="h-14 w-14 rounded-xl object-cover ring-1 ring-slate-200"
                                    />
                                    <span class="text-sm font-semibold text-slate-700">تغيير الصورة</span>
                                </template>
                                <template v-else>
                                    <span class="inline-flex size-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                                        <Upload class="h-5 w-5" stroke-width="2" />
                                    </span>
                                    <span class="text-sm font-semibold text-slate-700">رفع لوجو</span>
                                </template>
                            </button>
                            <p v-if="form.errors.store_logo" class="text-[13px] font-semibold text-red-600">{{ form.errors.store_logo }}</p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div v-show="step === 2" class="space-y-4">
                        <div class="text-center">
                            <h2 class="text-xl font-black tracking-tight text-slate-900">معلومات التواصل</h2>
                            <p class="mt-1 text-sm font-semibold text-slate-600">نربط الدفع والعملاء بمتجرك.</p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-black text-slate-900" for="instapay">رقم InstaPay</label>
                            <input
                                id="instapay"
                                v-model="form.instapay_wallet"
                                type="text"
                                name="instapay_wallet"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-[15px] font-semibold text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                                placeholder="المحفظة أو الرقم المسجل"
                                dir="ltr"
                            />
                            <p v-if="form.errors.instapay_wallet" class="text-[13px] font-semibold text-red-600">
                                {{ form.errors.instapay_wallet }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-black text-slate-900" for="whatsapp">رقم واتساب</label>
                            <input
                                id="whatsapp"
                                v-model="form.whatsapp_phone"
                                type="text"
                                name="whatsapp_phone"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-[15px] font-semibold text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                                placeholder="+20 ..."
                                dir="ltr"
                            />
                            <p v-if="form.errors.whatsapp_phone" class="text-[13px] font-semibold text-red-600">
                                {{ form.errors.whatsapp_phone }}
                            </p>
            </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-black text-slate-900" for="email">
                                <span class="inline-flex items-center gap-1.5">
                                    <Mail class="h-4 w-4 text-indigo-600" stroke-width="2" />
                                    الإيميل
                                </span>
                            </label>
                            <input
                    id="email"
                                v-model="form.email"
                    type="email"
                                name="email"
                    autocomplete="email"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-[15px] font-semibold text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                                placeholder="you@example.com"
                                dir="ltr"
                            />
                            <p v-if="form.errors.email" class="text-[13px] font-semibold text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-black text-slate-900" for="phone">الهاتف</label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                name="phone"
                                autocomplete="tel"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-[15px] font-semibold text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                                placeholder="+20 ..."
                                dir="ltr"
                            />
                            <p v-if="form.errors.phone" class="text-[13px] font-semibold text-red-600">{{ form.errors.phone }}</p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div v-show="step === 3" class="space-y-4">
                        <div class="text-center">
                            <h2 class="text-xl font-black tracking-tight text-slate-900">إعداد كلمة المرور</h2>
                            <p class="mt-1 text-sm font-semibold text-slate-600">كلمة مرور قوية وعنوان يظهر في الفواتير.</p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-black text-slate-900" for="password">كلمة المرور</label>
                            <input
                    id="password"
                                v-model="form.password"
                                type="password"
                                name="password"
                    autocomplete="new-password"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-[15px] font-semibold text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                />
                            <p v-if="form.errors.password" class="text-[13px] font-semibold text-red-600">{{ form.errors.password }}</p>
            </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-black text-slate-900" for="password_confirmation">تأكيد كلمة المرور</label>
                            <input
                    id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                name="password_confirmation"
                    autocomplete="new-password"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-[15px] font-semibold text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                />
            </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-black text-slate-900" for="address">
                                <span class="inline-flex items-center gap-1.5">
                                    <MapPin class="h-4 w-4 text-indigo-600" stroke-width="2" />
                                    العنوان
                                </span>
                            </label>
                            <textarea
                                id="address"
                                v-model="form.address"
                                name="address"
                                rows="3"
                                class="w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-[15px] font-semibold leading-relaxed text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                                placeholder="المدينة، الحي، تفاصيل اختيارية"
                            />
                            <p v-if="form.errors.address" class="text-[13px] font-semibold text-red-600">{{ form.errors.address }}</p>
                        </div>
        </div>

                    <div class="pt-2">
                        <button
                            v-if="step < 3"
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3.5 text-[15px] font-black text-primary-foreground shadow-sm shadow-indigo-600/20 transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="!stepValid || form.processing"
                        >
                            التالي
                            <ChevronRight class="h-[18px] w-[18px]" stroke-width="2.5" />
                        </button>
                        <button
                            v-else
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3.5 text-[15px] font-black text-primary-foreground shadow-sm shadow-indigo-600/20 transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="!stepValid || form.processing"
                        >
                            إنشاء الحساب
                            <ArrowRight class="h-[18px] w-[18px]" stroke-width="2.5" />
                        </button>
                    </div>
                            </form>

                            <p class="mt-5 text-center text-sm font-semibold text-slate-600">
                    عندك حساب؟
                                <Link :href="login()" class="font-black text-indigo-700 hover:text-indigo-800 hover:underline">
                                    تسجيل الدخول
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
