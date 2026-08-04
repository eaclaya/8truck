<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Package, Truck } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

const role = ref<'customer' | 'transporter'>('customer');

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label>I want to</Label>
                <div class="grid grid-cols-2 gap-3">
                    <label
                        class="flex cursor-pointer flex-col items-center gap-1.5 rounded-lg border p-3 text-sm transition-colors"
                        :class="
                            role === 'customer'
                                ? 'border-primary bg-primary/5'
                                : 'border-input hover:bg-muted/50'
                        "
                    >
                        <input
                            type="radio"
                            name="role"
                            value="customer"
                            v-model="role"
                            class="sr-only"
                        />
                        <Package class="size-5" />
                        Ship cargo
                    </label>
                    <label
                        class="flex cursor-pointer flex-col items-center gap-1.5 rounded-lg border p-3 text-sm transition-colors"
                        :class="
                            role === 'transporter'
                                ? 'border-primary bg-primary/5'
                                : 'border-input hover:bg-muted/50'
                        "
                    >
                        <input
                            type="radio"
                            name="role"
                            value="transporter"
                            v-model="role"
                            class="sr-only"
                        />
                        <Truck class="size-5" />
                        Transport cargo
                    </label>
                </div>
                <InputError :message="errors.role" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div v-if="role === 'transporter'" class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input
                    id="phone"
                    type="tel"
                    required
                    autocomplete="tel"
                    name="phone"
                    placeholder="+504 9999-9999"
                />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="6"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>
