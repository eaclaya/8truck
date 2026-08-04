<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import RatingForm from '@/components/RatingForm.vue';
import ShipmentStatusBadge from '@/components/ShipmentStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { dashboard } from '@/routes';
import jobRoutes, { advance } from '@/routes/jobs';
import loadRoutes from '@/routes/loads';

interface ReturnTrip {
    id: number;
    origin_city: string | null;
    destination_city: string | null;
    pickup_date: string;
    budget_amount: string | null;
    currency: string;
}

interface JobRow {
    id: number;
    status: string;
    next_status: string | null;
    origin_city: string | null;
    destination_city: string | null;
    pickup_date: string;
    cargo_type: string;
    customer_name: string;
    amount: string | null;
    currency: string;
    can_rate: boolean;
    return_trips: ReturnTrip[];
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    jobs: {
        data: JobRow[];
        links: PaginationLink[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Jobs', href: jobRoutes.index() },
        ],
    },
});

const { t } = useTrans();
</script>

<template>
    <Head :title="t('My jobs')" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">{{ t('My jobs') }}</h1>

        <div
            v-if="jobs.data.length === 0"
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
        >
            <p class="text-muted-foreground">
                {{ t('No jobs yet. Quote available loads to win work.') }}
            </p>
        </div>

        <div
            v-else
            class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-sidebar-border/70 text-left text-muted-foreground dark:border-sidebar-border"
                    >
                        <th class="px-4 py-3 font-medium">{{ t('Route') }}</th>
                        <th class="px-4 py-3 font-medium">
                            {{ t('Customer') }}
                        </th>
                        <th class="px-4 py-3 font-medium">{{ t('Pickup') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Budget') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="job in jobs.data" :key="job.id">
                        <tr
                            class="border-b border-sidebar-border/40 hover:bg-muted/50 dark:border-sidebar-border/40"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ job.origin_city }} →
                                {{ job.destination_city }}
                            </td>
                            <td class="px-4 py-3">{{ job.customer_name }}</td>
                            <td class="px-4 py-3">{{ job.pickup_date }}</td>
                            <td class="px-4 py-3">
                                {{
                                    job.amount
                                        ? `${job.currency} ${Number(job.amount).toLocaleString()}`
                                        : '—'
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <ShipmentStatusBadge :status="job.status" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Form
                                    v-if="job.next_status"
                                    v-bind="advance.form(job.id)"
                                    v-slot="{ errors, processing }"
                                >
                                    <input
                                        type="hidden"
                                        name="status"
                                        :value="job.next_status"
                                    />
                                    <Button
                                        type="submit"
                                        size="sm"
                                        variant="outline"
                                        :disabled="processing"
                                    >
                                        <Spinner v-if="processing" />
                                        {{ t('advance.' + job.next_status) }}
                                    </Button>
                                    <InputError :message="errors.status" />
                                </Form>
                                <span
                                    v-else-if="job.status === 'delivered'"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ t('Waiting for customer confirmation') }}
                                </span>
                            </td>
                        </tr>
                        <tr
                            v-if="job.return_trips.length > 0"
                            class="border-b border-sidebar-border/40 bg-emerald-50/60 dark:border-sidebar-border/40 dark:bg-emerald-950/20"
                        >
                            <td colspan="6" class="px-4 py-2 text-sm">
                                <span
                                    class="font-medium text-emerald-700 dark:text-emerald-300"
                                >
                                    {{ t('Return load available') }}:
                                </span>
                                <template
                                    v-for="(trip, index) in job.return_trips"
                                    :key="trip.id"
                                >
                                    <span v-if="index > 0"> · </span>
                                    <Link
                                        :href="loadRoutes.show(trip.id)"
                                        class="underline underline-offset-2 hover:text-emerald-700 dark:hover:text-emerald-300"
                                    >
                                        {{ trip.origin_city }} →
                                        {{ trip.destination_city }} ({{
                                            trip.pickup_date
                                        }}<template v-if="trip.budget_amount"
                                            >, {{ trip.currency }}
                                            {{
                                                Number(
                                                    trip.budget_amount,
                                                ).toLocaleString()
                                            }}</template
                                        >)
                                    </Link>
                                </template>
                            </td>
                        </tr>
                        <tr
                            v-if="job.can_rate"
                            class="border-b border-sidebar-border/40 dark:border-sidebar-border/40"
                        >
                            <td colspan="6" class="px-4 py-3">
                                <RatingForm
                                    :shipment-id="job.id"
                                    title="Rate customer"
                                    class="max-w-sm"
                                />
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>
