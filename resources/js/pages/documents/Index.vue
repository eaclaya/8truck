<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { FileCheck } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import ShipmentStatusBadge from '@/components/ShipmentStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { dashboard } from '@/routes';
import documentRoutes, { download } from '@/routes/documents';

interface DocumentRow {
    id: number;
    type: string;
    status: string;
    expires_at: string | null;
    notes: string | null;
    created_at: string | null;
}

defineProps<{
    documents: DocumentRow[];
    documentTypes: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Documents', href: documentRoutes.index() },
        ],
    },
});

const { t } = useTrans();

const typeLabels: Record<string, string> = {
    driver_license: 'Driver license',
    national_id: 'National ID',
    insurance: 'Insurance',
    business_registration: 'Business registration',
};

const form = useForm<{
    type: string;
    file: File | null;
    expires_at: string;
}>({
    type: 'driver_license',
    file: null,
    expires_at: '',
});

const selectClasses =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring dark:bg-input/30';

function onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    form.file = input.files?.[0] ?? null;
}

function submit() {
    form.transform((data) => ({
        ...data,
        expires_at: data.expires_at === '' ? null : data.expires_at,
    })).post(documentRoutes.store().url, {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head :title="t('My documents')" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">{{ t('My documents') }}</h1>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div
                    v-if="documents.length === 0"
                    class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
                >
                    <FileCheck class="size-8 text-muted-foreground" />
                    <p class="text-muted-foreground">
                        {{
                            t(
                                'You have no documents yet. Upload your license and IDs to get verified.',
                            )
                        }}
                    </p>
                </div>

                <ul
                    v-else
                    class="divide-y divide-sidebar-border/40 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <li
                        v-for="document in documents"
                        :key="document.id"
                        class="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
                    >
                        <div class="grid gap-0.5">
                            <a
                                :href="download.url(document.id)"
                                target="_blank"
                                class="font-medium hover:underline"
                            >
                                {{
                                    t(
                                        typeLabels[document.type] ??
                                            document.type,
                                    )
                                }}
                            </a>
                            <span class="text-sm text-muted-foreground">
                                {{ t('Uploaded') }}: {{ document.created_at }}
                                <template v-if="document.expires_at">
                                    · {{ t('Insurance until') }}
                                    {{ document.expires_at }}
                                </template>
                            </span>
                            <span
                                v-if="
                                    document.notes &&
                                    document.status === 'rejected'
                                "
                                class="text-sm text-red-600 dark:text-red-400"
                            >
                                "{{ document.notes }}"
                            </span>
                        </div>
                        <ShipmentStatusBadge :status="document.status" />
                    </li>
                </ul>
            </div>

            <div
                class="h-fit rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <h2 class="mb-3 font-medium">{{ t('Upload a document') }}</h2>
                <form @submit.prevent="submit" class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="type">{{ t('Document type') }}</Label>
                        <select
                            id="type"
                            v-model="form.type"
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
                        <InputError :message="form.errors.type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="file">{{ t('File') }}</Label>
                        <input
                            id="file"
                            type="file"
                            accept=".jpg,.jpeg,.png,.pdf"
                            required
                            class="text-sm text-muted-foreground file:mr-2 file:rounded-md file:border file:border-input file:bg-transparent file:px-2 file:py-1 file:text-sm"
                            @change="onFileSelected"
                        />
                        <InputError :message="form.errors.file" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="expires_at">{{
                            t('Expiry date (optional)')
                        }}</Label>
                        <Input
                            id="expires_at"
                            v-model="form.expires_at"
                            type="date"
                        />
                        <InputError :message="form.errors.expires_at" />
                    </div>

                    <Button
                        type="submit"
                        :disabled="form.processing || form.file === null"
                    >
                        <Spinner v-if="form.processing" />
                        {{ t('Save document') }}
                    </Button>
                </form>
            </div>
        </div>
    </div>
</template>
