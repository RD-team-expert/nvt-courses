<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    departments: Array,
})

const expandedDepartments = ref(new Set())
const selectedView = ref('hierarchy') // 'hierarchy' or 'table'

// Toggle department expansion
const toggleDepartment = (departmentId: number) => {
    if (expandedDepartments.value.has(departmentId)) {
        expandedDepartments.value.delete(departmentId)
    } else {
        expandedDepartments.value.add(departmentId)
    }
}

// Get role type color
const getRoleTypeColor = (roleType: string) => {
    const colors = {
        'Direct Manager': 'bg-blue-100 text-blue-800 border-blue-200',
        'Project Manager': 'bg-green-100 text-green-800 border-green-200',
        'Department Head': 'bg-purple-100 text-purple-800 border-purple-200',
        'Senior Manager': 'bg-red-100 text-red-800 border-red-200',
        'Team Lead': 'bg-yellow-100 text-yellow-800 border-yellow-200',
    };
    return colors[roleType] || 'bg-gray-100 text-gray-800 border-gray-200';
}

// Get level color
const getLevelColor = (level: string) => {
    if (!level) return 'bg-gray-100 text-gray-600'

    const colors = {
        'L1': 'bg-blue-50 text-blue-700',
        'L2': 'bg-green-50 text-green-700',
        'L3': 'bg-orange-50 text-orange-700',
        'L4': 'bg-red-50 text-red-700',
    };
    return colors[level] || 'bg-gray-50 text-gray-700';
}

