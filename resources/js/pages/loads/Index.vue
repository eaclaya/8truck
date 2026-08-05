<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { dashboard } from '@/routes';
import loadRoutes from '@/routes/loads';
import regionRoutes from '@/routes/regions';

interface LoadRow {
    id: number;
    status: string;
    origin_city: string | null;
    destination_city: string | null;
    pickup_date: string;
    cargo_type: string;
    weight_kg: number | null;
    truck_type: string | null;
    budget_amount: string | null;
    currency: string;
    quotes_count: number;
    has_my_quote: boolean;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    loads: {
        data: LoadRow[];
        links: PaginationLink[];
    };
    onlyMyRegions: boolean;
    hasRegions: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Loads', href: loadRoutes.index() },
        ],
    },
});

const { t } = useTrans();

const cleanLabel = (label: string) =>
    label.replace('&laquo;', '«').replace('&raquo;', '»');
</script>

<template>
    <Head :title="t('Available loads')" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold">{{ t('Available loads') }}</h1>
            <div class="flex rounded-lg border border-input p-0.5 text-sm">
                <Link
                    :href="loadRoutes.index()"
                    class="rounded-md px-3 py-1"
                    :class="
                        onlyMyRegions
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-muted'
                    "
                >
                    {{ t('In my regions') }}
                </Link>
                <Link
                    :href="loadRoutes.index({ query: { all: 1 } })"
                    class="rounded-md px-3 py-1"
                    :class="
                        !onlyMyRegions
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-muted'
                    "
                >
                    {{ t('All of Honduras') }}
                </Link>
            </div>
        </div>

        <div
            v-if="loads.data.length === 0"
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border"
        >
            <template v-if="onlyMyRegions && !hasRegions">
                <p class="text-muted-foreground">
                    {{
                        t(
                            'Define your operating regions to see loads near you.',
                        )
                    }}
                </p>
                <Button as-child>
                    <Link :href="regionRoutes.index()">{{
                        t('Define regions')
                    }}</Link>
                </Button>
            </template>
            <p v-else class="text-muted-foreground">
                {{
                    t(
                        'No loads available right now. Check back soon or widen your regions.',
                    )
                }}
            </p>
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
                        <th class="px-4 py-3 font-medium">
                            {{ t('Truck type') }}
                        </th>
                        <th class="px-4 py-3 font-medium">{{ t('Budget') }}</th>
                        <th class="px-4 py-3 font-medium">{{ t('Quotes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="load in loads.data"
                        :key="load.id"
                        class="border-b border-sidebar-border/40 last:border-0 hover:bg-muted/50 dark:border-sidebar-border/40"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="loadRoutes.show(load.id)"
                                class="font-medium hover:underline"
                            >
                                {{ load.origin_city }} →
                                {{ load.destination_city }}
                            </Link>
                            <Badge
                                v-if="load.has_my_quote"
                                variant="outline"
                                class="ml-2 bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                            >
                                {{ t('Quoted by you') }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">{{ load.pickup_date }}</td>
                        <td class="px-4 py-3">
                            {{ t('cargo.' + load.cargo_type) }}
                            <span
                                v-if="load.weight_kg"
                                class="text-muted-foreground"
                            >
                                · {{ load.weight_kg.toLocaleString() }} kg
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            {{ load.truck_type ?? t('Any truck') }}
                        </td>
                        <td class="px-4 py-3">
                            {{
                                load.budget_amount
                                    ? `${load.currency} ${Number(load.budget_amount).toLocaleString()}`
                                    : '—'
                            }}
                        </td>
                        <td class="px-4 py-3">{{ load.quotes_count }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav
            v-if="loads.links.length > 3"
            class="flex flex-wrap items-center gap-1"
        >
            <template v-for="(link, index) in loads.links" :key="index">
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
