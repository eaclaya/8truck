<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { useTrans } from '@/composables/useTrans';

const props = defineProps<{
    status: string;
}>();

const labels: Record<string, string> = {
    draft: 'Draft',
    published: 'Published',
    quoted: 'Quoted',
    accepted: 'Accepted',
    driver_assigned: 'Driver assigned',
    picked_up: 'Picked up',
    in_transit: 'In transit',
    delivered: 'Delivered',
    completed: 'Completed',
    cancelled: 'Cancelled',
    expired: 'Expired',
    pending: 'Pending',
    rejected: 'Rejected',
    withdrawn: 'Withdrawn',
};

const classes: Record<string, string> = {
    draft: 'bg-muted text-muted-foreground',
    published:
        'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
    quoted: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    accepted:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    driver_assigned:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
    picked_up:
        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
    in_transit:
        'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
    delivered:
        'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
    completed:
        'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    expired: 'bg-muted text-muted-foreground',
    pending:
        'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    withdrawn: 'bg-muted text-muted-foreground',
};

const { t } = useTrans();
</script>

<template>
    <Badge variant="outline" :class="classes[props.status]">
        {{ t(labels[props.status] ?? props.status) }}
    </Badge>
</template>
