<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Layers, Plus, Tag, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { dashboard } from '@/routes';

type CategoryRow = { value: string; label: string; products_count: number };

const props = defineProps<{
    categories: CategoryRow[];
    addableCategories: { value: string; label: string }[];
}>();

const page = usePage<{ flash?: { success?: string | null; error?: string | null } }>();
const totalProducts = computed(() => props.categories.reduce((sum, c) => sum + c.products_count, 0));

const addForm = useForm({
    category: props.addableCategories[0]?.value ?? '',
});
const deleteForm = useForm({});

function addCategory(): void {
    if (props.addableCategories.length === 0) {
        window.alert('لا يمكن إضافة صنف جديد الآن لأن كل الأصناف الأساسية مفعلة بالفعل.');
        return;
    }

    if (addForm.category === '') {
        addForm.category = props.addableCategories[0]?.value ?? '';
    }

    if (addForm.category === '') {
        return;
    }
    addForm.post('/merchant/categories', {
        preserveScroll: true,
    });
}

function removeCategory(value: string): void {
    if (!window.confirm('سيتم حذف الصنف ونقل منتجاته لصنف بديل. هل تريد المتابعة؟')) {
        return;
    }
    deleteForm.delete(`/merchant/categories/${encodeURIComponent(value)}`, {
        preserveScroll: true,
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'الرئيسية', href: dashboard() },
            { title: 'الأصناف', href: '/merchant/categories' },
        ],
    },
});
</script>

<template>
    <Head title="أصناف المتجر" />

    <div class="flex h-full flex-1 flex-col gap-5 overflow-x-auto rounded-xl p-4" dir="rtl" lang="ar">
        <div
            class="relative overflow-hidden rounded-2xl border border-violet-200/60 bg-gradient-to-l from-violet-50 to-white p-5 dark:border-violet-500/20 dark:from-violet-950/40 dark:to-card"
        >
            <div class="absolute -left-8 -top-8 size-28 rounded-full bg-violet-500/10" aria-hidden="true" />
            <div class="relative flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-start gap-3">
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300"
                        aria-hidden="true"
                    >
                        <Layers class="size-5" stroke-width="2" />
                    </div>
                    <div class="min-w-0 space-y-1 text-start">
                        <h1 class="text-lg font-semibold text-foreground">إدارة أصناف المتجر</h1>
                        <p class="text-sm leading-relaxed text-muted-foreground">
                            تحكم في الأصناف الظاهرة للعملاء، مع إمكانية الإضافة والحذف وإعادة توزيع المنتجات تلقائيًا.
                        </p>
                    </div>
                </div>

                <Button as-child variant="outline" class="shrink-0">
                    <Link href="/merchant/products">المنتجات</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">الأصناف النشطة</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-foreground">{{ categories.length }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">إجمالي المنتجات داخل الأصناف</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-foreground">{{ totalProducts }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">أصناف متاحة للإضافة</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-foreground">{{ addableCategories.length }}</p>
            </div>
        </div>

        <div
            v-if="page.props.flash?.success"
            class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100"
        >
            {{ page.props.flash.success }}
        </div>

        <div
            v-if="page.props.flash?.error"
            class="rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 text-sm text-red-900 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-100"
        >
            {{ page.props.flash.error }}
        </div>

        <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div class="w-full space-y-2 md:max-w-sm">
                    <label class="text-sm font-medium text-foreground">إضافة صنف جديد</label>
                    <Select v-model="addForm.category" :disabled="addableCategories.length === 0">
                        <SelectTrigger class="text-start">
                            <SelectValue placeholder="اختر الصنف" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in addableCategories"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="addForm.errors.category" />
                </div>

                <Button
                    class="gap-2 bg-primary text-primary-foreground hover:bg-primary/90"
                    :disabled="addForm.processing"
                    @click="addCategory"
                >
                    <Plus class="size-4" />
                    إضافة صنف جديد
                </Button>
            </div>
            <p v-if="addableCategories.length === 0" class="mt-2 text-xs text-muted-foreground">
                لا يوجد أصناف متاحة للإضافة الآن لأن كل الأصناف الأساسية مفعلة بالفعل.
            </p>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/40 text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">الصنف</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">عدد المنتجات</th>
                        <th class="px-4 py-3 text-start font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="c in categories"
                        :key="c.value"
                        class="border-t border-sidebar-border/60 transition hover:bg-muted/20 dark:border-sidebar-border"
                    >
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex size-7 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                    <Tag class="size-4" />
                                </span>
                                <div>
                                    <p class="font-medium text-foreground">{{ c.label }}</p>
                                    <p class="text-xs text-muted-foreground">{{ c.value }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-500/30 dark:text-emerald-300"
                            >
                                نشط
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="tabular-nums text-foreground">{{ c.products_count }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <Button as-child variant="outline" size="sm">
                                    <Link :href="`/merchant/products?category=${encodeURIComponent(c.value)}`">
                                        عرض المنتجات
                                    </Link>
                                </Button>
                                <Button variant="destructive" size="sm" class="gap-1" @click="removeCategory(c.value)">
                                    <Trash2 class="size-4" />
                                    حذف
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="categories.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                            لا توجد أصناف حالياً.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
