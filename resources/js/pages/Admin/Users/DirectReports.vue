<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { computed } from 'vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    manager: Object,
    directReports: Array,
    departmentRoles: Array,
    stats: Object,
})

// Get level color
const getLevelColor = (hierarchyLevel: number) => {
    const colors = {
        1: 'bg-blue-100 text-blue-800',
        2: 'bg-green-100 text-green-800',
        3: 'bg-orange-100 text-orange-800',
        4: 'bg-red-100 text-red-800',
    };
    return colors[hierarchyLevel] || 'bg-gray-100 text-gray-800';
}

// Get role color
const getRoleColor = (roleType: string) => {
    const colors = {
        'direct_manager': 'bg-blue-100 text-blue-800',
        'project_manager': 'bg-green-100 text-green-800',
        'department_head': 'bg-purple-100 text-purple-800',
        'senior_manager': 'bg-red-100 text-red-800',
        'team_lead': 'bg-yellow-100 text-yellow-800',
    };
    return colors[roleType] || 'bg-gray-100 text-gray-800';
}

// Computed stats
const computedStats = computed(() => ({
    totalReports: props.directReports?.length || 0,
    activeReports: props.directReports?.filter(r => r.status === 'active').length || 0,
    departmentRoles: props.departmentRoles?.length || 0,
    primaryReports: props.directReports?.filter(r => r.is_primary).length || 0,
}))

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Users', href: route('admin.users.index') },
    { name: props.manager?.name || 'Manager', href: '#' },
    { name: 'Direct Reports', href: route('admin.users.direct-reports', props.manager?.id) }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 h-16 w-16">
                            <div class="h-16 w-16 rounded-full bg-blue-200 flex items-center justify-center">
                                <span class="text-xl font-bold text-blue-600">
                                    {{ manager?.name?.charAt(0).toUpperCase() || '?' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ manager?.name || 'Unknown Manager' }}'s Team</h1>
                            <p class="text-sm text-gray-600">{{ manager?.email || 'No email' }}</p>
                            <div class="mt-2 flex items-center space-x-4">
                                <span v-if="manager?.level" class="text-sm text-gray-500">
                                    <strong>Level:</strong> {{ manager.level }}
                                </span>
                                <span v-if="manager?.department" class="text-sm text-gray-500">
                                    <strong>Department:</strong> {{ manager.department }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <Link
                            :href="route('admin.users.organizational', manager?.id)"
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center"
                            v-if="manager?.id"
                        >
                            View Profile
                        </Link>
                        <Link
                            :href="route('admin.users.reporting-chain', manager?.id)"
                            class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 transition w-full sm:w-auto text-center"
                            v-if="manager?.id"
                        >
                            Reporting Chain
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Reports</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ computedStats.totalReports }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ computedStats.activeReports }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Dept Roles</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ computedStats.departmentRoles }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Primary</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ computedStats.primaryReports }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Direct Reports List -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Direct Reports</h2>
                        </div>

                        <div v-if="directReports && directReports.length > 0" class="divide-y divide-gray-200">
                            <div v-for="report in directReports" :key="report.id"
                                 class="p-6 hover:bg-gray-50 transition-colors"
                                 :class="{ 'bg-blue-50': report.is_primary }">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0 h-12 w-12">
                                            <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                                                <span class="text-lg font-medium text-gray-600">
                                                    {{ report.name.charAt(0).toUpperCase() }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <h3 class="text-sm font-medium text-gray-900">{{ report.name }}</h3>
                                                <span v-if="report.is_primary"
                                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    Primary Report
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-500">{{ report.email }}</p>
                                            <div class="flex items-center space-x-4 mt-2">
                                                <span v-if="report.employee_code" class="text-xs text-gray-400">
                                                    ID: {{ report.employee_code }}
                                                </span>
                                                <span v-if="report.level"
                                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                      :class="getLevelColor(report.level_hierarchy)">
                                                    {{ report.level }}
                                                </span>
                                                <span v-if="report.department" class="text-xs text-gray-400">
                                                    {{ report.department }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-4">
                                        <div class="text-right">
                                            <span v-if="report.role_display"
                                                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                  :class="getRoleColor(report.role_type)">
                                                {{ report.role_display }}
                                            </span>
                                            <div v-if="report.start_date" class="text-xs text-gray-500 mt-1">
                                                Since {{ report.start_date }}
                                            </div>
                                        </div>

                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                              :class="{
                                                  'bg-green-100 text-green-800': report.status === 'active',
                                                  'bg-red-100 text-red-800': report.status === 'inactive',
                                                  'bg-yellow-100 text-yellow-800': report.status === 'on_leave'
                                              }">
                                            {{ report.status }}
                                        </span>

                                        <Link
                                            :href="route('admin.users.organizational', report.id)"
                                            class="text-blue-600 hover:text-blue-900 transition-colors"
                                        >
                                            View Profile
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No direct reports</h3>
                            <p class="mt-1 text-sm text-gray-500">This manager doesn't have any direct reports assigned.</p>
                        </div>
                    </div>
                </div>

                <!-- Department-Wide Roles Sidebar -->
                <div class="space-y-6">
                    <!-- Department Roles -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Department-Wide Roles</h2>

                        <div v-if="departmentRoles && departmentRoles.length > 0" class="space-y-3">
                            <div v-for="role in departmentRoles" :key="role.id"
                                 class="border rounded-lg p-3"
                                 :class="{ 'border-blue-300 bg-blue-50': role.is_primary }">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :class="getRoleColor(role.role_type)">
                                        {{ role.role_display }}
                                    </span>
                                    <span v-if="role.is_primary"
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        Primary
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900 mb-1">{{ role.department }}</div>
                                <div class="text-xs text-gray-500">
                                    Authority Level {{ role.authority_level }}
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-4 text-gray-500">
                            <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No department-wide roles</p>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>

                        <div class="space-y-3">
                            <Link
                                :href="route('admin.manager-roles.create')"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors w-full"
                            >
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Assign New Role
                            </Link>

                            <Link
                                :href="route('admin.manager-roles.index')"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors w-full"
                            >
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                View All Roles
                            </Link>

                            <Link
                                :href="route('admin.manager-roles.matrix')"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors w-full"
                            >
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                </svg>
                                Matrix View
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
