<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { dashboard } from '@/routes';
import truckRoutes, {
    destroy as destroyTruck,
    update as updateTruck,
} from '@/routes/trucks';

interface TruckRow {
    id: number;
    plate_number: string;
    truck_type: string | null;
    capacity_kg: number;
    availability: string;
    insurance_expires_at: string | null;
}

defineProps<{
    trucks: TruckRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Trucks', href: truckRoutes.index() },
        ],
    },
});

const { t } = useTrans();

const selectClasses =
    'h-8 rounded-md border border-input bg-transparent px-2 text-sm dark:bg-input/30';

function setAvailability(truckId: number, event: Event) {
    router.patch(updateTruck.url(truckId), {
        availability: (event.target as HTMLSelectElement).value,
    });
}

function removeTruck(truckId: number) {
    if (confirm(t('Delete') + '?')) {
        router.delete(destroyTruck.url(truckId));
    }
}
</script>

<template>
    <Head :title="t('My trucks')" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ t('My trucks') }}</h1>
            <Button as-child>
                <Link :href="truckRoutes.create()">
                    <Plus class="size-4" />
                    {{ t('Add truck') }}
                </Link>
            </Button>
        </div>

        <div
            v-if="trucks.length === 0"
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
        >
            <p class="text-muted-foreground">
                {{ t('You have no trucks yet.') }}
            </p>
            <Button as-child>
                <Link :href="truckRoutes.create()">{{ t('Add truck') }}</Link>
            </Button>
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
                        <th class="px-4 py-3 font-medium">{{ t('Plate') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Type') }}</th>
                        <th class="px-4 py-3 font-medium">
                            {{ t('Capacity') }}
                        </th>
                        <th class="px-4 py-3 font-medium">
                            {{ t('Insurance until') }}
                        </th>
                        <th class="px-4 py-3 font-medium">
                            {{ t('Availability') }}
                        </th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="truck in trucks"
                        :key="truck.id"
                        class="border-b border-sidebar-border/40 last:border-0 dark:border-sidebar-border/40"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ truck.plate_number }}
                        </td>
                        <td class="px-4 py-3">{{ truck.truck_type }}</td>
                        <td class="px-4 py-3">
                            {{ truck.capacity_kg.toLocaleString() }} kg
                        </td>
                        <td class="px-4 py-3">
                            {{ truck.insurance_expires_at ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <select
                                :value="truck.availability"
                                :class="selectClasses"
                                @change="setAvailability(truck.id, $event)"
                            >
                                <option value="available">
                                    {{ t('Available') }}
                                </option>
                                <option value="busy">{{ t('Busy') }}</option>
                                <option value="inactive">
                                    {{ t('Inactive') }}
                                </option>
                            </select>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                size="sm"
                                variant="ghost"
                                class="text-red-600 hover:text-red-700"
                                @click="removeTruck(truck.id)"
                            >
                                {{ t('Delete') }}
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
