<script setup lang="ts">
import { useTrans } from '@/composables/useTrans';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();

const { t } = useTrans();
</script>

<template>
    <AuthLayout :title="t(title)" :description="t(description)">
        <slot />
    </AuthLayout>
</template>
