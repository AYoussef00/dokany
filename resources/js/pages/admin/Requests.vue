<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

type SubscriptionRequestItem = {
    id: number;
    name: string;
    email: string;
    reported_at: string | null;
    payment_proof_url: string | null;
};

const props = defineProps<{
    requests: SubscriptionRequestItem[];
    subscriptionAmount: number;
    subscriptionCurrencyEn: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Subscription requests', href: '/admin/requests' },
        ],
    },
});

function amountLabel(): string {
    const n = props.subscriptionAmount;
    const formatted = Number.isFinite(n) ? n.toLocaleString('en-GB') : String(n);
    return `${formatted} ${props.subscriptionCurrencyEn}`.trim();
}

function approve(id: number): void {
    router.post(`/admin/requests/${id}/approve`, {}, { preserveScroll: true });
}

function reject(id: number): void {
    if (
        !confirm(
            'Reject this request? This merchant will not be able to log in until you change their status.',
        )
    ) {
        return;
    }
    router.post(`/admin/requests/${id}/reject`, {}, { preserveScroll: true });
}

function formatDateEn(iso: string | null): string {
    if (!iso) {
        return '—';
    }
    try {
        return new Intl.DateTimeFormat('en-GB', {
            dateStyle: 'medium',
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}

function formatTimeEn(iso: string | null): string {
    if (!iso) {
        return '—';
    }
    try {
        return new Intl.DateTimeFormat('en-GB', {
            timeStyle: 'short',
            hour12: false,
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}
</script>

<template>
    <Head title="Subscription requests" />

    <div lang="en" dir="ltr" class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
            <h1 class="text-lg font-semibold tracking-tight">Subscription payment requests</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Review payment proofs. The amount shown is the platform subscription fee configured for this store.
                After approval, the merchant can log in. Merchant name is shown exactly as registered.
            </p>
        </div>

        <div
            v-if="props.requests.length === 0"
            class="rounded-xl border border-dashed border-sidebar-border/70 p-10 text-center text-sm text-muted-foreground dark:border-sidebar-border"
        >
            No requests pending review.
        </div>

        <div v-else class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full min-w-[880px] text-start text-sm">
                <thead class="border-b border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Merchant name</th>
                        <th class="px-4 py-3 font-semibold">Email</th>
                        <th class="px-4 py-3 font-semibold">Amount</th>
                        <th class="px-4 py-3 font-semibold">Date</th>
                        <th class="px-4 py-3 font-semibold">Time</th>
                        <th class="px-4 py-3 font-semibold">Receipt</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in props.requests"
                        :key="row.id"
                        class="border-b border-sidebar-border/50 last:border-0 dark:border-sidebar-border/50"
                    >
                        <td class="px-4 py-3 font-medium" dir="auto">{{ row.name }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ row.email }}</td>
                        <td class="px-4 py-3 tabular-nums" dir="ltr">{{ amountLabel() }}</td>
                        <td class="px-4 py-3 text-muted-foreground" dir="ltr">{{ formatDateEn(row.reported_at) }}</td>
                        <td class="px-4 py-3 text-muted-foreground tabular-nums" dir="ltr">
                            {{ formatTimeEn(row.reported_at) }}
                        </td>
                        <td class="px-4 py-3">
                            <a
                                v-if="row.payment_proof_url"
                                :href="row.payment_proof_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-semibold text-primary underline-offset-4 hover:underline"
                            >
                                View image
                            </a>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                    @click="approve(row.id)"
                                >
                                    Approve
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-destructive/50 bg-background px-3 py-1.5 text-xs font-semibold text-destructive transition hover:bg-destructive/10"
                                    @click="reject(row.id)"
                                >
                                    Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