// Compute statistics
const stats = computed(() => {
    let totalManagers = 0
    let totalReports = 0
    let departmentHeads = 0
    let directManagers = 0

    props.departments.forEach(dept => {
        dept.roles.forEach(role => {
            totalManagers++
            if (role.managed_user) totalReports++
            if (role.role_type === 'Department Head') departmentHeads++
            if (role.role_type === 'Direct Manager') directManagers++
        })
    })

    return {
        totalDepartments: props.departments.length,
        totalManagers,
        totalReports,
        departmentHeads,
        directManagers
    }
})

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Manager Roles', href: route('admin.manager-roles.index') },
    { name: 'Matrix View', href: route('admin.manager-roles.matrix') }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-4">
                <h1 class="text-xl sm:text-2xl font-bold">Manager Roles Matrix</h1>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Link
                        :href="route('admin.manager-roles.create')"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center"
                    >
                        Assign New Role
                    </Link>
                    <Link
                        :href="route('admin.manager-roles.index')"
                        class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition w-full sm:w-auto text-center"
                    >
                        List View
                    </Link>
                </div>
            </div>

            <!-- View Toggle -->
            <div class="mb-6">
                <div class="sm:hidden">
                    <label for="view-select" class="sr-only">Select a view</label>
                    <select
                        id="view-select"
                        v-model="selectedView"
                        class="block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="hierarchy">Hierarchy View</option>
                        <option value="table">Table View</option>
                    </select>
                </div>
                <div class="hidden sm:block">
                    <nav class="flex space-x-8" aria-label="Tabs">
                        <button
                            @click="selectedView = 'hierarchy'"
                            :class="{
                                'border-indigo-500 text-indigo-600': selectedView === 'hierarchy',
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': selectedView !== 'hierarchy'
                            }"
                            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                        >
                            Hierarchy View
                        </button>
                        <button
                            @click="selectedView = 'table'"
                            :class="{
                                'border-indigo-500 text-indigo-600': selectedView === 'table',
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': selectedView !== 'table'
                            }"
                            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
                        >
                            Table View
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-3">
                            <dt class="text-xs font-medium text-gray-500 truncate">Departments</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ stats.totalDepartments }}</dd>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-3">
                            <dt class="text-xs font-medium text-gray-500 truncate">Managers</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ stats.totalManagers }}</dd>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-3">
                            <dt class="text-xs font-medium text-gray-500 truncate">Direct Reports</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ stats.totalReports }}</dd>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-3">
                            <dt class="text-xs font-medium text-gray-500 truncate">Dept Heads</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ stats.departmentHeads }}</dd>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-3">
                            <dt class="text-xs font-medium text-gray-500 truncate">Direct Mgrs</dt>
                            <dd class="text-lg font-semibold text-gray-900">{{ stats.directManagers }}</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hierarchy View -->
            <div v-show="selectedView === 'hierarchy'" class="space-y-6">
                <div v-for="department in departments" :key="department.id" class="bg-white rounded-lg shadow">
                    <!-- Department Header -->
                    <div
                        @click="toggleDepartment(department.id)"
                        class="cursor-pointer px-6 py-4 border-b border-gray-200 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="shrink-0">
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ department.name }}</h3>
                                    <p class="text-sm text-gray-500">{{ department.roles.length }} manager roles</p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <span class="mr-3 text-sm text-gray-500">
                                    {{ expandedDepartments.has(department.id) ? 'Collapse' : 'Expand' }}
                                </span>
                                <svg
                                    :class="{ 'rotate-90': expandedDepartments.has(department.id) }"
                                    class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Department Roles -->
                    <div v-show="expandedDepartments.has(department.id)" class="p-6">
                        <div v-if="department.roles.length === 0" class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No managers assigned</h3>
                            <p class="mt-1 text-sm text-gray-500">This department doesn't have any manager roles yet.</p>
                        </div>

                        <div v-else class="grid gap-4">
                            <div
                                v-for="(role, index) in department.roles"
                                :key="index"
                                class="relative border rounded-lg p-4"
                                :class="role.is_primary ? 'border-blue-300 bg-blue-50' : 'border-gray-200 bg-white'"
                            >
                                <!-- Primary Badge -->
                                <div v-if="role.is_primary" class="absolute top-2 right-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        Primary
                                    </span>
                                </div>

                                <div class="flex items-start justify-between">
                                    <div class="flex items-center space-x-4 flex-1">
                                        <!-- Manager Info -->
                                        <div class="shrink-0">
                                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-600">
                                                    {{ role.manager.name.charAt(0).toUpperCase() }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center space-x-2">
                                                <p class="text-sm font-medium text-gray-900">{{ role.manager.name }}</p>
                                                <span v-if="role.manager.level"
                                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                      :class="getLevelColor(role.manager.level)">
                                                    {{ role.manager.level }}
                                                </span>
                                            </div>
                                            <div class="mt-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border"
                                                      :class="getRoleTypeColor(role.role_type)">
                                                    {{ role.role_type }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Arrow -->
                                        <div v-if="role.managed_user" class="shrink-0">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5-5 5M6 7l5 5-5 5"></path>
                                            </svg>
                                        </div>

                                        <!-- Managed User Info -->
                                        <div v-if="role.managed_user" class="shrink-0">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                    <span class="text-xs font-medium text-green-600">
                                                        {{ role.managed_user.name.charAt(0).toUpperCase() }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-medium text-gray-700">{{ role.managed_user.name }}</p>
                                                    <p v-if="role.managed_user.level" class="text-xs text-gray-500">{{ role.managed_user.level }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Department-wide role indicator -->
                                        <div v-else class="shrink-0 text-center">
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Department-wide</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table View -->
            <div v-show="selectedView === 'table'" class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Department
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Manager
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Role Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Manages
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <template v-for="department in departments" :key="department.id">
                        <tr v-for="(role, index) in department.roles" :key="`${department.id}-${index}`"
                            :class="{ 'bg-blue-50': role.is_primary }">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ department.name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-600">
                                                {{ role.manager.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ role.manager.name }}</div>
                                        <div class="text-sm text-gray-500">{{ role.manager.level || 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border"
                                      :class="getRoleTypeColor(role.role_type)">
                                    {{ role.role_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="role.managed_user" class="text-sm">
                                    <div class="font-medium text-gray-900">{{ role.managed_user.name }}</div>
                                    <div class="text-gray-500">{{ role.managed_user.level || 'N/A' }}</div>
                                </div>
                                <div v-else class="text-sm text-gray-400">
                                    Department-wide role
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="role.is_primary"
                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    Primary
                                </span>
                                <span v-else
                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    Secondary
                                </span>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>

                <!-- Empty state -->
                <div v-if="!departments || departments.length === 0" class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No manager roles found</h3>
                        <p class="mt-1 text-sm text-gray-500">Start by assigning manager roles to users.</p>
                        <div class="mt-6">
                            <Link
                                :href="route('admin.manager-roles.create')"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                            >
                                Assign New Role
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
