<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ImagePlus, MoreHorizontal, Pencil, Plus, Trash2, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

export type ProductRow = {
    id: number;
    name: string;
    description: string;
    price: number;
    images: { id: number; url: string }[];
};

const props = defineProps<{
    products: ProductRow[];
    productCurrencyAr: string;
    productCurrencyEn: string;
}>();

const productsCount = computed(() => props.products.length);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'الرئيسية', href: dashboard() },
            { title: 'المنتجات', href: '/merchant/products' },
        ],
    },
});

const page = usePage<{ flash: { success?: string | null; error?: string | null } }>();

const addOpen = ref(false);
const imagesInputRef = ref<HTMLInputElement | null>(null);

const selectedFiles = ref<File[]>([]);
const previewUrls = ref<string[]>([]);

const editOpen = ref(false);
const editingProduct = ref<ProductRow | null>(null);
const editImagesInputRef = ref<HTMLInputElement | null>(null);
const editSelectedFiles = ref<File[]>([]);
const editPreviewUrls = ref<string[]>([]);

const form = useForm({
    name: '',
    description: '',
    price: '',
    images: [] as File[],
});

const editForm = useForm({
    name: '',
    description: '',
    price: '',
    remove_image_ids: [] as number[],
    images: [] as File[],
});

function revokeAllPreviews(): void {
    previewUrls.value.forEach((u) => URL.revokeObjectURL(u));
    previewUrls.value = [];
}

function revokeEditPreviews(): void {
    editPreviewUrls.value.forEach((u) => URL.revokeObjectURL(u));
    editPreviewUrls.value = [];
}

function rebuildPreviews(files: File[]): void {
    revokeAllPreviews();
    previewUrls.value = files.map((f) => URL.createObjectURL(f));
}

function onImagesChange(e: Event): void {
    const input = e.target as HTMLInputElement;
    const picked = Array.from(input.files ?? []);
    const merged = [...selectedFiles.value, ...picked].slice(0, 10);
    selectedFiles.value = merged;
    rebuildPreviews(merged);
    input.value = '';
}

function removeSelectedImage(index: number): void {
    const next = [...selectedFiles.value];
    next.splice(index, 1);
    selectedFiles.value = next;
    rebuildPreviews(next);
}

function triggerImagesPick(): void {
    imagesInputRef.value?.click();
}

const canSubmit = computed(
    () =>
        form.name.trim().length >= 1
        && form.description.trim().length >= 1
        && form.price !== ''
        && Number.isFinite(Number(form.price))
        && Number(form.price) >= 0
        && selectedFiles.value.length >= 1,
);

function resetAddProductForm(): void {
    form.reset();
    form.clearErrors();
    selectedFiles.value = [];
    revokeAllPreviews();
    if (imagesInputRef.value) {
        imagesInputRef.value.value = '';
    }
}

function submitProduct(): void {
    if (!canSubmit.value) {
        return;
    }
    form.images = [...selectedFiles.value];
    form.post('/merchant/products', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            addOpen.value = false;
            resetAddProductForm();
        },
    });
}

function onDialogOpenChange(open: boolean): void {
    addOpen.value = open;
    if (!open && !form.processing) {
        resetAddProductForm();
    }
}

function onEditImagesChange(e: Event): void {
    const input = e.target as HTMLInputElement;
    const picked = Array.from(input.files ?? []);
    if (!editingProduct.value) {
        input.value = '';
        return;
    }
    const kept = editingProduct.value.images.filter(
        (img) => !editForm.remove_image_ids.includes(img.id),
    ).length;
    const maxNew = Math.max(0, 10 - kept);
    const merged = [...editSelectedFiles.value, ...picked].slice(0, maxNew);
    editSelectedFiles.value = merged;
    revokeEditPreviews();
    editPreviewUrls.value = merged.map((f) => URL.createObjectURL(f));
    input.value = '';
}

function removeEditSelectedImage(index: number): void {
    const next = [...editSelectedFiles.value];
    next.splice(index, 1);
    editSelectedFiles.value = next;
    revokeEditPreviews();
    editPreviewUrls.value = next.map((f) => URL.createObjectURL(f));
}

function triggerEditImagesPick(): void {
    editImagesInputRef.value?.click();
}

