<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, FileCheck, MapPin } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { dashboard } from '@/routes';
import documentRoutes from '@/routes/documents';
import regionRoutes from '@/routes/regions';

interface RegionRow {
    id: number;
    name: string;
    radius_km: number;
}

interface DocumentRow {
    id: number;
    type: string;
    status: string;
}

interface CityOption {
    id: number;
    name: string;
    department: string;
}

const props = defineProps<{
    regions: RegionRow[];
    documents: DocumentRow[];
    cities: CityOption[];
    documentTypes: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const { t } = useTrans();

const step = ref(props.regions.length === 0 ? 1 : 2);

const steps = computed(() => [
    {
        number: 1,
        title: t('Regions'),
        icon: MapPin,
        done: props.regions.length > 0,
    },
    {
        number: 2,
        title: t('Documents'),
        icon: FileCheck,
        done: props.documents.length > 0,
    },
]);

const selectClasses =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring dark:bg-input/30';

// --- Step 1: regions ---
const regionForm = useForm({ city_id: '', radius_km: 50 });

function addRegion() {
    regionForm.post(regionRoutes.store().url, {
        preserveScroll: true,
        onSuccess: () => regionForm.reset(),
    });
}

// --- Step 2: documents (optional) ---
const typeLabels: Record<string, string> = {
    driver_license: 'Driver license',
    national_id: 'National ID',
    insurance: 'Insurance',
    business_registration: 'Business registration',
};

const documentForm = useForm<{ type: string; file: File | null }>({
    type: 'driver_license',
    file: null,
});

function onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    documentForm.file = input.files?.[0] ?? null;
}

function addDocument() {
    documentForm.post(documentRoutes.store().url, {
        preserveScroll: true,
        onSuccess: () => documentForm.reset(),
    });
}

function finish() {
    router.visit(dashboard().url);
}
</script>

<template>
    <Head :title="t('Set up your transporter profile')" />

    <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4">
        <div class="grid gap-1">
            <h1 class="text-xl font-semibold">
                {{ t('Set up your transporter profile') }}
            </h1>
            <p class="text-sm text-muted-foreground">
                {{ t('Step') }} {{ step }} / 2
            </p>
        </div>

        <ol class="flex items-center gap-2">
            <li
                v-for="s in steps"
                :key="s.number"
                class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border p-2.5 text-sm transition-colors"
                :class="
                    step === s.number
                        ? 'border-primary bg-primary/5'
                        : 'border-input hover:bg-muted/50'
                "
                @click="step = s.number"
            >
                <component
                    :is="s.done ? Check : s.icon"
                    class="size-4"
                    :class="
                        s.done ? 'text-emerald-600' : 'text-muted-foreground'
                    "
                />
                {{ s.title }}
            </li>
        </ol>

        <!-- Step 1: Regions -->
        <section v-if="step === 1" class="grid gap-4">
            <div class="grid gap-1">
                <h2 class="font-medium">{{ t('Where do you operate?') }}</h2>
                <p class="text-sm text-muted-foreground">
                    {{ t('Add at least one region to see loads near you.') }}
                </p>
            </div>

            <ul v-if="regions.length > 0" class="grid gap-2">
                <li
                    v-for="region in regions"
                    :key="region.id"
                    class="flex items-center gap-2 rounded-lg border border-input px-3 py-2 text-sm"
                >
                    <MapPin class="size-4 text-muted-foreground" />
                    {{ region.name }} · {{ region.radius_km }} km
                </li>
            </ul>

            <form
                @submit.prevent="addRegion"
                class="grid gap-3 sm:grid-cols-[1fr_120px_auto]"
            >
                <div class="grid gap-1">
                    <Label for="city_id" class="sr-only">{{ t('City') }}</Label>
                    <select
                        id="city_id"
                        v-model="regionForm.city_id"
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
                    <InputError :message="regionForm.errors.city_id" />
                </div>
                <div class="grid gap-1">
                    <Label for="radius_km" class="sr-only">{{
                        t('Radius (km)')
                    }}</Label>
                    <input
                        id="radius_km"
                        v-model.number="regionForm.radius_km"
                        type="number"
                        min="10"
                        max="300"
                        required
                        :class="selectClasses"
                    />
                    <InputError :message="regionForm.errors.radius_km" />
                </div>
                <Button
                    type="submit"
                    variant="outline"
                    :disabled="regionForm.processing"
                >
                    <Spinner v-if="regionForm.processing" />
                    {{ t('Add region') }}
                </Button>
            </form>

            <div class="flex justify-end">
                <Button :disabled="regions.length === 0" @click="step = 2">
                    {{ t('Continue') }}
                </Button>
            </div>
        </section>

        <!-- Step 2: Documents (optional) -->
        <section v-if="step === 2" class="grid gap-4">
            <div class="grid gap-1">
                <h2 class="font-medium">{{ t('Verification documents') }}</h2>
                <p class="text-sm text-muted-foreground">
                    {{
                        t(
                            'Optional for now - upload them to get verified faster.',
                        )
                    }}
                </p>
            </div>

            <ul v-if="documents.length > 0" class="grid gap-2">
                <li
                    v-for="document in documents"
                    :key="document.id"
                    class="flex items-center gap-2 rounded-lg border border-input px-3 py-2 text-sm"
                >
                    <FileCheck class="size-4 text-muted-foreground" />
                    {{ t(typeLabels[document.type] ?? document.type) }}
                </li>
            </ul>

            <form
                @submit.prevent="addDocument"
                class="grid gap-3 sm:grid-cols-[180px_1fr_auto]"
            >
                <div class="grid gap-1">
                    <Label for="doc_type" class="sr-only">{{
                        t('Document type')
                    }}</Label>
                    <select
                        id="doc_type"
                        v-model="documentForm.type"
                        required
                        :class="selectClasses"
                    >
                        <option
                            v-for="type in documentTypes"
                            :key="type"
                            :value="type"
                        >
                            {{ t(typeLabels[type] ?? type) }}
                        </option>
                    </select>
                    <InputError :message="documentForm.errors.type" />
                </div>
                <div class="grid gap-1">
                    <Label for="doc_file" class="sr-only">{{
                        t('File')
                    }}</Label>
                    <input
                        id="doc_file"
                        type="file"
                        accept=".jpg,.jpeg,.png,.pdf"
                        class="text-sm text-muted-foreground file:mr-2 file:rounded-md file:border file:border-input file:bg-transparent file:px-2 file:py-1 file:text-sm"
                        @change="onFileSelected"
                    />
                    <InputError :message="documentForm.errors.file" />
                </div>
                <Button
                    type="submit"
                    variant="outline"
                    :disabled="
                        documentForm.processing || documentForm.file === null
                    "
                >
                    <Spinner v-if="documentForm.processing" />
                    {{ t('Upload') }}
                </Button>
            </form>

            <div class="flex justify-between">
                <Button variant="ghost" @click="step = 1">{{
                    t('Back')
                }}</Button>
                <Button @click="finish">
                    {{ documents.length > 0 ? t('Finish') : t('Skip for now') }}
                </Button>
            </div>
        </section>
    </div>
</template>
