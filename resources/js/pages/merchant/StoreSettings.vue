<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Copy, ExternalLink, ImagePlus, LayoutTemplate, Share2, Store, Trash2, Upload, UserCircle } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type HeroBannerRow = {
    path: string;
    url: string;
};

type SellerProps = {
    name: string;
    email: string;
    phone: string;
    whatsapp_phone: string;
    instapay_wallet: string;
    store_slug: string | null;
    store_logo_url: string | null;
    public_store_url: string | null;
    storefront_hero_primary: string | null;
    storefront_hero_secondary: string | null;
    hero_banners: HeroBannerRow[];
    social_facebook_url: string | null;
    social_instagram_url: string | null;
    social_x_url: string | null;
    social_youtube_url: string | null;
    social_tiktok_url: string | null;
};

const props = defineProps<{
    seller: SellerProps;
    storefrontPrefix: string;
    mustVerifyEmail: boolean;
}>();

const MAX_HERO_BANNERS = 8;

type SettingsSectionId = 'identity' | 'storefront' | 'contact' | 'social';

const sectionTabs: {
    id: SettingsSectionId;
    label: string;
    hint: string;
    icon: typeof Store;
}[] = [
    {
        id: 'identity',
        label: 'الهوية والرابط',
        hint: 'الاسم، المعرّف، الشعار، رابط المتجر',
        icon: Store,
    },
    {
        id: 'storefront',
        label: 'واجهة المتجر',
        hint: 'النصوص وبانر الهيرو',
        icon: LayoutTemplate,
    },
    {
        id: 'contact',
        label: 'التواصل والدفع',
        hint: 'البريد، الهاتف، واتساب، إنستاباي',
        icon: UserCircle,
    },
    {
        id: 'social',
        label: 'وسائل التواصل',
        hint: 'روابط السوشيال في المتجر',
        icon: Share2,
    },
];

const activeSection = ref<SettingsSectionId>('identity');

function isValidSectionHash(h: string): h is SettingsSectionId {
    return sectionTabs.some((t) => t.id === h);
}

function syncSectionFromHash(): void {
    if (typeof window === 'undefined') {
        return;
    }
    const raw = window.location.hash.replace(/^#/, '');
    if (raw && isValidSectionHash(raw)) {
        activeSection.value = raw;
    }
}

function goToSection(id: SettingsSectionId): void {
    activeSection.value = id;
    if (typeof window !== 'undefined') {
        const url = `${window.location.pathname}${window.location.search}#${id}`;
        window.history.replaceState(null, '', url);
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'الرئيسية', href: dashboard() },
            { title: 'إعدادات المتجر', href: '/merchant/store-settings' },
        ],
    },
});

const page = usePage<{
    flash: { success?: string | null };
    errors: Record<string, string | string[] | undefined>;
}>();
const flashSuccess = computed(() => page.props.flash?.success ?? null);
const pageErrors = computed(() => page.props.errors ?? {});

function firstError(key: string): string | undefined {
    const e = pageErrors.value[key];
    if (e === undefined) {
        return undefined;
    }
    return Array.isArray(e) ? e[0] : e;
}

const heroBannerImagesError = computed(() => firstError('hero_banner_images'));

const logoInputRef = ref<HTMLInputElement | null>(null);
const logoPreview = ref<string | null>(null);
const heroBannerInputRef = ref<HTMLInputElement | null>(null);
const heroBannerRemovePaths = ref<string[]>([]);
const heroBannerNewFiles = ref<File[]>([]);
const heroBannerNewPreviewUrls = ref<string[]>([]);

const submitting = ref(false);

const form = useForm({
    name: props.seller.name,
    email: props.seller.email,
    phone: props.seller.phone,
    whatsapp_phone: props.seller.whatsapp_phone,
    instapay_wallet: props.seller.instapay_wallet,
    store_slug: props.seller.store_slug ?? '',
    store_logo: null as File | null,
    storefront_hero_primary: props.seller.storefront_hero_primary ?? '',
    storefront_hero_secondary: props.seller.storefront_hero_secondary ?? '',
    social_facebook_url: props.seller.social_facebook_url ?? '',
    social_instagram_url: props.seller.social_instagram_url ?? '',
    social_x_url: props.seller.social_x_url ?? '',
    social_youtube_url: props.seller.social_youtube_url ?? '',
    social_tiktok_url: props.seller.social_tiktok_url ?? '',
});