function openEdit(product: ProductRow): void {
    editingProduct.value = product;
    editForm.clearErrors();
    editForm.name = product.name;
    editForm.description = product.description;
    editForm.price = String(product.price);
    editForm.remove_image_ids = [];
    editForm.images = [];
    editSelectedFiles.value = [];
    revokeEditPreviews();
    if (editImagesInputRef.value) {
        editImagesInputRef.value.value = '';
    }
    editOpen.value = true;
}

function trimEditNewFilesToCap(): void {
    if (!editingProduct.value) {
        return;
    }
    const kept = editingProduct.value.images.filter(
        (img) => !editForm.remove_image_ids.includes(img.id),
    ).length;
    const maxNew = Math.max(0, 10 - kept);
    if (editSelectedFiles.value.length <= maxNew) {
        return;
    }
    const next = editSelectedFiles.value.slice(0, maxNew);
    editSelectedFiles.value = next;
    revokeEditPreviews();
    editPreviewUrls.value = next.map((f) => URL.createObjectURL(f));
}

function toggleRemoveExistingImage(imageId: number): void {
    const arr = editForm.remove_image_ids;
    const idx = arr.indexOf(imageId);
    if (idx === -1) {
        arr.push(imageId);
    } else {
        arr.splice(idx, 1);
    }
    trimEditNewFilesToCap();
}

function isExistingImageMarkedRemoved(imageId: number): boolean {
    return editForm.remove_image_ids.includes(imageId);
}

const editKeptImageCount = computed(() => {
    if (!editingProduct.value) {
        return 0;
    }
    return editingProduct.value.images.filter((img) => !editForm.remove_image_ids.includes(img.id)).length;
});

const editTotalImageCount = computed(() => editKeptImageCount.value + editSelectedFiles.value.length);

const canSubmitEdit = computed(
    () =>
        editingProduct.value !== null
        && editForm.name.trim().length >= 1
        && editForm.description.trim().length >= 1
        && editForm.price !== ''
        && Number.isFinite(Number(editForm.price))
        && Number(editForm.price) >= 0
        && editTotalImageCount.value >= 1
        && editTotalImageCount.value <= 10,
);

function resetEditForm(): void {
    editForm.reset();
    editForm.clearErrors();
    editingProduct.value = null;
    editSelectedFiles.value = [];
    revokeEditPreviews();
    if (editImagesInputRef.value) {
        editImagesInputRef.value.value = '';
    }
}

