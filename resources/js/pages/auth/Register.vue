<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, ChevronRight, Lock, Mail, MapPin, Store, Upload, UserCircle2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { home, login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({ layout: null });

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
        class="dokany-landing min-h-screen bg-[#1f0433] text-white antialiased selection:bg-[#C8A97E]/35"
    >
        <div
            class="relative z-10 mx-auto min-h-screen max-w-[430px] bg-[#1f0433] shadow-[0_4px_40px_-12px_rgba(0,0,0,0.45)] sm:my-8 sm:min-h-[calc(100vh-4rem)] sm:rounded-2xl sm:ring-1 sm:ring-white/10"
        >
            <header
                class="border-b border-white/10 bg-[#1f0433]/90 px-6 py-4 backdrop-blur-xl backdrop-saturate-150 sm:rounded-t-2xl"
            >
                <div class="flex items-center justify-between gap-3">
                    <Link
                        v-if="step > 1"
                        href="#"
                        class="flex items-center gap-1 text-[14px] font-semibold text-white/70 transition hover:text-white"
                        @click.prevent="prevStep"
                    >
                        <ArrowLeft class="h-4 w-4" stroke-width="2" />
                        رجوع
                    </Link>
                    <Link
                        v-else
                        :href="home()"
                        class="flex items-center gap-1 text-[14px] font-semibold text-[#C8A97E] transition hover:text-[#B89367]"
                    >
                        <ArrowLeft class="h-4 w-4" stroke-width="2" />
                        الرئيسية
                    </Link>

                    <span class="text-[16px] font-bold tracking-[-0.02em] text-white">دكاني</span>

                    <Link
                        :href="login()"
                        class="text-[14px] font-semibold text-white/70 transition hover:text-white"
                    >
                        دخول
                    </Link>
                </div>

                <div class="mt-5 flex gap-1.5">
                    <div
                        v-for="n in totalSteps"
                        :key="n"
                        class="h-1 flex-1 rounded-full transition-colors duration-300"
                        :class="n <= step ? 'bg-[#C8A97E]' : 'bg-white/15'"
                    />
                </div>
                <p class="mt-2 text-center text-[12px] text-white/55">الخطوة {{ step }} من {{ totalSteps }}</p>
            </header>

            <main class="px-6 pb-12 pt-8">
                <form class="space-y-8" @submit.prevent="step < 3 ? nextStep() : submitRegistration()">
                    <!-- Step 1 -->
                    <div v-show="step === 1" class="space-y-6">
                        <div class="text-center">
                            <div
                                class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-[#C8A97E]"
                            >
                                <Store class="h-6 w-6" stroke-width="1.5" />
                            </div>
                            <h1 class="text-[26px] font-bold leading-[1.2] tracking-[-0.02em] text-white">ابدأ بإنشاء متجرك</h1>
                            <p class="mt-2 text-[15px] text-white/65">اسم واضح ولوجو يعكس علامتك.</p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[14px] font-semibold text-white" for="store_name">اسم المتجر</label>
                            <input
                                id="store_name"
                                v-model="form.name"
                    type="text"
                    name="name"
                                autocomplete="organization"
                                class="dokany-input w-full px-4 py-3.5 text-[16px]"
                                placeholder="مثال: دكاني للأزياء"
                            />
                            <p v-if="form.errors.name" class="text-[13px] text-red-400">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <span class="block text-[14px] font-semibold text-white">لوجو المتجر</span>
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
                                class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-white/25 bg-white/5 py-10 transition hover:border-[#C8A97E]/60 hover:bg-white/10"
                                @click="logoInputRef?.click()"
                            >
                                <template v-if="logoPreview">
                                    <img
                                        :src="logoPreview"
                                        alt=""
                                        class="h-16 w-16 rounded-xl object-cover ring-1 ring-white/20"
                                    />
                                    <span class="text-[14px] font-medium text-white/70">تغيير الصورة</span>
                                </template>
                                <template v-else>
                                    <Upload class="h-5 w-5 text-[#C8A97E]" stroke-width="1.75" />
                                    <span class="text-[15px] font-medium text-white/80">رفع لوجو (اختياري)</span>
                                </template>
                            </button>
                            <p v-if="form.errors.store_logo" class="text-[13px] text-red-400">{{ form.errors.store_logo }}</p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div v-show="step === 2" class="space-y-5">
                        <div class="text-center">
                            <div
                                class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-[#C8A97E]"
                            >
                                <UserCircle2 class="h-6 w-6" stroke-width="1.5" />
                            </div>
                            <h1 class="text-[26px] font-bold leading-[1.2] tracking-[-0.02em] text-white">معلومات التواصل</h1>
                            <p class="mt-2 text-[15px] text-white/65">نربط الدفع والعملاء بمتجرك.</p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[14px] font-semibold" for="instapay">رقم InstaPay</label>
                            <input
                                id="instapay"
                                v-model="form.instapay_wallet"
                                type="text"
                                name="instapay_wallet"
                                class="dokany-input w-full px-4 py-3.5 text-[16px] font-inter"
                                placeholder="المحفظة أو الرقم المسجل"
                                dir="ltr"
                            />
                            <p v-if="form.errors.instapay_wallet" class="text-[13px] text-red-400">
                                {{ form.errors.instapay_wallet }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[14px] font-semibold" for="whatsapp">رقم واتساب</label>
                            <input
                                id="whatsapp"
                                v-model="form.whatsapp_phone"
                                type="text"
                                name="whatsapp_phone"
                                class="dokany-input w-full px-4 py-3.5 text-[16px] font-inter"
                                placeholder="+20 ..."
                                dir="ltr"
                            />
                            <p v-if="form.errors.whatsapp_phone" class="text-[13px] text-red-400">
                                {{ form.errors.whatsapp_phone }}
                            </p>
            </div>

                        <div class="space-y-2">
                            <label class="block text-[14px] font-semibold" for="email">
                                <span class="inline-flex items-center gap-1.5">
                                    <Mail class="h-3.5 w-3.5 text-white/50" stroke-width="2" />
                                    الإيميل
                                </span>
                            </label>
                            <input
                    id="email"
                                v-model="form.email"
                    type="email"
                                name="email"
                    autocomplete="email"
                                class="dokany-input w-full px-4 py-3.5 text-[16px] font-inter"
                                placeholder="you@example.com"
                                dir="ltr"
                            />
                            <p v-if="form.errors.email" class="text-[13px] text-red-400">{{ form.errors.email }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[14px] font-semibold" for="phone">الهاتف</label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                name="phone"
                                autocomplete="tel"
                                class="dokany-input w-full px-4 py-3.5 text-[16px] font-inter"
                                placeholder="+20 ..."
                                dir="ltr"
                            />
                            <p v-if="form.errors.phone" class="text-[13px] text-red-400">{{ form.errors.phone }}</p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div v-show="step === 3" class="space-y-5">
                        <div class="text-center">
                            <div
                                class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-[#C8A97E]"
                            >
                                <Lock class="h-6 w-6" stroke-width="1.5" />
                            </div>
                            <h1 class="text-[26px] font-bold leading-[1.2] tracking-[-0.02em] text-white">إنشاء الحساب</h1>
                            <p class="mt-2 text-[15px] text-white/65">كلمة مرور قوية وعنوان يظهر في الفواتير.</p>
            </div>

                        <div class="space-y-2">
                            <label class="block text-[14px] font-semibold" for="password">كلمة المرور</label>
                            <input
                    id="password"
                                v-model="form.password"
                                type="password"
                                name="password"
                    autocomplete="new-password"
                                class="dokany-input w-full px-4 py-3.5 text-[16px]"
                />
                            <p v-if="form.errors.password" class="text-[13px] text-red-400">{{ form.errors.password }}</p>
            </div>

                        <div class="space-y-2">
                            <label class="block text-[14px] font-semibold" for="password_confirmation">تأكيد كلمة المرور</label>
                            <input
                    id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                name="password_confirmation"
                    autocomplete="new-password"
                                class="dokany-input w-full px-4 py-3.5 text-[16px]"
                />
            </div>

                        <div class="space-y-2">
                            <label class="block text-[14px] font-semibold" for="address">
                                <span class="inline-flex items-center gap-1.5">
                                    <MapPin class="h-3.5 w-3.5 text-white/50" stroke-width="2" />
                                    العنوان
                                </span>
                            </label>
                            <textarea
                                id="address"
                                v-model="form.address"
                                name="address"
                                rows="3"
                                class="dokany-input w-full resize-none px-4 py-3.5 text-[16px] leading-relaxed"
                                placeholder="المدينة، الحي، تفاصيل اختيارية"
                            />
                            <p v-if="form.errors.address" class="text-[13px] text-red-400">{{ form.errors.address }}</p>
                        </div>
        </div>

                    <div class="pt-2">
                        <button
                            v-if="step < 3"
                            type="submit"
                            class="btn-primary-dark flex w-full items-center justify-center gap-2 rounded-xl py-4 text-[15px] font-semibold transition enabled:hover:bg-[#dfc296] disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="!stepValid || form.processing"
                        >
                            التالي
                            <ChevronRight class="h-[18px] w-[18px]" stroke-width="2.5" />
                        </button>
                        <button
                            v-else
                            type="submit"
                            class="btn-primary-dark flex w-full items-center justify-center gap-2 rounded-xl py-4 text-[15px] font-semibold transition enabled:hover:bg-[#dfc296] disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="!stepValid || form.processing"
                        >
                            إنشاء الحساب
                            <ArrowRight class="h-[18px] w-[18px]" stroke-width="2.5" />
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-[14px] text-white/55">
                    عندك حساب؟
                    <Link :href="login()" class="font-semibold text-[#C8A97E] hover:text-[#B89367] hover:underline">
                        تسجيل الدخول
                    </Link>
                </p>
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

.dokany-input {
    border-radius: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.08);
    color: #fafafa;
    outline: none;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.dokany-input::placeholder {
    color: rgba(255, 255, 255, 0.45);
}

.dokany-input:focus {
    border-color: #c8a97e;
    box-shadow: 0 0 0 3px rgba(200, 169, 126, 0.28);
}

.btn-primary-dark {
    background-color: #c8a97e;
    color: #1f0433;
    box-shadow:
        0 1px 2px rgba(0, 0, 0, 0.12),
        0 6px 16px -4px rgba(200, 169, 126, 0.35);
}
</style>
