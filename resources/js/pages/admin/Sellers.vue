<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type SellerRow = {
    id: number;
    name: string;
    phone: string | null;
    whatsapp_phone: string | null;
    instapay_wallet: string | null;
    subscription_paid_amount: string | null;
    merchant_access_months: number | null;
    created_at: string | null;
    subscription_payment_reported_at: string | null;
    access_ends_at: string | null;
    access_expired: boolean;
};

const props = defineProps<{
    sellers: SellerRow[];
    subscriptionCurrencyEn: string;
    platformSubscriptionAmount: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Sellers', href: '/admin/sellers' },
        ],
    },
});

const addOpen = ref(false);
const logoInputRef = ref<HTMLInputElement | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    subscription_paid_amount: '',
    merchant_access_months: 1,
    instapay_wallet: '',
    whatsapp_phone: '',
    phone: '',
    address: '',
    store_logo: null as File | null,
});

function onLogoChange(e: Event): void {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0] ?? null;
    form.store_logo = f;
}

function submitAdd(): void {
    form.post('/admin/sellers', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            addOpen.value = false;
            form.reset();
            form.merchant_access_months = 1;
            form.subscription_paid_amount = '';
            if (logoInputRef.value) {
                logoInputRef.value.value = '';
            }
        },
    });
}

function onOpenChange(open: boolean): void {
    addOpen.value = open;
    if (!open) {
        form.clearErrors();
    }
}

