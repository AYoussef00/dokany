<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Eye, EyeOff, Lock, LogIn, Mail } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { home, register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({ layout: null as any });

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
        class="min-h-screen bg-white text-slate-900 antialiased selection:bg-indigo-500/15"
    >
        <main class="relative min-h-screen">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(110%_70%_at_50%_0%,rgba(79,70,229,0.14),transparent_60%),linear-gradient(180deg,#ffffff_0%,#f8fafc_55%,#ffffff_100%)]" />

            <!-- Minimal header -->
            <header class="relative z-10 mx-auto flex h-14 max-w-6xl items-center justify-between px-6">
                <Link
                    :href="home()"
                    class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                >
                    <ArrowLeft class="h-4 w-4" stroke-width="2" />
                    الرئيسية
                </Link>
                <Link
                    :href="home()"
                    class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-slate-900 hover:bg-slate-100"
                    aria-label="Dokany"
                    dir="ltr"
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
                                    <h1 class="text-2xl font-black tracking-tight text-slate-900">تسجيل الدخول</h1>
                                </div>
                                <div class="inline-flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700">
                                    <LogIn class="h-6 w-6" stroke-width="2" />
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-6 sm:px-8">
                            <div
                                v-if="status"
                                class="mb-4 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-semibold text-slate-700"
                                role="status"
                            >
                                {{ status }}
                            </div>

                            <Form
                                v-bind="store.form()"
                                :reset-on-success="['password']"
                                v-slot="{ errors, processing }"
                                class="flex flex-col gap-4"
                            >
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2 text-sm font-black text-slate-900" for="email">
                                        <Mail class="h-4 w-4 text-indigo-600" stroke-width="2" />
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
                                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-[15px] font-semibold text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                                        placeholder="you@example.com"
                                        dir="ltr"
                                    />
                                    <InputError :message="errors.email" />
                                </div>

                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <label class="flex items-center gap-2 text-sm font-black text-slate-900" for="password">
                                            <Lock class="h-4 w-4 text-indigo-600" stroke-width="2" />
                                            كلمة المرور
                                        </label>
                                        <Link
                                            v-if="canResetPassword"
                                            :href="request()"
                                            class="text-sm font-semibold text-indigo-700 hover:text-indigo-800 hover:underline"
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
                                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 pe-12 text-[15px] font-semibold text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15"
                                            placeholder="••••••••"
                                        />
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 end-0 flex items-center px-3 text-slate-500 transition hover:text-slate-900"
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

                                <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:bg-slate-100">
                                    <input
                                        id="remember"
                                        type="checkbox"
                                        name="remember"
                                        class="size-4 shrink-0 rounded border-slate-300 text-indigo-600 accent-indigo-600"
                                        :tabindex="3"
                                    />
                                    <span class="text-sm font-semibold text-slate-700">تذكّرني على هذا الجهاز</span>
                                </label>

                                <button
                                    type="submit"
                                    class="mt-1 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3.5 text-[15px] font-black text-primary-foreground shadow-sm shadow-indigo-600/20 transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
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

                                <p class="pt-2 text-center text-sm font-semibold text-slate-600">
                                    ليس لديك حساب؟
                                    <Link :href="register()" class="font-black text-indigo-700 hover:text-indigo-800 hover:underline">
                                        تسجيل
                                    </Link>
                                </p>
                            </Form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
