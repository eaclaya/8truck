<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import AddTruckModal from '@/components/AddTruckModal.vue';
import InputError from '@/components/InputError.vue';
import ShipmentStatusBadge from '@/components/ShipmentStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { dashboard } from '@/routes';
import loadRoutes, { quote as quoteRoute } from '@/routes/loads';

interface LoadDetail {
    id: number;
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
    customer_name: string;
    quotes_count: number;
}

interface MyQuote {
    amount: string;
    currency: string;
    status: string;
    estimated_pickup_at: string | null;
    estimated_delivery_at: string | null;
    notes: string | null;
}

interface TruckOption {
    id: number;
    label: string;
}

interface TruckTypeOption {
    id: number;
    name: string;
}

const props = defineProps<{
    load: LoadDetail;
    myQuote: MyQuote | null;
    trucks: TruckOption[];
    truckTypes: TruckTypeOption[];
    canQuote: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Loads', href: loadRoutes.index() },
        ],
    },
});

const { t } = useTrans();

const form = useForm<{
    amount: string;
    truck_id: number | '';
    estimated_pickup_at: string;
    estimated_delivery_at: string;
    notes: string;
}>({
    amount: '',
    truck_id: '',
    estimated_pickup_at: '',
    estimated_delivery_at: '',
    notes: '',
});

const selectClasses =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30';

const showTruckModal = ref(false);

function sendQuote() {
    form.transform((data) => ({
        ...data,
        estimated_pickup_at:
            data.estimated_pickup_at === '' ? null : data.estimated_pickup_at,
        estimated_delivery_at:
            data.estimated_delivery_at === ''
                ? null
                : data.estimated_delivery_at,
    })).post(quoteRoute.url(props.load.id));
}

/**
 * A quote always travels on a truck, so a transporter without one is asked for
 * it here rather than at signup. The quote continues once the truck exists.
 */
function submit() {
    if (props.trucks.length === 0) {
        showTruckModal.value = true;

        return;
    }

    sendQuote();
}

async function onTruckAdded() {
    await nextTick();

    const newest = props.trucks.at(-1);

    if (newest === undefined) {
        return;
    }

    form.truck_id = newest.id;
    sendQuote();
}

const formatMoney = (amount: string, currency: string) =>
    `${currency} ${Number(amount).toLocaleString('es-HN', { minimumFractionDigits: 2 })}`;
</script>

