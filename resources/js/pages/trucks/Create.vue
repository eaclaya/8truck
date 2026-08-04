<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { dashboard } from '@/routes';
import truckRoutes from '@/routes/trucks';

interface TruckTypeOption {
    id: number;
    name: string;
}

defineProps<{
    truckTypes: TruckTypeOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Trucks', href: truckRoutes.index() },
            { title: 'Add truck', href: truckRoutes.create() },
        ],
    },
});

const { t } = useTrans();

const form = useForm({
    truck_type_id: '',
    plate_number: '',
    capacity_kg: '',
    length_cm: '',
    width_cm: '',
    height_cm: '',
    insurance_expires_at: '',
});

const selectClasses =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30';

function submit() {
    form.transform((data) => ({
        ...data,
        length_cm: data.length_cm === '' ? null : data.length_cm,
        width_cm: data.width_cm === '' ? null : data.width_cm,
        height_cm: data.height_cm === '' ? null : data.height_cm,
        insurance_expires_at:
            data.insurance_expires_at === '' ? null : data.insurance_expires_at,
    })).post(truckRoutes.store().url);
}
</script>

<template>
    <Head :title="t('Register a truck')" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">{{ t('Register a truck') }}</h1>

        <form @submit.prevent="submit" class="grid max-w-2xl gap-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="truck_type_id">{{ t('Truck type') }}</Label>
                    <select
                        id="truck_type_id"
                        v-model="form.truck_type_id"
                        required
                        :class="selectClasses"
                    >
                        <option value="" disabled>
                            {{ t('Select a type') }}
                        </option>
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
                    <Label for="plate_number">{{ t('Plate number') }}</Label>
                    <Input
                        id="plate_number"
                        v-model="form.plate_number"
                        required
                        placeholder="AAB 1234"
                    />
                    <InputError :message="form.errors.plate_number" />
                </div>

                <div class="grid gap-2">
                    <Label for="capacity_kg">{{ t('Capacity (kg)') }}</Label>
                    <Input
                        id="capacity_kg"
                        v-model="form.capacity_kg"
                        type="number"
                        min="100"
                        required
                        placeholder="10000"
                    />
                    <InputError :message="form.errors.capacity_kg" />
                </div>

                <div class="grid gap-2">
                    <Label for="insurance_expires_at">{{
                        t('Insurance expiry (optional)')
                    }}</Label>
                    <Input
                        id="insurance_expires_at"
                        v-model="form.insurance_expires_at"
                        type="date"
                    />
                    <InputError :message="form.errors.insurance_expires_at" />
                </div>

                <div class="grid gap-2">
                    <Label for="length_cm">{{
                        t('Length (cm, optional)')
                    }}</Label>
                    <Input
                        id="length_cm"
                        v-model="form.length_cm"
                        type="number"
                        min="1"
                    />
                    <InputError :message="form.errors.length_cm" />
                </div>

                <div class="grid gap-2">
                    <Label for="width_cm">{{
                        t('Width (cm, optional)')
                    }}</Label>
                    <Input
                        id="width_cm"
                        v-model="form.width_cm"
                        type="number"
                        min="1"
                    />
                    <InputError :message="form.errors.width_cm" />
                </div>

                <div class="grid gap-2">
                    <Label for="height_cm">{{
                        t('Height (cm, optional)')
                    }}</Label>
                    <Input
                        id="height_cm"
                        v-model="form.height_cm"
                        type="number"
                        min="1"
                    />
                    <InputError :message="form.errors.height_cm" />
                </div>
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    {{ t('Save truck') }}
                </Button>
            </div>
        </form>
    </div>
</template>
