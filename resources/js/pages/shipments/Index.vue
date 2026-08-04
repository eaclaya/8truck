<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import ShipmentStatusBadge from '@/components/ShipmentStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { dashboard } from '@/routes';
import shipmentRoutes from '@/routes/shipments';

interface ShipmentRow {
    id: number;
    status: string;
    origin_city: string | null;
    destination_city: string | null;
    pickup_date: string;
    cargo_type: string;
    quotes_count: number;
    created_at: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    shipments: {
        data: ShipmentRow[];
        links: PaginationLink[];
    };
}>();

const cleanLabel = (label: string) =>
    label.replace('&laquo;', '\u00AB').replace('&raquo;', '\u00BB');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Shipments', href: shipmentRoutes.index() },
        ],
    },
});

const { t } = useTrans();
</script>

<template>
    <Head :title="t('Shipments')" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ t('My shipments') }}</h1>
            <Button as-child>
                <Link :href="shipmentRoutes.create()">
                    <Plus class="size-4" />{{ t('New shipment') }}</Link
                >
            </Button>
        </div>

        <div
            v-if="shipments.data.length === 0"
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
        >
            <p class="text-muted-foreground">
                {{
                    t(
                        'You have no shipments yet. Create one to start receiving quotes from transporters.',
                    )
                }}
            </p>
            <Button as-child>
                <Link :href="shipmentRoutes.create()">{{
                    t('Create your first shipment')
                }}</Link>
            </Button>
        </div>

        <div
            v-else
            class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-sidebar-border/70 text-left text-muted-foreground dark:border-sidebar-border"
                    >
                        <th class="px-4 py-3 font-medium">{{ t('Route') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Pickup') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Cargo') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Quotes') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="shipment in shipments.data"
                        :key="shipment.id"
                        class="border-b border-sidebar-border/40 last:border-0 hover:bg-muted/50 dark:border-sidebar-border/40"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="shipmentRoutes.show(shipment.id)"
                                class="font-medium hover:underline"
                            >
                                {{ shipment.origin_city }} →
                                {{ shipment.destination_city }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">{{ shipment.pickup_date }}</td>
                        <td class="px-4 py-3 capitalize">
                            {{ t('cargo.' + shipment.cargo_type) }}
                        </td>
                        <td class="px-4 py-3">{{ shipment.quotes_count }}</td>
                        <td class="px-4 py-3">
                            <ShipmentStatusBadge :status="shipment.status" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav
            v-if="shipments.links.length > 3"
            class="flex flex-wrap items-center gap-1"
        >
            <template v-for="(link, index) in shipments.links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded-md px-3 py-1.5 text-sm"
                    :class="
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-muted'
                    "
                >
                    {{ cleanLabel(link.label) }}
                </Link>
                <span v-else class="px-3 py-1.5 text-sm text-muted-foreground">
                    {{ cleanLabel(link.label) }}
                </span>
            </template>
        </nav>
    </div>
</template>
