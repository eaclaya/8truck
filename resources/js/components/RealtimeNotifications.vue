<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { useEchoNotification } from '@laravel/echo-vue';
import { toast } from 'vue-sonner';
import { useTrans } from '@/composables/useTrans';
import { read } from '@/routes/notifications';

interface NotificationPayload {
    id: string;
    title?: string;
    body?: string;
    url?: string | null;
}

const page = usePage();
const { t } = useTrans();

const userId = page.props.auth?.user?.id;

if (userId) {
    useEchoNotification<NotificationPayload>(
        `App.Models.User.${userId}`,
        (notification) => {
            toast(notification.title ?? t('Notifications'), {
                description: notification.body,
                action: notification.url
                    ? {
                          label: t('View'),
                          onClick: () => router.post(read.url(notification.id)),
                      }
                    : undefined,
            });

            router.reload();
        },
    );
}
</script>

<template>
    <span class="hidden" />
</template>