<template>
    <Head
        :title="`${t('Load details')}: ${load.origin_city} → ${load.destination_city}`"
    />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center gap-3">
            <h1 class="text-xl font-semibold">
                {{ load.origin_city }} → {{ load.destination_city }}
            </h1>
            <ShipmentStatusBadge :status="load.status" />
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 lg:col-span-2 dark:border-sidebar-border"
            >
                <h2 class="mb-3 font-medium">{{ t('Load details') }}</h2>
                <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">{{ t('Pickup') }}</dt>
                        <dd>
                            {{ load.origin_address }}, {{ load.origin_city }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">
                            {{ t('Delivery') }}
                        </dt>
                        <dd>
                            {{ load.destination_address }},
                            {{ load.destination_city }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">
                            {{ t('Pickup date') }}
                        </dt>
                        <dd>{{ load.pickup_date }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ t('Cargo') }}</dt>
                        <dd>
                            {{ t('cargo.' + load.cargo_type) }}
                            <span v-if="load.weight_kg">
                                · {{ load.weight_kg.toLocaleString() }} kg</span
                            >
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">
                            {{ t('Truck type') }}
                        </dt>
                        <dd>{{ load.truck_type ?? t('Any truck') }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ t('Budget') }}</dt>
                        <dd>
                            {{
                                load.budget_amount
                                    ? formatMoney(
                                          load.budget_amount,
                                          load.currency,
                                      )
                                    : t('Open to offers')
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">
                            {{ t('Customer') }}
                        </dt>
                        <dd>{{ load.customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ t('Quotes') }}</dt>
                        <dd>
                            {{ load.quotes_count }} {{ t('quotes so far') }}
                        </dd>
                    </div>
                    <div v-if="load.special_instructions" class="sm:col-span-2">
                        <dt class="text-muted-foreground">
                            {{ t('Special instructions') }}
                        </dt>
                        <dd>{{ load.special_instructions }}</dd>
                    </div>
                </dl>
            </div>

            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <template v-if="myQuote">
                    <h2 class="mb-3 font-medium">{{ t('Your quote') }}</h2>
                    <div class="grid gap-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-semibold">
                                {{
                                    formatMoney(
                                        myQuote.amount,
                                        myQuote.currency,
                                    )
                                }}
                            </span>
                            <ShipmentStatusBadge :status="myQuote.status" />
                        </div>
                        <p
                            v-if="myQuote.estimated_pickup_at"
                            class="text-muted-foreground"
                        >
                            {{ t('Estimated pickup') }}:
                            {{ myQuote.estimated_pickup_at }}
                        </p>
                        <p
                            v-if="myQuote.estimated_delivery_at"
                            class="text-muted-foreground"
                        >
                            {{ t('Estimated delivery') }}:
                            {{ myQuote.estimated_delivery_at }}
                        </p>
                        <p v-if="myQuote.notes" class="text-muted-foreground">
                            "{{ myQuote.notes }}"
                        </p>
                    </div>
                </template>

                <template v-else-if="canQuote">
                    <h2 class="mb-3 font-medium">{{ t('Submit a quote') }}</h2>
                    <form @submit.prevent="submit" class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="amount">{{ t('Amount in HNL') }}</Label>
                            <Input
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                min="1"
                                step="0.01"
                                required
                                placeholder="15000"
                            />
                            <InputError :message="form.errors.amount" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="truck_id">{{ t('Truck') }}</Label>
                            <select
                                id="truck_id"
                                v-model="form.truck_id"
                                :required="trucks.length > 0"
                                :class="selectClasses"
                            >
                                <option value="" disabled>
                                    {{
                                        trucks.length > 0
                                            ? t('Select a truck')
                                            : t('No trucks registered yet')
                                    }}
                                </option>
                                <option
                                    v-for="truck in trucks"
                                    :key="truck.id"
                                    :value="truck.id"
                                >
                                    {{ truck.label }}
                                </option>
                            </select>
                            <p
                                v-if="trucks.length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                {{
                                    t(
                                        'We will ask for your truck details when you send the quote.',
                                    )
                                }}
                            </p>
                            <InputError :message="form.errors.truck_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="estimated_pickup_at">{{
                                t('Estimated pickup')
                            }}</Label>
                            <Input
                                id="estimated_pickup_at"
                                v-model="form.estimated_pickup_at"
                                type="datetime-local"
                            />
                            <InputError
                                :message="form.errors.estimated_pickup_at"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="estimated_delivery_at">{{
                                t('Estimated delivery')
                            }}</Label>
                            <Input
                                id="estimated_delivery_at"
                                v-model="form.estimated_delivery_at"
                                type="datetime-local"
                            />
                            <InputError
                                :message="form.errors.estimated_delivery_at"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="notes">{{
                                t('Notes (optional)')
                            }}</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="2"
                                :class="selectClasses"
                                class="h-auto"
                            />
                            <InputError :message="form.errors.notes" />
                        </div>

                        <InputError
                            :message="
                                (form.errors as Partial<Record<string, string>>)
                                    .quote
                            "
                        />

                        <Button type="submit" :disabled="form.processing">
                            <Spinner v-if="form.processing" />
                            {{ t('Send quote') }}
                        </Button>
                    </form>

                    <AddTruckModal
                        v-model:open="showTruckModal"
                        :truck-types="truckTypes"
                        @added="onTruckAdded"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