function submitEdit(): void {
    if (!editingProduct.value || !canSubmitEdit.value) {
        return;
    }
    editForm.images = [...editSelectedFiles.value];
    editForm.patch(`/merchant/products/${editingProduct.value.id}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            editOpen.value = false;
            resetEditForm();
        },
    });
}

function onEditDialogOpenChange(open: boolean): void {
    editOpen.value = open;
    if (!open && !editForm.processing) {
        resetEditForm();
    }
}

function confirmDeleteProduct(product: ProductRow): void {
    if (
        !window.confirm(
            `حذف المنتج «${product.name}»؟ لا يمكن التراجع عن هذا الإجراء.`,
        )
    ) {
        return;
    }
    router.delete(`/merchant/products/${product.id}`, {
        preserveScroll: true,
    });
}

onBeforeUnmount(() => {
    revokeAllPreviews();
    revokeEditPreviews();
});

function formatPrice(value: number): string {
    const n = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(value);
    return `${n} ${props.productCurrencyEn}`;
}

const flashSuccess = computed(() => page.props.flash?.success ?? null);
</script>

<template>
    <Head title="المنتجات" />

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

        <div
            class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-6 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border"
        >
            <p class="text-sm text-muted-foreground">
                <span class="font-medium text-foreground">عدد المنتجات:</span>
                <span class="ms-1 inline-flex min-w-[1.25rem] tabular-nums text-foreground" dir="ltr">{{
                    productsCount
                }}</span>
            </p>

            <Dialog :open="addOpen" @update:open="onDialogOpenChange">
                <DialogTrigger as-child>
                    <Button
                        class="shrink-0 gap-2 bg-[#111111] text-white hover:bg-[#222222] dark:bg-[#E6E5E2] dark:text-[#111111] dark:hover:bg-white"
                    >
                        <Plus class="size-4" stroke-width="2" />
                        إضافة منتج جديد
                    </Button>
                </DialogTrigger>
                <DialogContent
                    class="max-h-[min(90vh,720px)] overflow-y-auto sm:max-w-lg"
                    dir="rtl"
                    lang="ar"
                >
                    <DialogHeader class="text-right sm:text-right">
                        <DialogTitle>منتج جديد</DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-4 py-2">
                        <div class="grid gap-2">
                            <Label for="product-name">اسم المنتج</Label>
                            <Input
                                id="product-name"
                                v-model="form.name"
                                name="name"
                                type="text"
                                autocomplete="off"
                                class="text-start"
                                dir="auto"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="product-price">
                                السعر
                                <span class="text-muted-foreground" dir="ltr"
                                    >({{ productCurrencyEn }} — {{ productCurrencyAr }})</span
                                >
                            </Label>
                            <Input
                                id="product-price"
                                v-model="form.price"
                                name="price"
                                type="number"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                class="text-start"
                                dir="ltr"
                            />
                            <InputError :message="form.errors.price" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="product-description">الوصف</Label>
                            <textarea
                                id="product-description"
                                v-model="form.description"
                                name="description"
                                rows="4"
                                class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:bg-input/30 flex min-h-[100px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                dir="auto"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label>صور المنتج</Label>
                            <input
                                ref="imagesInputRef"
                                type="file"
                                name="images"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                multiple
                                class="sr-only"
                                @change="onImagesChange"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                class="h-auto flex-wrap gap-2 py-3"
                                @click="triggerImagesPick"
                            >
                                <ImagePlus class="size-4 shrink-0" stroke-width="2" />
                                <span>اختر صوراً ({{ selectedFiles.length }}/10)</span>
                            </Button>
                            <p v-if="selectedFiles.length === 0" class="text-xs text-muted-foreground">
                                يجب إرفاق صورة واحدة على الأقل.
                            </p>
                            <InputError :message="form.errors.images" />
                            <ul
                                v-if="Object.keys(form.errors).some((k) => k.startsWith('images.'))"
                                class="text-xs text-destructive"
                            >
                                <li
                                    v-for="(msg, key) in form.errors"
                                    v-show="key.startsWith('images.')"
                                    :key="key"
                                >
                                    {{ msg }}
                                </li>
                            </ul>

                            <div
                                v-if="previewUrls.length > 0"
                                class="grid grid-cols-3 gap-2 sm:grid-cols-4"
                            >
                                <div
                                    v-for="(url, i) in previewUrls"
                                    :key="`${url}-${i}`"
                                    class="group relative aspect-square overflow-hidden rounded-lg border border-sidebar-border/70 bg-muted/30 dark:border-sidebar-border"
                                >
                                    <img
                                        :src="url"
                                        alt=""
                                        class="size-full object-cover"
                                    />
                                    <button
                                        type="button"
                                        class="absolute end-1 top-1 flex size-7 items-center justify-center rounded-md bg-black/55 text-white opacity-0 transition group-hover:opacity-100"
                                        @click="removeSelectedImage(i)"
                                    >
                                        <X class="size-4" stroke-width="2.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="gap-2 sm:justify-start">
                        <Button
                            type="button"
                            class="bg-[#111111] text-white hover:bg-[#222222] dark:bg-[#E6E5E2] dark:text-[#111111] dark:hover:bg-white"
                            :disabled="!canSubmit || form.processing"
                            @click="submitProduct"
                        >
                            {{ form.processing ? 'جاري الحفظ…' : 'حفظ المنتج' }}
                        </Button>
                        <Button type="button" variant="outline" @click="onDialogOpenChange(false)">
                            إلغاء
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog :open="editOpen" @update:open="onEditDialogOpenChange">
                <DialogContent
                    class="max-h-[min(90vh,720px)] overflow-y-auto sm:max-w-lg"
                    dir="rtl"
                    lang="ar"
                >
                    <DialogHeader class="text-right sm:text-right">
                        <DialogTitle>تعديل منتج</DialogTitle>
                    </DialogHeader>

                    <div v-if="editingProduct" class="grid gap-4 py-2">
                        <div class="grid gap-2">
                            <Label for="edit-product-name">اسم المنتج</Label>
                            <Input
                                id="edit-product-name"
                                v-model="editForm.name"
                                name="name"
                                type="text"
                                autocomplete="off"
                                class="text-start"
                                dir="auto"
                            />
                            <InputError :message="editForm.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-product-price">
                                السعر
                                <span class="text-muted-foreground" dir="ltr"
                                    >({{ productCurrencyEn }} — {{ productCurrencyAr }})</span
                                >
                            </Label>
                            <Input
                                id="edit-product-price"
                                v-model="editForm.price"
                                name="price"
                                type="number"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                class="text-start"
                                dir="ltr"
                            />
                            <InputError :message="editForm.errors.price" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit-product-description">الوصف</Label>
                            <textarea
                                id="edit-product-description"
                                v-model="editForm.description"
                                name="description"
                                rows="4"
                                class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:bg-input/30 flex min-h-[100px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                dir="auto"
                            />
                            <InputError :message="editForm.errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label>صور المنتج الحالية</Label>
                            <p class="text-xs text-muted-foreground">
                                اضغط على الصورة لإزالتها أو لاستعادها. يجب أن يبقى صورة واحدة على الأقل في
                                المجموع.
                            </p>
                            <div
                                class="grid grid-cols-3 gap-2 sm:grid-cols-4"
                            >
                                <button
                                    v-for="img in editingProduct.images"
                                    :key="img.id"
                                    type="button"
                                    class="group relative aspect-square overflow-hidden rounded-lg border-2 bg-muted/30 transition"
                                    :class="
                                        isExistingImageMarkedRemoved(img.id)
                                            ? 'border-destructive/80 opacity-50 ring-2 ring-destructive/30'
                                            : 'border-sidebar-border/70 dark:border-sidebar-border'
                                    "
                                    @click="toggleRemoveExistingImage(img.id)"
                                >
                                    <img
                                        :src="img.url"
                                        alt=""
                                        class="size-full object-cover"
                                    />
                                    <span
                                        class="absolute inset-x-0 bottom-0 bg-black/60 py-0.5 text-center text-[10px] font-medium text-white"
                                    >
                                        {{
                                            isExistingImageMarkedRemoved(img.id)
                                                ? 'مُستبعدة — اضغط للإلغاء'
                                                : 'مُدرَجة — اضغط للإزالة'
                                        }}
                                    </span>
                                </button>
                            </div>
                            <InputError :message="editForm.errors.remove_image_ids" />
                        </div>

                        <div class="grid gap-2">
                            <Label>إضافة صور جديدة (اختياري)</Label>
                            <input
                                ref="editImagesInputRef"
                                type="file"
                                name="images"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                multiple
                                class="sr-only"
                                @change="onEditImagesChange"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                class="h-auto flex-wrap gap-2 py-3"
                                @click="triggerEditImagesPick"
                            >
                                <ImagePlus class="size-4 shrink-0" stroke-width="2" />
                                <span
                                    >إضافة صور ({{ editSelectedFiles.length }} جديدة —
                                    {{ editTotalImageCount }}/10 إجمالي)</span
                                >
                            </Button>
                            <InputError :message="editForm.errors.images" />
                            <ul
                                v-if="Object.keys(editForm.errors).some((k) => k.startsWith('images.'))"
                                class="text-xs text-destructive"
                            >
                                <li
                                    v-for="(msg, key) in editForm.errors"
                                    v-show="key.startsWith('images.')"
                                    :key="key"
                                >
                                    {{ msg }}
                                </li>
                            </ul>

                            <div
                                v-if="editPreviewUrls.length > 0"
                                class="grid grid-cols-3 gap-2 sm:grid-cols-4"
                            >
                                <div
                                    v-for="(url, i) in editPreviewUrls"
                                    :key="`${url}-${i}`"
                                    class="group relative aspect-square overflow-hidden rounded-lg border border-sidebar-border/70 bg-muted/30 dark:border-sidebar-border"
                                >
                                    <img
                                        :src="url"
                                        alt=""
                                        class="size-full object-cover"
                                    />
                                    <button
                                        type="button"
                                        class="absolute end-1 top-1 flex size-7 items-center justify-center rounded-md bg-black/55 text-white opacity-0 transition group-hover:opacity-100"
                                        @click="removeEditSelectedImage(i)"
                                    >
                                        <X class="size-4" stroke-width="2.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="gap-2 sm:justify-start">
                        <Button
                            type="button"
                            class="bg-[#111111] text-white hover:bg-[#222222] dark:bg-[#E6E5E2] dark:text-[#111111] dark:hover:bg-white"
                            :disabled="!canSubmitEdit || editForm.processing"
                            @click="submitEdit"
                        >
                            {{ editForm.processing ? 'جاري الحفظ…' : 'حفظ التعديلات' }}
                        </Button>
                        <Button type="button" variant="outline" @click="onEditDialogOpenChange(false)">
                            إلغاء
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <div
            v-if="products.length === 0"
            class="rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
        >
            <p class="text-sm text-muted-foreground">
                لا توجد منتجات بعد. اضغط «إضافة منتج جديد» للبدء.
            </p>
        </div>

        <div
            v-else
            class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[58rem] table-fixed border-collapse text-sm" dir="rtl">
                    <colgroup>
                        <col style="width: 7%;" />
                        <col style="width: 10%;" />
                        <col style="width: 18%;" />
                        <col style="width: 12%;" />
                        <col style="width: 30%;" />
                        <col style="width: 11%;" />
                        <col style="width: 12%;" />
                    </colgroup>
                    <thead>
                        <tr class="border-b border-border bg-muted/40" dir="rtl">
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-center text-sm font-medium text-muted-foreground"
                            >
                                رقم
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-center text-sm font-medium text-muted-foreground"
                            >
                                الصورة
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                اسم المنتج
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                السعر
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                الوصف
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-start text-sm font-medium text-muted-foreground"
                            >
                                عدد الصور
                            </th>
                            <th
                                scope="col"
                                class="box-border px-3 py-2 text-center text-sm font-medium text-muted-foreground"
                            >
                                <span class="sr-only">إجراءات</span>
                                <span aria-hidden="true" class="text-muted-foreground/70">⋯</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(product, index) in products"
                            :key="product.id"
                            class="border-b border-border/40 transition-colors hover:bg-muted/30"
                        >
                            <td class="box-border px-3 py-2 align-middle text-center" dir="rtl">
                                <span
                                    class="inline-block min-w-0 tabular-nums text-muted-foreground"
                                    dir="ltr"
                                >
                                    {{ index + 1 }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-center" dir="rtl">
                                <div class="flex justify-center">
                                    <div
                                        class="relative size-11 shrink-0 overflow-hidden rounded-lg border border-sidebar-border/60 bg-muted/40 dark:border-sidebar-border"
                                    >
                                        <img
                                            v-if="product.images[0]"
                                            :src="product.images[0].url"
                                            :alt="product.name"
                                            class="size-full object-cover"
                                            loading="lazy"
                                        />
                                        <div
                                            v-else
                                            class="flex size-full items-center justify-center text-[10px] text-muted-foreground"
                                        >
                                            —
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate font-medium text-foreground"
                                    dir="rtl"
                                    :title="product.name"
                                >
                                    {{ product.name }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate tabular-nums text-[#9a7349] dark:text-[#d4b896] text-right"
                                    dir="ltr"
                                >
                                    {{ formatPrice(product.price) }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate text-muted-foreground"
                                    dir="rtl"
                                    :title="product.description"
                                >
                                    {{ product.description }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-start" dir="rtl">
                                <span
                                    class="block min-w-0 truncate tabular-nums text-muted-foreground text-right"
                                    dir="ltr"
                                >
                                    {{ product.images.length }}
                                </span>
                            </td>
                            <td class="box-border px-3 py-2 align-middle text-center" dir="rtl">
                                <div class="flex justify-center">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                class="size-8 shrink-0 text-muted-foreground hover:text-foreground"
                                                :disabled="form.processing || editForm.processing"
                                            >
                                                <MoreHorizontal class="size-4" stroke-width="2" />
                                                <span class="sr-only">إجراءات المنتج</span>
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="w-48" dir="rtl">
                                            <DropdownMenuItem
                                                class="cursor-pointer"
                                                :disabled="form.processing || editForm.processing"
                                                @click="openEdit(product)"
                                            >
                                                <Pencil class="size-4 shrink-0" stroke-width="2" />
                                                تعديل
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                variant="destructive"
                                                class="cursor-pointer"
                                                :disabled="form.processing || editForm.processing"
                                                @click="confirmDeleteProduct(product)"
                                            >
                                                <Trash2 class="size-4 shrink-0" stroke-width="2" />
                                                حذف
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
