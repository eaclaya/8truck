<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Copy, Plus } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import ShipmentStatusBadge from '@/components/ShipmentStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { dashboard } from '@/routes';
import { advance } from '@/routes/jobs';
import jobRoutes from '@/routes/jobs';
import loadRoutes from '@/routes/loads';
import regionRoutes from '@/routes/regions';
import shipmentRoutes from '@/routes/shipments';
import truckRoutes, { update as updateTruck } from '@/routes/trucks';

interface AttentionRow {
    id: number;
    origin_city: string;
    destination_city: string;
    status: string;
    pending_quotes: number;
    action: 'review_quotes' | 'confirm_delivery';
}

interface JobRow {
    id: number;
    origin_city: string;
    destination_city: string;
    status: string;
    pickup_date: string;
    next_status: string | null;
}

interface TruckChip {
    id: number;
    plate_number: string;
    truck_type: string | null;
    availability: string;
}

interface LoadRow {
    id: number;
    origin_city: string;
    destination_city: string;
    pickup_date: string;
    budget_amount: string | null;
    currency: string;
}

defineProps<{
    customer: {
        stats: {
            draft: number;
            awaiting: number;
            inProgress: number;
            completed: number;
        };
        attention: AttentionRow[];
    };
    transporter: {
        trucks: TruckChip[];
        stats: {
            loads: number;
            pendingQuotes: number;
            activeJobs: number;
            completedJobs: number;
        };
        jobs: JobRow[];
        loads: LoadRow[];
    } | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const { t } = useTrans();

function toggleTruckAvailability(truck: TruckChip) {
    router.patch(
        updateTruck.url(truck.id),
        {
            availability:
                truck.availability === 'available' ? 'busy' : 'available',
        },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="t('Dashboard')" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <section class="grid gap-3">
            <div class="flex items-center justify-between">
                <h2 class="font-medium">{{ t('My shipments') }}</h2>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="shipmentRoutes.index()">{{
                        t('View all')
                    }}</Link>
                </Button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p class="text-2xl font-semibold">
                        {{ customer.stats.draft }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ t('Drafts') }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p class="text-2xl font-semibold">
                        {{ customer.stats.awaiting }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ t('Awaiting quotes') }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p class="text-2xl font-semibold">
                        {{ customer.stats.inProgress }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ t('In progress') }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p class="text-2xl font-semibold">
                        {{ customer.stats.completed }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ t('Completed shipments') }}
                    </p>
                </div>
            </div>

            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <h3 class="mb-2 text-sm font-medium">
                    {{ t('Needs your attention') }}
                </h3>
                <p
                    v-if="customer.attention.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('All caught up.') }}
                </p>
                <ul v-else class="divide-y divide-sidebar-border/40">
                    <li
                        v-for="row in customer.attention"
                        :key="row.id"
                        class="flex flex-wrap items-center justify-between gap-2 py-2"
                    >
                        <Link
                            :href="shipmentRoutes.show(row.id)"
                            class="font-medium hover:underline"
                        >
                            {{ row.origin_city }} → {{ row.destination_city }}
                        </Link>
                        <span class="flex items-center gap-2 text-sm">
                            <ShipmentStatusBadge :status="row.status" />
                            <span
                                v-if="row.action === 'review_quotes'"
                                class="text-muted-foreground"
                            >
                                {{ row.pending_quotes }} {{ t('pending') }} ·
                                {{ t('Review quotes') }}
                            </span>
                            <span v-else class="text-muted-foreground">
                                {{ t('Confirm delivery') }}
                            </span>
                        </span>
                    </li>
                </ul>
            </div>
        </section>

        <section v-if="transporter" class="grid gap-3">
            <div class="flex items-center justify-between">
                <h2 class="font-medium">{{ t('My jobs') }}</h2>
                <Button as-child variant="ghost" size="sm">
                    <Link :href="jobRoutes.index()">{{ t('View all') }}</Link>
                </Button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p class="text-2xl font-semibold">
                        {{ transporter.stats.loads }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ t('Available loads') }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p class="text-2xl font-semibold">
                        {{ transporter.stats.pendingQuotes }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ t('Pending quotes') }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p class="text-2xl font-semibold">
                        {{ transporter.stats.activeJobs }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ t('Active jobs') }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p class="text-2xl font-semibold">
                        {{ transporter.stats.completedJobs }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ t('Completed jobs') }}
                    </p>
                </div>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <div
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <h3 class="mb-2 text-sm font-medium">
                        {{ t('Active jobs') }}
                    </h3>
                    <p
                        v-if="transporter.jobs.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{
                            t('No jobs yet. Quote available loads to win work.')
                        }}
                    </p>
                    <ul v-else class="divide-y divide-sidebar-border/40">
                        <li
                            v-for="job in transporter.jobs"
                            :key="job.id"
                            class="flex flex-wrap items-center justify-between gap-2 py-2"
                        >
                            <Link
                                :href="jobRoutes.index()"
                                class="font-medium hover:underline"
                            >
                                {{ job.origin_city }} →
                                {{ job.destination_city }}
                            </Link>
                            <span
                                class="flex items-center gap-2 text-sm text-muted-foreground"
                            >
                                {{ job.pickup_date }}
                                <ShipmentStatusBadge :status="job.status" />
                            </span>
                        </li>
                    </ul>
                </div>

                <div
                    class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <h3 class="mb-2 text-sm font-medium">
                        {{ t('Latest loads in your regions') }}
                    </h3>
                    <p
                        v-if="transporter.loads.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{
                            t(
                                'No loads available right now. Check back soon or widen your regions.',
                            )
                        }}
                    </p>
                    <ul v-else class="divide-y divide-sidebar-border/40">
                        <li
                            v-for="load in transporter.loads"
                            :key="load.id"
                            class="flex flex-wrap items-center justify-between gap-2 py-2"
                        >
                            <Link
                                :href="loadRoutes.show(load.id)"
                                class="font-medium hover:underline"
                            >
                                {{ load.origin_city }} →
                                {{ load.destination_city }}
                            </Link>
                            <span class="text-sm text-muted-foreground">
                                {{ load.pickup_date }}
                                <template v-if="load.budget_amount">
                                    · {{ load.currency }}
                                    {{
                                        Number(
                                            load.budget_amount,
                                        ).toLocaleString()
                                    }}
                                </template>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
</template>
