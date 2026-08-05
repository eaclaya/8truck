<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { MapPin } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { dashboard } from '@/routes';
import regionRoutes, { destroy as destroyRegion } from '@/routes/regions';

interface RegionRow {
    id: number;
    name: string;
    department: string | null;
    radius_km: number;
}

interface CityOption {
    id: number;
    name: string;
    department: string;
}

defineProps<{
    regions: RegionRow[];
    cities: CityOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Regions', href: regionRoutes.index() },
        ],
    },
});

const { t } = useTrans();

const form = useForm({
    city_id: '',
    radius_km: 50,
});

const selectClasses =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring dark:bg-input/30';

function submit() {
    form.post(regionRoutes.store().url, {
        onSuccess: () => form.reset(),
    });
}

function removeRegion(regionId: number) {
    router.delete(destroyRegion.url(regionId));
}
</script>

<template>
    <Head :title="t('Operating regions')" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">{{ t('Operating regions') }}</h1>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div
                    v-if="regions.length === 0"
                    class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
                >
                    <MapPin class="size-8 text-muted-foreground" />
                    <p class="text-muted-foreground">
                        {{
                            t(
                                'You have no operating regions yet. Add the cities where you pick up loads.',
                            )
                        }}
                    </p>
                </div>

                <ul
                    v-else
                    class="divide-y divide-sidebar-border/40 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <li
                        v-for="region in regions"
                        :key="region.id"
                        class="flex items-center justify-between px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <MapPin class="size-4 text-muted-foreground" />
                            <div class="grid">
                                <span class="font-medium">{{
                                    region.name
                                }}</span>
                                <span class="text-sm text-muted-foreground">
                                    {{ region.department }} ·
                                    {{ region.radius_km }} km
                                </span>
                            </div>
                        </div>
                        <Button
                            size="sm"
                            variant="ghost"
                            class="text-red-600 hover:text-red-700"
                            @click="removeRegion(region.id)"
                        >
                            {{ t('Delete') }}
                        </Button>
                    </li>
                </ul>
            </div>

            <div
                class="h-fit rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <h2 class="mb-3 font-medium">{{ t('Add region') }}</h2>
                <form @submit.prevent="submit" class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="city_id">{{ t('City') }}</Label>
                        <select
                            id="city_id"
                            v-model="form.city_id"
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
                        <InputError :message="form.errors.city_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="radius_km">{{ t('Radius (km)') }}</Label>
                        <input
                            id="radius_km"
                            v-model.number="form.radius_km"
                            type="number"
                            min="10"
                            max="300"
                            required
                            :class="selectClasses"
                        />
                        <InputError :message="form.errors.radius_km" />
                    </div>

                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        {{ t('Save region') }}
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>
