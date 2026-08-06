<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Camera } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { pod } from '@/routes/jobs';

const props = defineProps<{
    shipmentId: number;
    podCount: number;
}>();

const { t } = useTrans();

const form = useForm<{ photos: File[] }>({
    photos: [],
});

function onFilesSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    form.photos = Array.from(input.files ?? []);
}

function submit() {
    form.post(pod.url(props.shipmentId), {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <form @submit.prevent="submit" class="flex flex-wrap items-center gap-2">
        <Camera class="size-4 text-muted-foreground" />
        <span v-if="podCount > 0" class="text-xs text-muted-foreground">
            {{ podCount }} {{ t('photos uploaded') }}
        </span>
        <input
            type="file"
            multiple
            accept="image/*"
            class="max-w-52 text-xs text-muted-foreground file:mr-2 file:rounded-md file:border file:border-input file:bg-transparent file:px-2 file:py-1 file:text-xs"
            @change="onFilesSelected"
        />
        <Button
            type="submit"
            size="sm"
            variant="outline"
            :disabled="form.processing || form.photos.length === 0"
        >
            <Spinner v-if="form.processing" />
            {{ t('Upload proof of delivery') }}
        </Button>
        <InputError :message="form.errors.photos" />
    </form>
</template>
