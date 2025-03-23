<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
// Add the Reports icon import
import { BookOpen, Folder, LayoutGrid, Clock, BookOpenCheck, Users, Settings, BarChart, Bot } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

// Get current user from Inertia page props with better error handling
const page = usePage();
const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.is_admin === true || user.value?.role === 'admin');

// Common navigation items for all users
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Courses',
        href: '/courses',
        icon: BookOpenCheck,
    },
    {
        title: 'Attendance',
        href: '/attendance',
        icon: Clock,
    },
];

// Admin-only navigation items
// Add Reports to adminNavItems array
const adminNavItems: NavItem[] = [
    {
        title: 'Course Management',
        href: '/admin/courses',
        icon: BookOpenCheck,
    },
    {
        title: 'User Management',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Attendance Management',
        href: '/admin/attendance',
        icon: Clock,
    },
    {
        title: 'Reports & Analytics',
        href: '/admin/reports',
        icon: BarChart,
    },

    // {
    //     title: 'Ai',
    //     href: '/gemini',
    //     icon: Bot,
    // },
    // {
    //     title: 'Settings',
    //     href: '/admin/settings',
    //     icon: Settings,
    // },
];

// External links for footer
const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
        external: true,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
        external: true,
    },
];

// For debugging - log user data to console
console.log('User data:', user.value);
console.log('Is admin:', isAdmin.value);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <!-- User Navigation -->
            <NavMain :items="mainNavItems" />
            
            <!-- Admin Navigation (only shown to admins) -->
            <template v-if="isAdmin">
                <!-- <div class="px-3 pt-2 pb-1">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Admin
                    </div>
                </div> -->
                <NavMain :items="adminNavItems" label="Admin" />
            </template>
        </SidebarContent>

        <SidebarFooter>
            <!-- <NavFooter :items="footerNavItems" /> -->
            <NavUser />
            <!-- <div class="px-3 py-2 text-xs text-center text-gray-500 dark:text-gray-400">
                Made with ❤️ by R&D team
            </div> -->
            
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
