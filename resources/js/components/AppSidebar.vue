<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Briefcase,
    FileCheck,
    FolderGit2,
    LayoutGrid,
    MapPin,
    Package,
    Search,
    Truck,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import documents from '@/routes/documents';
import jobs from '@/routes/jobs';
import loads from '@/routes/loads';
import regions from '@/routes/regions';
import shipments from '@/routes/shipments';
import trucks from '@/routes/trucks';
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Shipments',
            href: shipments.index(),
            icon: Package,
        },
    ];

    if (page.props.auth.isTransporter) {
        items.push(
            {
                title: 'Loads',
                href: loads.index(),
                icon: Search,
            },
            {
                title: 'Jobs',
                href: jobs.index(),
                icon: Briefcase,
            },
            {
                title: 'Trucks',
                href: trucks.index(),
                icon: Truck,
            },
            {
                title: 'Regions',
                href: regions.index(),
                icon: MapPin,
            },
            {
                title: 'Documents',
                href: documents.index(),
                icon: FileCheck,
            },
        );
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
