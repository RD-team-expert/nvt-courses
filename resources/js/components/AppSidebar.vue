<script setup lang="ts">
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
    SidebarMenuSub,
    SidebarMenuSubItem,
    SidebarMenuSubButton
} from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Folder,
    LayoutGrid,
    Clock,
    BookOpenCheck,
    Users,
    Settings,
    BarChart,
    Bot,
    LayoutList,
    ChevronDown,
    Building2,
    Shield,
    UserCheck,
    History,
    Volume2
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLogo from './AppLogo.vue';

// Get current user from Inertia page props with better error handling
const page = usePage();
const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.is_admin === true || user.value?.role === 'admin');

// Track which submenus are open
const openSubmenus = ref({
    reportsAnalytics: false
});

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
    {
        title: 'Quizzes',
        href: '/quizzes',
        icon: LayoutList
    },
    {
        title: 'Profile',
        href: '/profile',
        icon: LayoutList
    },
    {
        title: 'Audio books',
        href: '/audio',
        icon: Clock,
    },
    {
        title: 'Online courses',
        href: '/courses-online',
        icon: BookOpenCheck,
    },
    {
        title: 'My feedback',
        href: '/my-feedback',
        icon: BookOpenCheck,
    },

];

// Reports & Analytics submenu items
const reportsSubItems = [
    {
        title: 'Courses',
        href: '/admin/courses',
        icon: BookOpenCheck,
    },
    {
        title: 'Quizzes',
        href: '/admin/quizzes',
        icon: LayoutList,
    },
    {
        title: 'Quizzes Assignment',
        href: '/admin/quiz-assignments/create',
        icon: LayoutList,
    },
    {
        title: 'Users',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Attendance',
        href: '/admin/attendance',
        icon: Clock,
    },
    {
        title: 'Reports',
        href: '/admin/reports',
        icon: BarChart,
    },
    {
        title: 'Departments',
        href: '/admin/departments',
        icon: Building2,
    },
    {
        title: 'User Levels',
        href: '/admin/user-levels',
        icon: Shield,
    },
    {
        title: 'Manager Roles',
        href: '/admin/manager-roles',
        icon: UserCheck,
    },
    {
        title: 'User Assignment',
        href: '/admin/users/assignment',
        icon: Users,
    },
    {
        title: 'History & Reports',
        href: '/admin/evaluations/history',
        icon: History,
    },
    {
        title: 'Audio Categories',
        href: '/admin/audio-categories',
        icon: Volume2,
    },

    {
        title: 'Video Categories',
        href: '/admin/video-categories',
        icon: Volume2,
    },
    {
        title: 'Bug Reports',
        href: '/admin/bug-reports',
        icon: BarChart,
    },
    {
        title: 'Feedback',
        href: '/admin/feedback',
        icon: UserCheck,

    }

];

// Other Admin-only navigation items (non-Reports)
const otherAdminNavItems: NavItem[] = [

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
        title: 'Resend Login Links',
        href: '/admin/resend-login-links',
        icon: Clock,
    },
    {
        title: 'Evaluations',
        href: '/admin/evaluations',
        icon: Clock,
    },
    {
        title: 'Evaluations Notifications',
        href: '/admin/evaluations/notifications',
        icon: Clock,
    },
    {
        title: 'User Evaluation',
        href: '/admin/evaluations/user-evaluation',
        icon: Clock,
    },
    {
        title: 'Admin audio',
        href: '/admin/audio',
        icon: Clock,
    },
    {
        title: 'Admin online course',
        href: '/admin/course-online',
        icon: Clock,
    },
    {
        title: 'Admin Video',
        href: '/admin/videos',
        icon: Clock,
    },
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

// Check if current URL matches any of the reports submenu items
const isReportsSubmenuActive = computed(() => {
    const currentUrl = page.url;
    return reportsSubItems.some(item => currentUrl.includes(item.href));
});

// Auto-open Reports & Analytics if any sub-item is active
if (isReportsSubmenuActive.value) {
    openSubmenus.value.reportsAnalytics = true;
}

// For debugging - log user data to console
console.log('User data:', user.value);
console.log('Is admin:', isAdmin.value);


</script>

<template  >
    <Sidebar collapsible="icon" variant="inset" class="bg-sidebar">
        <SidebarHeader class="bg-background" >
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

        <SidebarContent class="bg-background">
            <!-- User Navigation -->
            <NavMain :items="mainNavItems" />

            <!-- Admin Navigation (only shown to admins) -->
            <template v-if="isAdmin">
                <!-- Other Admin Navigation Items -->
                <NavMain :items="otherAdminNavItems" label="Admin" />

                <!-- Reports & Analytics Collapsible Menu in Admin Section -->
                <SidebarMenu>
                    <Collapsible
                        v-model:open="openSubmenus.reportsAnalytics"
                        class="group/collapsible"
                    >
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="isReportsSubmenuActive"
                                    class="w-full justify-between"
                                >
                                    <div class="flex items-center">
                                        <BarChart class="h-4 w-4" />
                                        <span>Reports & Analytics</span>
                                    </div>
                                    <ChevronDown class="h-4 w-4 transition-transform group-data-[state=open]/collapsible:rotate-180" />
                                </SidebarMenuButton>
                            </CollapsibleTrigger>

                            <CollapsibleContent>
                                <SidebarMenuSub>
                                    <SidebarMenuSubItem
                                        v-for="item in reportsSubItems"
                                        :key="item.href"
                                    >
                                        <SidebarMenuSubButton
                                            as-child
                                            :is-active="page.url.includes(item.href)"
                                        >
                                            <Link :href="item.href">
                                                <component :is="item.icon" class="h-4 w-4" />
                                                <span>{{ item.title }}</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>
                </SidebarMenu>
            </template>
        </SidebarContent>

        <SidebarFooter class="bg-background">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>


