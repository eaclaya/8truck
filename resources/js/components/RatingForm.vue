<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Star } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { rate } from '@/routes/shipments';

const props = defineProps<{
    shipmentId: number;
    title: string;
}>();

const { t } = useTrans();

const form = useForm({
    score: 0,
    comment: '',
});

function submit() {
    form.transform((data) => ({
        ...data,
        comment: data.comment === '' ? null : data.comment,
    })).post(rate.url(props.shipmentId));
}
</script>

<template>
    <form @submit.prevent="submit" class="grid gap-3">
        <h3 class="text-sm font-medium">{{ t(title) }}</h3>

        <div class="flex gap-1">
            <button
                v-for="star in 5"
                :key="star"
                type="button"
                class="p-0.5"
                :aria-label="`${star}/5`"
                @click="form.score = star"
            >
                <Star
                    class="size-6 transition-colors"
                    :class="
                        star <= form.score
                            ? 'fill-amber-400 text-amber-400'
                            : 'text-muted-foreground'
                    "
                />
            </button>
        </div>
        <InputError :message="form.errors.score" />

        <textarea
            v-model="form.comment"
            rows="2"
            class="w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm dark:bg-input/30"
            :placeholder="t('Comment (optional)')"
        />
        <InputError :message="form.errors.comment" />
        <InputError
            :message="(form.errors as Partial<Record<string, string>>).rating"
        />

        <div>
            <Button
                type="submit"
                size="sm"
                :disabled="form.processing || form.score === 0"
            >
                <Spinner v-if="form.processing" />
                {{ t('Submit rating') }}
            </Button>
        </div>
    </form>
</template>