function formatDateEn(iso: string | null): string {
    if (!iso) {
        return '—';
    }
    try {
        return new Intl.DateTimeFormat('en-GB', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}

function formatAmountDisplay(amount: string | null): string {
    if (amount == null || amount === '') {
        return '—';
    }
    const n = Number(amount);
    if (Number.isNaN(n)) {
        return `${amount} ${props.subscriptionCurrencyEn}`;
    }
    return `${n.toLocaleString('en-GB', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} ${props.subscriptionCurrencyEn}`;
}

function termLabel(months: number | null): string {
    if (months == null || months < 1) {
        return 'Days (platform)';
    }
    return `${months} mo`;
}

/** Recorded paid amount, or platform subscription fee if they completed online payment proof. */
function amountPaidForRow(row: SellerRow): string {
    if (row.subscription_paid_amount != null && row.subscription_paid_amount !== '') {
        return formatAmountDisplay(row.subscription_paid_amount);
    }
    if (row.subscription_payment_reported_at) {
        return formatAmountDisplay(String(props.platformSubscriptionAmount));
    }
    return '—';
}

function deleteSeller(row: SellerRow): void {
    if (
        !confirm(
            `Delete merchant "${row.name}"? This cannot be undone. All account data will be removed.`,
        )
    ) {
        return;
    }
    router.delete(`/admin/sellers/${row.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Sellers" />

    <div lang="en" dir="ltr" class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-6 sm:flex-row sm:items-start sm:justify-between dark:border-sidebar-border">
            <div class="min-w-0">
                <h1 class="text-lg font-semibold tracking-tight">Sellers</h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Approved sellers. Merchants who register online use the platform
                    <strong>day-based</strong>
                    access window from signup unless you add them manually. When you use
                    <strong>Add merchant</strong>, enter the
                    <strong>amount paid</strong>
                    and subscription length in
                    <strong>months</strong>
                    — access ends on that calendar basis from account creation.
                </p>
            </div>
            <Dialog :open="addOpen" @update:open="onOpenChange">
                <DialogTrigger as-child>
                    <Button type="button" class="shrink-0 gap-2" variant="default">
                        <Plus class="size-4" stroke-width="2" />
                        Add merchant
                    </Button>
                </DialogTrigger>
                <DialogContent class="max-h-[min(90vh,720px)] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Add merchant</DialogTitle>
                        <DialogDescription>
                            Creates a verified, active seller. Set how much they paid and how many months their access
                            should last. Share the password securely.
                        </DialogDescription>
                    </DialogHeader>

                    <form class="space-y-4" @submit.prevent="submitAdd">
                        <hr role="presentation" class="border-border" />
                        <div class="grid gap-2">
                            <Label for="admin-seller-name">Merchant / store name</Label>
                            <input
                                id="admin-seller-name"
                                v-model="form.name"
                                type="text"
                                name="name"
                                required
                                autocomplete="organization"
                                class="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <InputError :message="form.errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="admin-seller-email">Email</Label>
                            <input
                                id="admin-seller-email"
                                v-model="form.email"
                                type="email"
                                name="email"
                                required
                                autocomplete="email"
                                class="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                dir="ltr"
                            />
                            <InputError :message="form.errors.email" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="admin-seller-password">Password</Label>
                            <input
                                id="admin-seller-password"
                                v-model="form.password"
                                type="password"
                                name="password"
                                required
                                autocomplete="new-password"
                                class="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                dir="ltr"
                            />
                            <InputError :message="form.errors.password" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="admin-seller-password-confirm">Confirm password</Label>
                            <input
                                id="admin-seller-password-confirm"
                                v-model="form.password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                                class="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                dir="ltr"
                            />
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 sm:gap-3">
                            <div class="grid gap-2">
                                <Label for="admin-seller-paid">Amount paid</Label>
                                <input
                                    id="admin-seller-paid"
                                    v-model="form.subscription_paid_amount"
                                    type="text"
                                    inputmode="decimal"
                                    name="subscription_paid_amount"
                                    required
                                    placeholder="e.g. 500"
                                    class="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    dir="ltr"
                                />
                                <p class="text-muted-foreground text-xs">
                                    In {{ subscriptionCurrencyEn }} (record only).
                                </p>
                                <InputError :message="form.errors.subscription_paid_amount" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="admin-seller-months">Subscription (months)</Label>
                                <input
                                    id="admin-seller-months"
                                    v-model.number="form.merchant_access_months"
                                    type="number"
                                    name="merchant_access_months"
                                    min="1"
                                    max="120"
                                    required
                                    class="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    dir="ltr"
                                />
                                <p class="text-muted-foreground text-xs">
                                    Access ends this many months after account creation.
                                </p>
                                <InputError :message="form.errors.merchant_access_months" />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="admin-seller-instapay">InstaPay wallet</Label>
                            <input
                                id="admin-seller-instapay"
                                v-model="form.instapay_wallet"
                                type="text"
                                name="instapay_wallet"
                                required
                                class="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                dir="ltr"
                            />
                            <InputError :message="form.errors.instapay_wallet" />
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 sm:gap-3">
                            <div class="grid gap-2">
                                <Label for="admin-seller-phone">Phone</Label>
                                <input
                                    id="admin-seller-phone"
                                    v-model="form.phone"
                                    type="text"
                                    name="phone"
                                    required
                                    class="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    dir="ltr"
                                />
                                <InputError :message="form.errors.phone" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="admin-seller-whatsapp">WhatsApp</Label>
                                <input
                                    id="admin-seller-whatsapp"
                                    v-model="form.whatsapp_phone"
                                    type="text"
                                    name="whatsapp_phone"
                                    required
                                    class="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    dir="ltr"
                                />
                                <InputError :message="form.errors.whatsapp_phone" />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="admin-seller-address">Address</Label>
                            <textarea
                                id="admin-seller-address"
                                v-model="form.address"
                                name="address"
                                required
                                rows="3"
                                class="border-input flex min-h-[80px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <InputError :message="form.errors.address" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="admin-seller-logo">Store logo (optional)</Label>
                            <input
                                id="admin-seller-logo"
                                ref="logoInputRef"
                                type="file"
                                name="store_logo"
                                accept="image/*"
                                class="text-muted-foreground max-w-full text-sm file:me-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1 file:text-sm file:font-medium file:text-primary-foreground"
                                @change="onLogoChange"
                            />
                            <InputError :message="form.errors.store_logo" />
                        </div>

                        <DialogFooter class="gap-2 sm:gap-0">
                            <Button type="button" variant="outline" @click="addOpen = false"> Cancel </Button>
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? 'Creating…' : 'Create merchant' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <div
            v-if="sellers.length === 0"
            class="rounded-xl border border-dashed border-sidebar-border/70 p-10 text-center text-sm text-muted-foreground dark:border-sidebar-border"
        >
            No approved sellers yet. Use <strong>Add merchant</strong> to create one.
        </div>

        <div v-else class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full min-w-[1080px] text-start text-sm">
                <thead class="border-b border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Merchant name</th>
                        <th class="px-4 py-3 font-semibold">Amount paid</th>
                        <th class="px-4 py-3 font-semibold">Term</th>
                        <th class="px-4 py-3 font-semibold">Phone</th>
                        <th class="px-4 py-3 font-semibold">WhatsApp</th>
                        <th class="px-4 py-3 font-semibold">InstaPay</th>
                        <th class="px-4 py-3 font-semibold">Registered</th>
                        <th class="px-4 py-3 font-semibold">Payment reported</th>
                        <th class="px-4 py-3 font-semibold">Access ends</th>
                        <th class="px-4 py-3 font-semibold">Access</th>
                        <th class="px-4 py-3 font-semibold text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in sellers"
                        :key="row.id"
                        class="border-b border-sidebar-border/50 last:border-0 dark:border-sidebar-border/50"
                    >
                        <td class="px-4 py-3 font-medium" dir="auto">{{ row.name }}</td>
                        <td class="px-4 py-3 font-semibold tabular-nums text-foreground" dir="ltr">
                            {{ amountPaidForRow(row) }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground" dir="ltr">{{ termLabel(row.merchant_access_months) }}</td>
                        <td class="px-4 py-3 tabular-nums" dir="ltr">{{ row.phone ?? '—' }}</td>
                        <td class="px-4 py-3 tabular-nums" dir="ltr">{{ row.whatsapp_phone ?? '—' }}</td>
                        <td class="max-w-[160px] truncate px-4 py-3" dir="ltr" :title="row.instapay_wallet ?? undefined">
                            {{ row.instapay_wallet ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground" dir="ltr">{{ formatDateEn(row.created_at) }}</td>
                        <td class="px-4 py-3 text-muted-foreground" dir="ltr">
                            {{ formatDateEn(row.subscription_payment_reported_at) }}
                        </td>
                        <td class="px-4 py-3 font-medium tabular-nums text-foreground" dir="ltr">
                            {{ formatDateEn(row.access_ends_at) }}
                        </td>
                        <td class="px-4 py-3" dir="ltr">
                            <span
                                v-if="row.access_expired"
                                class="inline-flex rounded-full bg-destructive/15 px-2 py-0.5 text-xs font-semibold text-destructive"
                            >
                                Expired
                            </span>
                            <span
                                v-else
                                class="inline-flex rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400"
                            >
                                Active
                            </span>
                        </td>
                        <td class="px-4 py-3 text-end" dir="ltr">
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                class="h-8 text-xs"
                                @click="deleteSeller(row)"
                            >
                                Delete
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
