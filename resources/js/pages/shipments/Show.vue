<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import ShipmentStatusBadge from '@/components/ShipmentStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { accept } from '@/routes/quotes';
import shipments, { publish } from '@/routes/shipments';

interface ShipmentDetail {
    id: number;
    reference: string;
    status: string;
    origin_city: string | null;
    origin_address: string;
    destination_city: string | null;
    destination_address: string;
    pickup_date: string;
    cargo_type: string;
    truck_type: string | null;
    weight_kg: number | null;
    budget_amount: string | null;
    currency: string;
    special_instructions: string | null;
    published_at: string | null;
    accepted_quote_id: number | null;
}

interface QuoteRow {
    id: number;
    amount: string;
    currency: string;
    status: string;
    estimated_pickup_at: string | null;
    estimated_delivery_at: string | null;
    notes: string | null;
    transporter_name: string;
    transporter_rating: string;
    transporter_rating_count: number;
    truck: string | null;
}

interface HistoryRow {
    id: number;
    from_status: string | null;
    to_status: string;
    actor: string | null;
    notes: string | null;
    created_at: string | null;
}

defineProps<{
    shipment: ShipmentDetail;
    quotes: QuoteRow[];
    histories: HistoryRow[];
    can: {
        publish: boolean;
        acceptQuote: boolean;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Shipments', href: shipments.index() },
        ],
    },
});

const formatMoney = (amount: string, currency: string) =>
    `${currency} ${Number(amount).toLocaleString('es-HN', { minimumFractionDigits: 2 })}`;
</script>

<template>
    <Head
        :title="`Shipment ${shipment.origin_city} → ${shipment.destination_city}`"
    />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold">
                    {{ shipment.origin_city }} → {{ shipment.destination_city }}
                </h1>
                <ShipmentStatusBadge :status="shipment.status" />
            </div>

            <Form
                v-if="can.publish"
                v-bind="publish.form(shipment.id)"
                v-slot="{ errors, processing }"
            >
                <Button type="submit" :disabled="processing">
                    <Spinner v-if="processing" />
                    Publish shipment
                </Button>
                <InputError :message="errors.shipment" />
            </Form>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 lg:col-span-2 dark:border-sidebar-border"
            >
                <h2 class="mb-3 font-medium">Shipment details</h2>
                <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">Pickup</dt>
                        <dd>
                            {{ shipment.origin_address }},
                            {{ shipment.origin_city }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Delivery</dt>
                        <dd>
                            {{ shipment.destination_address }},
                            {{ shipment.destination_city }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Pickup date</dt>
                        <dd>{{ shipment.pickup_date }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Cargo</dt>
                        <dd class="capitalize">
                            {{ shipment.cargo_type }}
                            <span v-if="shipment.weight_kg">
                                ·
                                {{ shipment.weight_kg.toLocaleString() }}
                                kg</span
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Truck type</dt>
                        <dd>{{ shipment.truck_type ?? 'Any truck' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Budget</dt>
                        <dd>
                            {{
                                shipment.budget_amount
                                    ? formatMoney(
                                          shipment.budget_amount,
                                          shipment.currency,
                                      )
                                    : 'Open to offers'
                            }}
                        </dd>
                    </div>
                    <div
                        v-if="shipment.special_instructions"
                        class="sm:col-span-2"
                    >
                        <dt class="text-muted-foreground">
                            Special instructions
                        </dt>
                        <dd>{{ shipment.special_instructions }}</dd>
                    </div>
                </dl>
            </div>

            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <h2 class="mb-3 font-medium">History</h2>
                <ol class="space-y-3 text-sm">
                    <li
                        v-for="history in histories"
                        :key="history.id"
                        class="flex flex-col"
                    >
                        <span class="flex items-center gap-2">
                            <ShipmentStatusBadge :status="history.to_status" />
                            <span
                                v-if="history.actor"
                                class="text-muted-foreground"
                            >
                                by {{ history.actor }}
                            </span>
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ history.created_at }}
                        </span>
                    </li>
                </ol>
            </div>
        </div>

        <div
            class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
        >
            <h2 class="mb-3 font-medium">
                Quotes
                <span class="text-muted-foreground">({{ quotes.length }})</span>
            </h2>

            <p v-if="quotes.length === 0" class="text-sm text-muted-foreground">
                No quotes yet.
                <template v-if="shipment.status === 'draft'">
                    Publish the shipment so transporters can quote it.
                </template>
                <template v-else-if="shipment.status === 'published'">
                    Transporters have been able to see this shipment since
                    {{ shipment.published_at }}.
                </template>
            </p>

            <ul v-else class="divide-y divide-sidebar-border/40">
                <li
                    v-for="quote in quotes"
                    :key="quote.id"
                    class="flex flex-wrap items-center justify-between gap-3 py-3"
                >
                    <div class="grid gap-0.5 text-sm">
                        <span class="font-medium">
                            {{ quote.transporter_name }}
                            <span
                                v-if="quote.transporter_rating_count > 0"
                                class="text-muted-foreground"
                            >
                                ★ {{ quote.transporter_rating }} ({{
                                    quote.transporter_rating_count
                                }})
                            </span>
                        </span>
                        <span class="text-muted-foreground">
                            <template v-if="quote.truck"
                                >{{ quote.truck }} ·
                            </template>
                            <template v-if="quote.estimated_pickup_at">
                                pickup {{ quote.estimated_pickup_at }}
                            </template>
                        </span>
                        <span v-if="quote.notes" class="text-muted-foreground">
                            "{{ quote.notes }}"
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-lg font-semibold">
                            {{ formatMoney(quote.amount, quote.currency) }}
                        </span>

                        <ShipmentStatusBadge
                            v-if="quote.status !== 'pending'"
                            :status="quote.status"
                        />

                        <Form
                            v-else-if="can.acceptQuote"
                            v-bind="accept.form(quote.id)"
                            v-slot="{ errors, processing }"
                        >
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="processing"
                            >
                                <Spinner v-if="processing" />
                                Accept quote
                            </Button>
                            <InputError :message="errors.quote" />
                        </Form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
