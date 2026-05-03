<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Eye, EyeOff, Lock, LogIn, Mail } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { home, register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({ layout: null });

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const showPassword = ref(false);
</script>

<template>
    <Head title="تسجيل الدخول — دكاني">
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
            <header
                class="border-b border-[#E6E5E2] bg-white/90 px-6 py-4 backdrop-blur-xl backdrop-saturate-150 sm:rounded-t-2xl"
            >
                <div class="flex items-center justify-between gap-3">
                    <Link
                        :href="home()"
                        class="flex items-center gap-1 text-[14px] font-semibold text-[#C8A97E] transition hover:text-[#B89367]"
                    >
                        <ArrowLeft class="h-4 w-4" stroke-width="2" />
                        الرئيسية
                    </Link>
                    <span class="text-[16px] font-bold tracking-[-0.02em]">دكاني</span>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="text-[14px] font-semibold text-[#6B7280] transition hover:text-[#111111]"
                    >
                        حساب جديد
                    </Link>
                    <span v-else class="w-14" aria-hidden="true" />
                </div>
            </header>

            <main
                class="relative px-6 pb-14 pt-10 [background:linear-gradient(165deg,#FFFFFF_0%,#FFFFFF_50%,#F8F8F7_100%)]"
            >
                <div
                    class="pointer-events-none absolute inset-x-0 top-0 h-40 opacity-40 [background:radial-gradient(ellipse_80%_70%_at_50%_0%,rgba(200,169,126,0.12),transparent_70%)]"
                    aria-hidden="true"
                />

                <div class="relative">
                    <div
                        class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#F3EFE6] text-[#111111] shadow-[0_2px_12px_-4px_rgba(17,17,17,0.08)]"
                    >
                        <LogIn class="h-7 w-7" stroke-width="1.5" />
                    </div>

                    <h1 class="text-center text-[28px] font-bold leading-[1.15] tracking-[-0.02em]">
                        تسجيل الدخول
                    </h1>
                    <p class="mx-auto mt-2 max-w-[24ch] text-center text-[16px] leading-relaxed text-[#6B7280]">
                        مرحباً بعودتك — أدخل بياناتك للمتابعة.
                    </p>

                    <div
                        v-if="status"
                        class="mt-8 rounded-xl border border-[#C8A97E]/35 bg-[#FDFBF7] px-4 py-3 text-center text-[14px] font-medium text-[#6B7280]"
                        role="status"
                    >
                        {{ status }}
                    </div>

                    <Form
                        v-bind="store.form()"
                        :reset-on-success="['password']"
                        v-slot="{ errors, processing }"
                        class="mt-8 flex flex-col gap-6"
                    >
                        <div class="space-y-5">
                            <div class="space-y-2">
                                <label class="flex items-center gap-1.5 text-[14px] font-semibold text-[#111111]" for="email">
                                    <Mail class="h-3.5 w-3.5 text-[#C8A97E]" stroke-width="2" />
                                    البريد الإلكتروني
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autofocus
                                    :tabindex="1"
                                    autocomplete="email"
                                    class="dokany-input w-full px-4 py-3.5 text-[16px] font-inter"
                                    placeholder="you@example.com"
                                    dir="ltr"
                                />
                                <InputError :message="errors.email" />
                            </div>

                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <label class="flex items-center gap-1.5 text-[14px] font-semibold text-[#111111]" for="password">
                                        <Lock class="h-3.5 w-3.5 text-[#C8A97E]" stroke-width="2" />
                                        كلمة المرور
                                    </label>
                                    <Link
                                        v-if="canResetPassword"
                                        :href="request()"
                                        class="text-[13px] font-semibold text-[#C8A97E] transition hover:text-[#B89367] hover:underline"
                                        :tabindex="5"
                                    >
                                        نسيت كلمة المرور؟
                                    </Link>
                                </div>
                                <div class="relative">
                                    <input
                                        id="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        name="password"
                                        required
                                        :tabindex="2"
                                        autocomplete="current-password"
                                        class="dokany-input w-full px-4 py-3.5 pe-12 text-[16px]"
                                        placeholder="••••••••"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 end-0 flex items-center px-3 text-[#6B7280] transition hover:text-[#111111]"
                                        :tabindex="-1"
                                        :aria-label="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                                        @click="showPassword = !showPassword"
                                    >
                                        <EyeOff v-if="showPassword" class="h-[18px] w-[18px]" stroke-width="2" />
                                        <Eye v-else class="h-[18px] w-[18px]" stroke-width="2" />
                                    </button>
                                </div>
                                <InputError :message="errors.password" />
                            </div>

                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-xl border border-[#E6E5E2] bg-[#F8F8F7]/80 px-4 py-3.5 transition hover:border-[#C8A97E]/35"
                            >
                                <input
                                    id="remember"
                                    type="checkbox"
                                    name="remember"
                                    class="size-4 shrink-0 rounded border-[#D1D5DB] text-[#111111] accent-[#111111]"
                                    :tabindex="3"
                                />
                                <span class="text-[14px] font-medium text-[#4B5563]">تذكّرني على هذا الجهاز</span>
                            </label>
                        </div>

                        <button
                            type="submit"
                            class="btn-primary-dark flex w-full items-center justify-center gap-2 rounded-xl py-4 text-[15px] font-semibold text-white transition enabled:hover:bg-[#222222] disabled:cursor-not-allowed disabled:opacity-50"
                            :tabindex="4"
                            :disabled="processing"
                            data-test="login-button"
                        >
                            <span
                                v-if="processing"
                                class="inline-block size-5 animate-spin rounded-full border-2 border-white border-t-transparent"
                                aria-hidden="true"
                            />
                            <span>{{ processing ? 'جاري الدخول…' : 'دخول' }}</span>
                        </button>

                        <p v-if="canRegister" class="text-center text-[14px] text-[#6B7280]">
                            ليس لديك حساب؟
                            <Link
                                :href="register()"
                                class="font-semibold text-[#C8A97E] transition hover:text-[#B89367] hover:underline"
                                :tabindex="6"
                            >
                                ابدأ الآن
                            </Link>
                        </p>
                    </Form>
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

.dokany-input {
    border-radius: 0.75rem;
    border: 1px solid #e6e5e2;
    background: #fff;
    outline: none;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.dokany-input::placeholder {
    color: #9ca3af;
}

.dokany-input:focus {
    border-color: #c8a97e;
    box-shadow: 0 0 0 3px rgba(200, 169, 126, 0.2);
}

.btn-primary-dark {
    background-color: #111111;
    box-shadow:
        0 1px 2px rgba(17, 17, 17, 0.06),
        0 6px 16px -4px rgba(17, 17, 17, 0.1);
}
</style>