function onLogoChange(e: Event): void {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.store_logo = file;
    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
    }
    logoPreview.value = file ? URL.createObjectURL(file) : null;
}

watch(
    () => props.seller.hero_banners,
    () => {
        heroBannerRemovePaths.value = [];
        heroBannerNewPreviewUrls.value.forEach((u) => URL.revokeObjectURL(u));
        heroBannerNewPreviewUrls.value = [];
        heroBannerNewFiles.value = [];
    },
    { deep: true },
);

const visibleHeroBanners = computed(() =>
    props.seller.hero_banners.filter((b) => !heroBannerRemovePaths.value.includes(b.path)),
);

const heroBannerCountAfterSave = computed(
    () => visibleHeroBanners.value.length + heroBannerNewFiles.value.length,
);

function queueRemoveHeroBanner(path: string): void {
    if (!heroBannerRemovePaths.value.includes(path)) {
        heroBannerRemovePaths.value.push(path);
    }
}

function undoRemoveHeroBanner(path: string): void {
    heroBannerRemovePaths.value = heroBannerRemovePaths.value.filter((p) => p !== path);
}

function onHeroBannersPick(e: Event): void {
    const input = e.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    let room = MAX_HERO_BANNERS - heroBannerCountAfterSave.value;
    for (const f of files) {
        if (room <= 0) {
            break;
        }
        heroBannerNewFiles.value.push(f);
        heroBannerNewPreviewUrls.value.push(URL.createObjectURL(f));
        room -= 1;
    }
    input.value = '';
}

function removeQueuedNewBanner(index: number): void {
    const u = heroBannerNewPreviewUrls.value[index];
    if (u) {
        URL.revokeObjectURL(u);
    }
    heroBannerNewPreviewUrls.value.splice(index, 1);
    heroBannerNewFiles.value.splice(index, 1);
}

function appendScalarFields(fd: FormData): void {
    fd.append('name', form.name);
    fd.append('email', form.email);
    fd.append('phone', form.phone);
    fd.append('whatsapp_phone', form.whatsapp_phone);
    fd.append('instapay_wallet', form.instapay_wallet);
    fd.append('store_slug', form.store_slug);
    fd.append('storefront_hero_primary', form.storefront_hero_primary);
    fd.append('storefront_hero_secondary', form.storefront_hero_secondary);
    fd.append('social_facebook_url', form.social_facebook_url);
    fd.append('social_instagram_url', form.social_instagram_url);
    fd.append('social_x_url', form.social_x_url);
    fd.append('social_youtube_url', form.social_youtube_url);
    fd.append('social_tiktok_url', form.social_tiktok_url);
}

function needsMultipartSubmit(): boolean {
    return (
        form.store_logo != null
        || heroBannerNewFiles.value.length > 0
        || heroBannerRemovePaths.value.length > 0
    );
}

function resetLogoAfterSuccess(): void {
    form.store_logo = null;
    if (logoInputRef.value) {
        logoInputRef.value.value = '';
    }
    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
        logoPreview.value = null;
    }
}

function resetBannerQueueAfterSuccess(): void {
    heroBannerNewPreviewUrls.value.forEach((u) => URL.revokeObjectURL(u));
    heroBannerNewPreviewUrls.value = [];
    heroBannerNewFiles.value = [];
    heroBannerRemovePaths.value = [];
}

