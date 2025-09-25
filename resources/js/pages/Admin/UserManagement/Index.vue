<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref } from 'vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    users: Object, // Paginated users
    departments: Array,
    userLevels: Array,
    stats: Object,
})

const selectedUsers = ref([])
const bulkAction = ref('')
const selectedDepartment = ref('')
const selectedLevel = ref('')

// Bulk assign department
const bulkAssignDepartment = () => {
    if (selectedUsers.value.length === 0) {
        alert('Please select users first');
        return;
    }

    if (!selectedDepartment.value) {
        alert('Please select a department');
        return;
    }

    router.post('/admin/users/bulk-assign-department', {
        user_ids: selectedUsers.value,
        department_id: selectedDepartment.value
    }, {
        preserveState: true,
        onSuccess: () => {
            alert('Users assigned to department successfully!');
            selectedUsers.value = [];
        },
        onError: (errors) => {
            console.error('Bulk assignment failed:', errors);
            alert('Failed to assign users. Please try again.');
        }
    });
}

// Bulk assign level
const bulkAssignLevel = () => {
    if (selectedUsers.value.length === 0) {
        alert('Please select users first');
        return;
    }

    if (!selectedLevel.value) {
        alert('Please select a level');
        return;
    }

    router.post('/admin/users/bulk-assign-level', {
        user_ids: selectedUsers.value,
        user_level_id: selectedLevel.value
    }, {
        preserveState: true,
        onSuccess: () => {
            alert('Users assigned to level successfully!');
            selectedUsers.value = [];
        },
        onError: (errors) => {
            console.error('Bulk assignment failed:', errors);
            alert('Failed to assign users. Please try again.');
        }
    });
}

// Toggle all users selection
const toggleAll = (event) => {
    if (event.target.checked) {
        selectedUsers.value = props.users.data.map(user => user.id);
    } else {
        selectedUsers.value = [];
    }
}

// Get level badge color
const getLevelColor = (level) => {
    if (!level) return 'bg-gray-100 text-gray-800';

    const colors = {
        'L1': 'bg-blue-100 text-blue-800',
        'L2': 'bg-green-100 text-green-800',
        'L3': 'bg-orange-100 text-orange-800',
        'L4': 'bg-red-100 text-red-800',
    };
    return colors[level] || 'bg-gray-100 text-gray-800';
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'User Management', href: route('admin.users.assignment') },
    { name: 'User Assignment', href: route('admin.users.assignment') }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-4">
                <h1 class="text-xl sm:text-2xl font-bold">User Management & Assignment</h1>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Link
                        :href="route('admin.users.import')"
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition w-full sm:w-auto text-center"
                    >
                        Import Users
                    </Link>
                    <Link
                        :href="route('admin.users.export')"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center"
                    >
                        Export Users
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.total_users || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">With Departments</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.users_with_departments || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">With Levels</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.users_with_levels || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">With Managers</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.users_with_managers || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div v-if="selectedUsers.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-yellow-800 font-medium">{{ selectedUsers.length }} user(s) selected</span>
                    </div>
                    <button
                        @click="selectedUsers = []"
                        class="text-yellow-600 hover:text-yellow-800"
                    >
                        Clear Selection
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Bulk Assign Department -->
                    <div class="flex gap-2">
                        <select
                            v-model="selectedDepartment"
                            class="flex-1 border border-gray-300 rounded px-3 py-2 focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Select Department</option>
                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                {{ dept.name }}
                            </option>
                        </select>
                        <button
                            @click="bulkAssignDepartment"
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition"
                        >
                            Assign Dept
                        </button>
                    </div>

                    <!-- Bulk Assign Level -->
                    <div class="flex gap-2">
                        <select
                            v-model="selectedLevel"
                            class="flex-1 border border-gray-300 rounded px-3 py-2 focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">Select Level</option>
                            <option v-for="level in userLevels" :key="level.id" :value="level.id">
                                {{ level.code }} - {{ level.name }}
                            </option>
                        </select>
                        <button
                            @click="bulkAssignLevel"
                            class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition"
                        >
                            Assign Level
                        </button>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input
                                type="checkbox"
                                @change="toggleAll"
                                :checked="selectedUsers.length === users.data.length && users.data.length > 0"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            User
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Department
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Level
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Manager
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="user in users.data" :key="user.id" :class="{ 'bg-blue-50': selectedUsers.includes(user.id) }">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input
                                type="checkbox"
                                v-model="selectedUsers"
                                :value="user.id"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-600">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                                    <div v-if="user.employee_code" class="text-xs text-gray-400">ID: {{ user.employee_code }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div v-if="user.department" class="text-sm">
                                <div class="font-medium text-gray-900">{{ user.department.name }}</div>
                                <div class="text-gray-500">{{ user.department.department_code }}</div>
                            </div>
                            <div v-else class="text-sm text-gray-400 italic">
                                No department assigned
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span v-if="user.user_level"
                                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                  :class="getLevelColor(user.user_level.code)">
                                {{ user.user_level.code }} - {{ user.user_level.name }}
                            </span>
                            <span v-else class="text-sm text-gray-400 italic">
                                No level assigned
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div v-if="user.managers && user.managers.length > 0" class="text-sm">
                                <div v-for="manager in user.managers.slice(0, 2)" :key="manager.id" class="mb-1">
                                    <span class="font-medium text-gray-900">{{ manager.name }}</span>
                                    <span class="text-xs text-gray-500 ml-1">({{ manager.pivot.role_type }})</span>
                                </div>
                                <div v-if="user.managers.length > 2" class="text-xs text-gray-400">
                                    +{{ user.managers.length - 2 }} more
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-400 italic">
                                No manager assigned
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                :class="{
                                    'bg-green-100 text-green-800': user.status === 'active',
                                    'bg-red-100 text-red-800': user.status === 'inactive',
                                    'bg-yellow-100 text-yellow-800': user.status === 'on_leave'
                                }"
                            >
                                {{ user.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <Link
                                :href="route('admin.users.organizational', user.id)"
                                class="text-blue-600 hover:text-blue-900 mr-3 transition-colors"
                            >
                                View
                            </Link>
                            <Link
                                :href="route('admin.users.assign-form', user.id)"
                                class="text-indigo-600 hover:text-indigo-900 transition-colors"
                            >
                                Assign
                            </Link>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="users.links && users.links.length > 3" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link
                            v-if="users.prev_page_url"
                            :href="users.prev_page_url"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="users.next_page_url"
                            :href="users.next_page_url"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Next
                        </Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <template v-for="link in users.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        class="relative inline-flex items-center px-2 py-2 border text-sm font-medium"
                                        :class="{
                                            'z-10 bg-blue-50 border-blue-500 text-blue-600': link.active,
                                            'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': !link.active
                                        }"
                                        v-html="link.label"
                                    />
                                    <span
                                        v-else
                                        class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-300"
                                        v-html="link.label"
                                    />
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="!users.data || users.data.length === 0" class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                        <p class="mt-1 text-sm text-gray-500">Start by importing users or check your filters.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
