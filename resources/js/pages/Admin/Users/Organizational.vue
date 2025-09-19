<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { computed } from 'vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    user: Object,
    managerRoles: Array,
    directReports: Array,
    managers: Array,
})

// Get level color
const getLevelColor = (levelCode: string) => {
    const colors = {
        'L1': 'bg-blue-100 text-blue-800',
        'L2': 'bg-green-100 text-green-800',
        'L3': 'bg-orange-100 text-orange-800',
        'L4': 'bg-red-100 text-red-800',
    };
    return colors[levelCode] || 'bg-gray-100 text-gray-800';
}

// Get role type color
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

// Stats
const stats = computed(() => ({
    managerRoles: props.managerRoles?.length || 0,
    directReports: props.directReports?.length || 0,
    managers: props.managers?.length || 0,
    isManager: (props.managerRoles?.length || 0) > 0
}))

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Users', href: route('admin.users.index') },
    { name: props.user?.name || 'User', href: '#' },
    { name: 'Organizational Profile', href: route('admin.users.organizational', props.user?.id) }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <!-- User Header -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 h-16 w-16">
                            <div class="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center">
                                <span class="text-xl font-bold text-gray-600">
                                    {{ user?.name?.charAt(0).toUpperCase() || '?' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ user?.name || 'Unknown User' }}</h1>
                            <p class="text-sm text-gray-600">{{ user?.email || 'No email' }}</p>
                            <div class="mt-2 flex items-center space-x-4">
                                <span v-if="user?.employee_code" class="text-sm text-gray-500">
                                    <strong>ID:</strong> {{ user.employee_code }}
                                </span>
                                <span v-if="user?.level"
                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                      :class="getLevelColor(user.level.code)">
                                    {{ user.level.code }} - {{ user.level.name }}
                                </span>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-800': user?.status === 'active',
                                          'bg-red-100 text-red-800': user?.status === 'inactive'
                                      }">
                                    {{ user?.status || 'Unknown' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <Link
                            :href="route('admin.users.reporting-chain', user?.id)"
                            class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600 transition w-full sm:w-auto text-center"
                            v-if="user?.id"
                        >
                            View Reporting Chain
                        </Link>
                        <Link
                            :href="route('admin.users.direct-reports', user?.id)"
                            class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition w-full sm:w-auto text-center"
                            v-if="stats.isManager && user?.id"
                        >
                            View Team ({{ stats.directReports }})
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Manager Roles</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.managerRoles }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

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
                                <dt class="text-sm font-medium text-gray-500 truncate">Direct Reports</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.directReports }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Reports To</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.managers }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Hierarchy Level</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ user?.level?.hierarchy_level || 'N/A' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- User Details -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">User Information</h2>

                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-700">Department</dt>
                            <dd class="mt-1">
                                <div v-if="user?.department" class="text-sm text-gray-900">
                                    {{ user.department.name }} ({{ user.department.code }})
                                    <span v-if="user.department.parent" class="text-gray-500 ml-2">
                                        → {{ user.department.parent }}
                                    </span>
                                </div>
                                <div v-else class="text-sm text-gray-400 italic">No department assigned</div>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-700">User Level</dt>
                            <dd class="mt-1">
                                <span v-if="user?.level"
                                      class="inline-flex items-center px-2 py-0.5 rounded text-sm font-medium"
                                      :class="getLevelColor(user.level.code)">
                                    {{ user.level.code }} - {{ user.level.name }} (Level {{ user.level.hierarchy_level }})
                                </span>
                                <span v-else class="text-sm text-gray-400 italic">No level assigned</span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-700">Employee Code</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ user?.employee_code || 'Not assigned' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-700">Join Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ user?.created_at || 'Unknown' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-700">Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-800': user?.status === 'active',
                                          'bg-red-100 text-red-800': user?.status === 'inactive',
                                          'bg-yellow-100 text-yellow-800': user?.status === 'on_leave'
                                      }">
                                    {{ user?.status || 'Unknown' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Manager Roles -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        Manager Roles ({{ stats.managerRoles }})
                    </h2>

                    <div v-if="managerRoles && managerRoles.length > 0" class="space-y-3">
                        <div v-for="role in managerRoles" :key="role.id"
                             class="border rounded-lg p-4"
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
                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                <span>Authority Level {{ role.authority_level }}</span>
                                <span v-if="role.start_date">Since {{ role.start_date }}</span>
                                <span class="px-2 py-0.5 rounded"
                                      :class="role.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                    {{ role.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No manager roles</h3>
                        <p class="mt-1 text-sm text-gray-500">This user is not assigned any management responsibilities.</p>
                    </div>
                </div>
            </div>

            <!-- Reports and Managers Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <!-- Direct Reports -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">
                            Direct Reports ({{ stats.directReports }})
                        </h2>
                        <Link
                            v-if="stats.directReports > 0 && user?.id"
                            :href="route('admin.users.direct-reports', user.id)"
                            class="text-blue-600 hover:text-blue-900 text-sm transition-colors"
                        >
                            View All →
                        </Link>
                    </div>

                    <div v-if="directReports && directReports.length > 0" class="space-y-3">
                        <div v-for="report in directReports.slice(0, 5)" :key="report.id" class="flex items-center space-x-3 p-3 border rounded-lg">
                            <div class="flex-shrink-0 h-8 w-8">
                                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-xs font-medium text-gray-600">
                                        {{ report.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900">{{ report.name }}</div>
                                <div class="text-sm text-gray-500">{{ report.email }}</div>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span v-if="report.level" class="text-xs text-gray-400">{{ report.level }}</span>
                                    <span v-if="report.department" class="text-xs text-gray-400">{{ report.department }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-if="directReports.length > 5" class="text-center pt-2">
                            <Link
                                :href="route('admin.users.direct-reports', user.id)"
                                class="text-blue-600 hover:text-blue-900 text-sm transition-colors"
                            >
                                View {{ directReports.length - 5 }} more reports
                            </Link>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No direct reports</h3>
                        <p class="mt-1 text-sm text-gray-500">This user doesn't manage anyone directly.</p>
                    </div>
                </div>

                <!-- Managers -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">
                            Reports To ({{ stats.managers }})
                        </h2>
                        <Link
                            v-if="user?.id"
                            :href="route('admin.users.reporting-chain', user.id)"
                            class="text-blue-600 hover:text-blue-900 text-sm transition-colors"
                        >
                            View Chain →
                        </Link>
                    </div>

                    <div v-if="managers && managers.length > 0" class="space-y-3">
                        <div v-for="manager in managers" :key="manager.id" class="flex items-center space-x-3 p-3 border rounded-lg">
                            <div class="flex-shrink-0 h-8 w-8">
                                <div class="h-8 w-8 rounded-full bg-blue-200 flex items-center justify-center">
                                    <span class="text-xs font-medium text-blue-600">
                                        {{ manager.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900">{{ manager.name }}</div>
                                <div class="text-sm text-gray-500">{{ manager.email }}</div>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span v-if="manager.level" class="text-xs text-gray-400">{{ manager.level }}</span>
                                    <span v-if="manager.department" class="text-xs text-gray-400">{{ manager.department }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No direct managers</h3>
                        <p class="mt-1 text-sm text-gray-500">This user doesn't have assigned managers.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