function submitSettings(): void {
    const options = { preserveScroll: true };

    if (needsMultipartSubmit()) {
        const fd = new FormData();
        fd.append('_method', 'patch');
        appendScalarFields(fd);
        if (form.store_logo != null) {
            fd.append('store_logo', form.store_logo);
        }
        for (const p of heroBannerRemovePaths.value) {
            fd.append('hero_banner_remove_paths[]', p);
        }
        for (const f of heroBannerNewFiles.value) {
            fd.append('hero_banner_images[]', f);
        }
        router.post('/merchant/store-settings', fd, {
            ...options,
            onStart: () => {
                submitting.value = true;
            },
            onFinish: () => {
                submitting.value = false;
            },
            onSuccess: () => {
                resetLogoAfterSuccess();
                resetBannerQueueAfterSuccess();
            },
        });
        return;
    }

    form.patch('/merchant/store-settings', {
        ...options,
        onStart: () => {
            submitting.value = true;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
}

const displayLogo = computed(() => logoPreview.value ?? props.seller.store_logo_url);

let copyFlash: ReturnType<typeof setTimeout> | null = null;
const copiedUrl = ref(false);

async function copyPublicUrl(): Promise<void> {
    const u = props.seller.public_store_url;
    if (!u) {
        return;
    }
    try {
        await navigator.clipboard.writeText(u);
        copiedUrl.value = true;
        if (copyFlash) {
            clearTimeout(copyFlash);
        }
        copyFlash = setTimeout(() => {
            copiedUrl.value = false;
            copyFlash = null;
        }, 2000);
    } catch {
        copiedUrl.value = false;
    }
}

onMounted(() => {
    syncSectionFromHash();
    window.addEventListener('hashchange', syncSectionFromHash);
});

onBeforeUnmount(() => {
    window.removeEventListener('hashchange', syncSectionFromHash);
    if (logoPreview.value) {
        URL.revokeObjectURL(logoPreview.value);
    }
    heroBannerNewPreviewUrls.value.forEach((u) => URL.revokeObjectURL(u));
});
</script>

<template>
    <Head title="إعدادات المتجر" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        dir="rtl"
        lang="ar"
    >
        <div
            v-if="flashSuccess"
            class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100"
            role="status"
        >
            {{ flashSuccess }}
        </div>

        <form
            class="rounded-2xl border border-sidebar-border/70 bg-card/30 shadow-sm dark:border-sidebar-border"
            @submit.prevent="submitSettings"
        >
            <div
                class="sticky top-0 z-10 border-b border-sidebar-border/60 bg-muted/30 px-2 py-2 backdrop-blur-md dark:border-sidebar-border/80 md:px-4"
                role="tablist"
                aria-label="أقسام إعدادات المتجر"
            >
                <div class="flex gap-1.5 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] md:flex-wrap md:overflow-visible [&::-webkit-scrollbar]:hidden">
                    <button
                        v-for="tab in sectionTabs"
                        :key="tab.id"
                        type="button"
                        role="tab"
                        :aria-selected="activeSection === tab.id"
                        class="flex min-w-[9.5rem] shrink-0 items-center gap-2 rounded-xl border px-3 py-2.5 text-start text-sm transition-all md:min-w-0 md:flex-1"
                        :class="
                            activeSection === tab.id
                                ? 'border-primary/40 bg-background text-foreground shadow-md ring-2 ring-primary/20 dark:bg-card'
                                : 'border-transparent bg-transparent text-muted-foreground hover:border-sidebar-border/80 hover:bg-background/80 hover:text-foreground'
                        "
                        @click="goToSection(tab.id)"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <component :is="tab.icon" class="size-4" stroke-width="2" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold leading-tight">{{ tab.label }}</span>
                            <span class="mt-0.5 hidden text-[11px] leading-snug text-muted-foreground sm:block">{{
                                tab.hint
                            }}</span>
                        </span>
                    </button>
                </div>
            </div>

            <div class="p-4 md:p-6">
                <div
                    v-show="activeSection === 'identity'"
                    id="panel-identity"
                    role="tabpanel"
                    :aria-hidden="activeSection !== 'identity'"
                    class="space-y-6 scroll-mt-24"
                >
                    <div class="rounded-xl border border-sidebar-border/60 bg-muted/15 p-4 dark:border-sidebar-border md:p-5">
                        <h2 class="text-sm font-semibold">رابط المتجر العام</h2>
                        <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                            يشارك مع العملاء. المسار:
                            <span class="tabular-nums text-foreground" dir="ltr">/{{ storefrontPrefix }}/</span>
                            ثم معرّف المتجر (إنجليزي صغير).
                        </p>

                        <div
                            v-if="seller.public_store_url"
                            class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center"
                        >
                            <code
                                class="block min-w-0 flex-1 truncate rounded-lg border border-sidebar-border/70 bg-background/80 px-3 py-2 text-start text-xs tabular-nums sm:text-sm"
                                dir="ltr"
                                >{{ seller.public_store_url }}</code
                            >
                            <div class="flex flex-wrap gap-2">
                                <Button type="button" variant="outline" size="sm" class="gap-1.5" @click="copyPublicUrl">
                                    <Copy class="size-3.5" stroke-width="2" />
                                    {{ copiedUrl ? 'تم النسخ' : 'نسخ الرابط' }}
                                </Button>
                                <Button type="button" variant="outline" size="sm" class="gap-1.5" as-child>
                                    <a :href="seller.public_store_url" target="_blank" rel="noopener noreferrer">
                                        <ExternalLink class="size-3.5" stroke-width="2" />
                                        معاينة
                                    </a>
                                </Button>
                            </div>
                        </div>
                        <p v-else class="mt-3 text-sm text-amber-800 dark:text-amber-200">
                            احفظ معرّف المتجر أدناه ليظهر الرابط هنا.
                        </p>
                    </div>

                    <div class="grid gap-6">
                        <div class="grid gap-2">
                            <Label for="store-name">اسم المتجر</Label>
                    <Input
                        id="store-name"
                        v-model="form.name"
                        type="text"
                        name="name"
                        autocomplete="organization"
                        dir="auto"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="store-slug">
                        معرّف الرابط
                        <span class="font-normal text-muted-foreground" dir="ltr">(a-z, 0-9, -)</span>
                    </Label>
                    <div class="flex flex-wrap items-center gap-2" dir="ltr">
                        <span class="shrink-0 text-sm text-muted-foreground select-none"
                            >…/{{ storefrontPrefix }}/</span
                        >
                        <Input
                            id="store-slug"
                            v-model="form.store_slug"
                            type="text"
                            name="store_slug"
                            class="min-w-[10rem] flex-1"
                            autocomplete="off"
                            placeholder="my-store"
                        />
                    </div>
                    <InputError :message="form.errors.store_slug" />
                </div>

                <div class="grid gap-2">
                    <Label>شعار المتجر</Label>
                    <input
                        ref="logoInputRef"
                        type="file"
                        name="store_logo"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        class="sr-only"
                        @change="onLogoChange"
                    />
                    <div class="flex flex-wrap items-start gap-4">
                        <div
                            class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-sidebar-border/70 bg-muted/30 dark:border-sidebar-border"
                        >
                            <img
                                v-if="displayLogo"
                                :src="displayLogo"
                                alt=""
                                class="size-full object-cover"
                            />
                            <Store v-else class="size-8 text-muted-foreground" stroke-width="1.5" />
                        </div>
                        <Button type="button" variant="outline" size="sm" class="gap-2" @click="logoInputRef?.click()">
                            <Upload class="size-4" stroke-width="2" />
                            تغيير الصورة
                        </Button>
                    </div>
                    <InputError :message="form.errors.store_logo" />
                        </div>
                    </div>
                </div>

                <div
                    v-show="activeSection === 'storefront'"
                    id="panel-storefront"
                    role="tabpanel"
                    :aria-hidden="activeSection !== 'storefront'"
                    class="space-y-6 scroll-mt-24"
                >
                    <div class="grid gap-6">
                        <div class="grid gap-2">
                            <Label for="store-hero-primary">نص الترحيب في صفحة المتجر</Label>
                    <textarea
                        id="store-hero-primary"
                        v-model="form.storefront_hero_primary"
                        name="storefront_hero_primary"
                        rows="4"
                        class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:bg-input/30 flex min-h-[100px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                        dir="auto"
                    />
                    <p class="text-xs text-muted-foreground">
                        يظهر تحت اسم المتجر في صفحة المتجر العامة فقط عند وجود نص. إذا تركت الحقل فارغاً واحفظت، لا
                        تظهر هذه الفقرة في الرابط العام.
                    </p>
                    <InputError :message="form.errors.storefront_hero_primary" />
                </div>

                <div class="grid gap-2">
                    <Label for="store-hero-secondary">نص إضافي في صفحة المتجر</Label>
                    <textarea
                        id="store-hero-secondary"
                        v-model="form.storefront_hero_secondary"
                        name="storefront_hero_secondary"
                        rows="3"
                        class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:bg-input/30 flex min-h-[80px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                        dir="auto"
                    />
                    <InputError :message="form.errors.storefront_hero_secondary" />
                    <p class="text-xs text-muted-foreground">
                        نص أصغر تحت الفقرة الأولى إن وُجدت. فارغ + حفظ = لا يظهر في المتجر العام.
                    </p>
                </div>

                <div
                    class="grid gap-3 rounded-xl border border-sidebar-border/70 bg-muted/20 p-4 dark:border-sidebar-border"
                >
                    <div>
                        <Label class="text-sm font-semibold">بانر الهيرو (صور متحركة)</Label>
                        <p class="mt-1 text-xs text-muted-foreground">
                            حتى {{ MAX_HERO_BANNERS }} صور تُعرض كشرائح تتبدّل تلقائياً على الموبايل وسطح المكتب. يُفضّل
                            صور أفقية أو مربعة وخفيفة (JPEG / WebP / PNG). يمكنك الجمع بين البانر والنص أعلاه.
                        </p>
                    </div>
                    <input
                        ref="heroBannerInputRef"
                        type="file"
                        name="hero_banner_images"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        multiple
                        class="sr-only"
                        @change="onHeroBannersPick"
                    />
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="gap-2"
                            :disabled="
                                heroBannerCountAfterSave >= MAX_HERO_BANNERS || form.processing || submitting
                            "
                            @click="heroBannerInputRef?.click()"
                        >
                            <ImagePlus class="size-4" stroke-width="2" />
                            إضافة صور للبانر
                        </Button>
                        <span class="self-center text-xs tabular-nums text-muted-foreground" dir="ltr">
                            {{ heroBannerCountAfterSave }} / {{ MAX_HERO_BANNERS }}
                        </span>
                    </div>
                    <div v-if="visibleHeroBanners.length || heroBannerNewPreviewUrls.length" class="space-y-2">
                        <p class="text-xs font-medium text-foreground">
                            معاينة الصور
                            <span class="font-normal text-muted-foreground">
                                — تظهر فور الاختيار؛ بعد «حفظ التغييرات» تُعرض في رابط المتجر بنفس العرض.
                            </span>
                        </p>
                        <div
                            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div
                                v-for="b in visibleHeroBanners"
                                :key="b.path"
                                class="relative aspect-[16/10] w-full overflow-hidden rounded-xl border border-border bg-background shadow-sm"
                            >
                                <img :src="b.url" alt="" class="size-full object-cover" loading="lazy" />
                                <button
                                    type="button"
                                    class="absolute end-2 top-2 flex size-8 items-center justify-center rounded-full bg-black/65 text-white hover:bg-black/85"
                                    :title="'إزالة'"
                                    @click="queueRemoveHeroBanner(b.path)"
                                >
                                    <Trash2 class="size-4" stroke-width="2" />
                                </button>
                            </div>
                            <div
                                v-for="(preview, idx) in heroBannerNewPreviewUrls"
                                :key="`new-${idx}`"
                                class="relative aspect-[16/10] w-full overflow-hidden rounded-xl border-2 border-dashed border-primary/50 bg-muted/40 shadow-sm"
                            >
                                <img :src="preview" alt="" class="size-full object-cover" />
                                <span
                                    class="absolute start-2 top-2 rounded bg-primary/90 px-2 py-0.5 text-[10px] font-medium text-primary-foreground"
                                >
                                    جديد — سيُرفَع عند الحفظ
                                </span>
                                <button
                                    type="button"
                                    class="absolute end-2 top-2 flex size-8 items-center justify-center rounded-full bg-black/65 text-white hover:bg-black/85"
                                    @click="removeQueuedNewBanner(idx)"
                                >
                                    <Trash2 class="size-4" stroke-width="2" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <p
                        v-else
                        class="rounded-lg border border-dashed border-muted-foreground/25 bg-muted/20 px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        لم تُختر صور بعد. اضغط «إضافة صور للبانر» ثم احفظ لتظهر في صفحة متجرك.
                    </p>
                    <div
                        v-if="heroBannerRemovePaths.length"
                        class="flex flex-wrap gap-2 text-xs text-muted-foreground"
                    >
                        <span>صور ستُحذف عند الحفظ:</span>
                        <button
                            v-for="p in heroBannerRemovePaths"
                            :key="p"
                            type="button"
                            class="rounded-md bg-destructive/10 px-2 py-0.5 text-destructive hover:bg-destructive/20"
                            @click="undoRemoveHeroBanner(p)"
                        >
                            تراجع
                        </button>
                    </div>
                    <InputError :message="heroBannerImagesError" />
                </div>
                    </div>
                </div>

                <div
                    v-show="activeSection === 'contact'"
                    id="panel-contact"
                    role="tabpanel"
                    :aria-hidden="activeSection !== 'contact'"
                    class="space-y-6 scroll-mt-24"
                >
                    <p class="text-sm text-muted-foreground">
                        بيانات التواصل تُستخدم في لوحة التحكم والواجهة العامة حيث ينطبق ذلك (مثل واتساب في المتجر).
                    </p>
                    <div class="grid gap-6">
                        <div class="grid gap-2">
                            <Label for="seller-email">البريد الإلكتروني</Label>
                    <Input
                        id="seller-email"
                        v-model="form.email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        dir="ltr"
                        class="text-start"
                    />
                    <p v-if="mustVerifyEmail" class="text-xs text-muted-foreground">
                        عند تغيير البريد قد تحتاج لتأكيده من جديد.
                    </p>
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2 sm:grid-cols-2 sm:gap-4">
                    <div class="grid gap-2">
                        <Label for="seller-phone">رقم الهاتف</Label>
                        <Input
                            id="seller-phone"
                            v-model="form.phone"
                            type="text"
                            name="phone"
                            autocomplete="tel"
                            dir="ltr"
                            class="text-start"
                        />
                        <InputError :message="form.errors.phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="seller-whatsapp">واتساب</Label>
                        <Input
                            id="seller-whatsapp"
                            v-model="form.whatsapp_phone"
                            type="text"
                            name="whatsapp_phone"
                            autocomplete="tel"
                            dir="ltr"
                            class="text-start"
                        />
                        <InputError :message="form.errors.whatsapp_phone" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="seller-instapay">رقم InstaPay</Label>
                    <Input
                        id="seller-instapay"
                        v-model="form.instapay_wallet"
                        type="text"
                        name="instapay_wallet"
                        autocomplete="off"
                        dir="ltr"
                        class="text-start"
                    />
                    <InputError :message="form.errors.instapay_wallet" />
                </div>
                    </div>
                </div>

                <div
                    v-show="activeSection === 'social'"
                    id="panel-social"
                    role="tabpanel"
                    :aria-hidden="activeSection !== 'social'"
                    class="space-y-6 scroll-mt-24"
                >
                    <div>
                        <h3 class="text-sm font-semibold">روابط التواصل والسوشيال</h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            تظهر في أسفل صفحة متجرك العامة كأيقونات. اترك الحقل فارغاً لإخفاء المنصة. يُفضّل
                            لصق الرابط كاملاً يبدأ بـ https://
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="social-instagram">إنستغرام</Label>
                            <Input
                                id="social-instagram"
                                v-model="form.social_instagram_url"
                                type="url"
                                name="social_instagram_url"
                                dir="ltr"
                                class="text-start"
                                placeholder="https://instagram.com/..."
                            />
                            <InputError :message="form.errors.social_instagram_url" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="social-facebook">فيسبوك</Label>
                            <Input
                                id="social-facebook"
                                v-model="form.social_facebook_url"
                                type="url"
                                name="social_facebook_url"
                                dir="ltr"
                                class="text-start"
                                placeholder="https://facebook.com/..."
                            />
                            <InputError :message="form.errors.social_facebook_url" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="social-tiktok">تيك توك</Label>
                            <Input
                                id="social-tiktok"
                                v-model="form.social_tiktok_url"
                                type="url"
                                name="social_tiktok_url"
                                dir="ltr"
                                class="text-start"
                                placeholder="https://tiktok.com/@..."
                            />
                            <InputError :message="form.errors.social_tiktok_url" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="social-youtube">يوتيوب</Label>
                            <Input
                                id="social-youtube"
                                v-model="form.social_youtube_url"
                                type="url"
                                name="social_youtube_url"
                                dir="ltr"
                                class="text-start"
                                placeholder="https://youtube.com/..."
                            />
                            <InputError :message="form.errors.social_youtube_url" />
                        </div>
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="social-x">X (تويتر)</Label>
                            <Input
                                id="social-x"
                                v-model="form.social_x_url"
                                type="url"
                                name="social_x_url"
                                dir="ltr"
                                class="text-start"
                                placeholder="https://x.com/..."
                            />
                            <InputError :message="form.errors.social_x_url" />
                        </div>
                    </div>
                </div>

                <div
                    class="mt-8 flex flex-col gap-3 border-t border-sidebar-border/70 pt-6 dark:border-sidebar-border sm:flex-row sm:flex-wrap sm:items-center"
                >
                    <Button
                        type="submit"
                        class="bg-[#111111] text-white hover:bg-[#222222] dark:bg-[#E6E5E2] dark:text-[#111111] dark:hover:bg-white"
                        :disabled="form.processing || submitting"
                    >
                        {{ form.processing || submitting ? 'جاري الحفظ…' : 'حفظ كل التغييرات' }}
                    </Button>
                    <Button type="button" variant="outline" as-child>
                        <Link href="/settings/security">الأمان وكلمة المرور</Link>
                    </Button>
                    <p class="text-xs text-muted-foreground sm:ms-auto sm:text-end">
                        يشمل الحفظ كل الأقسام حتى التي لا تظهر حالياً.
                    </p>
                </div>
            </div>
        </form>
    </div>
</template>
