<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTrans } from '@/composables/useTrans';
import { read, readAll } from '@/routes/notifications';

interface NotificationItem {
    id: string;
    title: string;
    body: string;
    read: boolean;
    created_at: string | null;
}

interface NotificationsProp {
    unread: number;
    items: NotificationItem[];
}

const page = usePage<{ notifications: NotificationsProp | null }>();
const { t } = useTrans();

const notifications = computed(() => page.props.notifications);

function open(notification: NotificationItem) {
    router.post(read.url(notification.id));
}

function markAllRead() {
    router.post(readAll.url(), {}, { preserveScroll: true });
}
</script>

<template>
    <DropdownMenu v-if="notifications">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative">
                <Bell class="size-5" />
                <span
                    v-if="notifications.unread > 0"
                    class="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-semibold text-white"
                >
                    {{ notifications.unread > 9 ? '9+' : notifications.unread }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80 p-0">
            <div
                class="flex items-center justify-between border-b border-border px-3 py-2"
            >
                <span class="text-sm font-medium">{{
                    t('Notifications')
                }}</span>
                <button
                    v-if="notifications.unread > 0"
                    class="text-xs text-muted-foreground hover:text-foreground"
                    @click="markAllRead"
                >
                    {{ t('Mark all as read') }}
                </button>
            </div>

            <p
                v-if="notifications.items.length === 0"
                class="px-3 py-6 text-center text-sm text-muted-foreground"
            >
                {{ t('No notifications yet.') }}
            </p>

            <ul v-else class="max-h-96 overflow-y-auto">
                <li
                    v-for="item in notifications.items"
                    :key="item.id"
                    class="cursor-pointer border-b border-border/50 px-3 py-2.5 last:border-0 hover:bg-muted/50"
                    :class="{ 'opacity-60': item.read }"
                    @click="open(item)"
                >
                    <div class="flex items-start gap-2">
                        <span
                            v-if="!item.read"
                            class="mt-1.5 size-2 shrink-0 rounded-full bg-blue-500"
                        />
                        <div
                            class="grid gap-0.5"
                            :class="{ 'pl-4': item.read }"
                        >
                            <span class="text-sm font-medium">{{
                                item.title
                            }}</span>
                            <span class="text-sm text-muted-foreground">{{
                                item.body
                            }}</span>
                            <span class="text-xs text-muted-foreground">{{
                                item.created_at
                            }}</span>
                        </div>
                    </div>
                </li>
            </ul>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
