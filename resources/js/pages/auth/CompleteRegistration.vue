<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { Package, Truck } from '@lucide/vue';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { store } from '@/routes/google';

const props = defineProps<{
    name: string;
    email: string;
}>();

defineOptions({
    layout: {
        title: 'Complete your account',
        description: 'Tell us how you will use the platform',
    },
});

const { t } = useTrans();

const form = useForm({
    role: 'customer',
    phone: '',
});

watch(
    () => form.role,
    () => form.clearErrors('phone'),
);

function submit() {
    form.transform((data) => ({
        ...data,
        phone: data.phone === '' ? null : data.phone,
    })).post(store().url);
}
</script>

<template>
    <Head :title="t('Complete your account')" />

    <form @submit.prevent="submit" class="flex flex-col gap-6">
        <div class="grid gap-6">
            <p class="text-center text-sm text-muted-foreground">
                {{ props.name }} · {{ props.email }}
            </p>

            <div class="grid gap-2">
                <Label>{{ t('I want to') }}</Label>
                <div class="grid grid-cols-2 gap-3">
                    <label
                        class="flex cursor-pointer flex-col items-center gap-1.5 rounded-lg border p-3 text-sm transition-colors"
                        :class="
                            form.role === 'customer'
                                ? 'border-primary bg-primary/5'
                                : 'border-input hover:bg-muted/50'
                        "
                    >
                        <input
                            type="radio"
                            value="customer"
                            v-model="form.role"
                            class="sr-only"
                        />
                        <Package class="size-5" />
                        {{ t('Ship cargo') }}
                    </label>
                    <label
                        class="flex cursor-pointer flex-col items-center gap-1.5 rounded-lg border p-3 text-sm transition-colors"
                        :class="
                            form.role === 'transporter'
                                ? 'border-primary bg-primary/5'
                                : 'border-input hover:bg-muted/50'
                        "
                    >
                        <input
                            type="radio"
                            value="transporter"
                            v-model="form.role"
                            class="sr-only"
                        />
                        <Truck class="size-5" />
                        {{ t('Transport cargo') }}
                    </label>
                </div>
                <InputError :message="form.errors.role" />
            </div>

            <div v-if="form.role === 'transporter'" class="grid gap-2">
                <Label for="phone">{{ t('Phone') }}</Label>
                <Input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    required
                    placeholder="+504 9999-9999"
                />
                <InputError :message="form.errors.phone" />
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                {{ t('Create account') }}
            </Button>
        </div>
    </form>
</template>
