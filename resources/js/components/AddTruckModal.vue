<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import truckRoutes from '@/routes/trucks';

interface TruckTypeOption {
    id: number;
    name: string;
}

defineProps<{
    truckTypes: TruckTypeOption[];
}>();

const open = defineModel<boolean>('open', { required: true });

const emit = defineEmits<{ added: [] }>();

const { t } = useTrans();

const form = useForm({
    truck_type_id: '',
    plate_number: '',
    capacity_kg: '',
    stay: 1,
});

const selectClasses =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring dark:bg-input/30';

function submit() {
    form.post(truckRoutes.store().url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            form.reset();
            open.value = false;
            emit('added');
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <form @submit.prevent="submit" class="grid gap-4">
                <DialogHeader class="space-y-2">
                    <DialogTitle>{{ t('Add a truck to quote') }}</DialogTitle>
                    <DialogDescription>
                        {{
                            t(
                                'Every quote travels on a truck - register yours to send this one.',
                            )
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="modal_truck_type_id">{{
                        t('Truck type')
                    }}</Label>
                    <select
                        id="modal_truck_type_id"
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
                    <Label for="modal_plate_number">{{
                        t('Plate number')
                    }}</Label>
                    <Input
                        id="modal_plate_number"
                        v-model="form.plate_number"
                        required
                        placeholder="AAB 1234"
                    />
                    <InputError :message="form.errors.plate_number" />
                </div>

                <div class="grid gap-2">
                    <Label for="modal_capacity_kg">{{
                        t('Capacity (kg)')
                    }}</Label>
                    <Input
                        id="modal_capacity_kg"
                        v-model="form.capacity_kg"
                        type="number"
                        min="100"
                        required
                        placeholder="10000"
                    />
                    <InputError :message="form.errors.capacity_kg" />
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="open = false"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        {{ t('Add truck') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
