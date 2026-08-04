<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { dashboard } from '@/routes';
import shipments from '@/routes/shipments';

interface CityOption {
    id: number;
    name: string;
    department: string;
}

interface TruckTypeOption {
    id: number;
    name: string;
}

defineProps<{
    cities: CityOption[];
    truckTypes: TruckTypeOption[];
    cargoTypes: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Shipments', href: shipments.index() },
            { title: 'New shipment', href: shipments.create() },
        ],
    },
});

const form = useForm({
    origin_city_id: '',
    origin_address: '',
    destination_city_id: '',
    destination_address: '',
    pickup_date: '',
    cargo_type: 'general',
    weight_kg: '',
    truck_type_id: '',
    budget_amount: '',
    special_instructions: '',
});

const selectClasses =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30';

function submit() {
    form.transform((data) => ({
        ...data,
        weight_kg: data.weight_kg === '' ? null : data.weight_kg,
        truck_type_id: data.truck_type_id === '' ? null : data.truck_type_id,
        budget_amount: data.budget_amount === '' ? null : data.budget_amount,
    })).post(shipments.store().url);
}

const { t } = useTrans();
</script>

<template>
    <Head :title="t('New shipment')" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">{{ t('Create a shipment') }}</h1>

        <form @submit.prevent="submit" class="grid max-w-2xl gap-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="origin_city_id">{{ t('Origin city') }}</Label>
                    <select
                        id="origin_city_id"
                        v-model="form.origin_city_id"
                        required
                        :class="selectClasses"
                    >
                        <option value="" disabled>
                            {{ t('Select a city') }}
                        </option>
                        <option
                            v-for="city in cities"
                            :key="city.id"
                            :value="city.id"
                        >
                            {{ city.name }} ({{ city.department }})
                        </option>
                    </select>
                    <InputError :message="form.errors.origin_city_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="destination_city_id">{{
                        t('Destination city')
                    }}</Label>
                    <select
                        id="destination_city_id"
                        v-model="form.destination_city_id"
                        required
                        :class="selectClasses"
                    >
                        <option value="" disabled>
                            {{ t('Select a city') }}
                        </option>
                        <option
                            v-for="city in cities"
                            :key="city.id"
                            :value="city.id"
                        >
                            {{ city.name }} ({{ city.department }})
                        </option>
                    </select>
                    <InputError :message="form.errors.destination_city_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="origin_address">{{
                        t('Pickup address')
                    }}</Label>
                    <Input
                        id="origin_address"
                        v-model="form.origin_address"
                        required
                        :placeholder="t('Street, neighborhood, references')"
                    />
                    <InputError :message="form.errors.origin_address" />
                </div>

                <div class="grid gap-2">
                    <Label for="destination_address">{{
                        t('Delivery address')
                    }}</Label>
                    <Input
                        id="destination_address"
                        v-model="form.destination_address"
                        required
                        :placeholder="t('Street, neighborhood, references')"
                    />
                    <InputError :message="form.errors.destination_address" />
                </div>

                <div class="grid gap-2">
                    <Label for="pickup_date">{{ t('Pickup date') }}</Label>
                    <Input
                        id="pickup_date"
                        v-model="form.pickup_date"
                        type="date"
                        required
                    />
                    <InputError :message="form.errors.pickup_date" />
                </div>

                <div class="grid gap-2">
                    <Label for="cargo_type">{{ t('Cargo type') }}</Label>
                    <select
                        id="cargo_type"
                        v-model="form.cargo_type"
                        required
                        :class="selectClasses"
                    >
                        <option
                            v-for="type in cargoTypes"
                            :key="type"
                            :value="type"
                            class="capitalize"
                        >
                            {{ t('cargo.' + type) }}
                        </option>
                    </select>
                    <InputError :message="form.errors.cargo_type" />
                </div>

                <div class="grid gap-2">
                    <Label for="weight_kg">{{
                        t('Weight (kg, optional)')
                    }}</Label>
                    <Input
                        id="weight_kg"
                        v-model="form.weight_kg"
                        type="number"
                        min="1"
                        placeholder="5000"
                    />
                    <InputError :message="form.errors.weight_kg" />
                </div>

                <div class="grid gap-2">
                    <Label for="truck_type_id">{{
                        t('Truck type (optional)')
                    }}</Label>
                    <select
                        id="truck_type_id"
                        v-model="form.truck_type_id"
                        :class="selectClasses"
                    >
                        <option value="">{{ t('Any truck') }}</option>
                        <option
                            v-for="type in truckTypes"
                            :key="type.id"
                            :value="type.id"
                        >
                            {{ type.name }}
                        </option>
                    </select>
                    <InputError :message="form.errors.truck_type_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="budget_amount">{{
                        t('Budget in HNL (optional)')
                    }}</Label>
                    <Input
                        id="budget_amount"
                        v-model="form.budget_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="15000"
                    />
                    <InputError :message="form.errors.budget_amount" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="special_instructions">{{
                    t('Special instructions (optional)')
                }}</Label>
                <textarea
                    id="special_instructions"
                    v-model="form.special_instructions"
                    rows="3"
                    :class="selectClasses"
                    class="h-auto"
                    :placeholder="
                        t('Fragile cargo, needs a forklift at delivery...')
                    "
                />
                <InputError :message="form.errors.special_instructions" />
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />{{
                        t('Save draft')
                    }}</Button
                >
            </div>
        </form>
    </div>
</template>
