<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref } from 'vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    department: Object,
    managerRoles: Array,
    users: Array,
})

const showAddManagerModal = ref(false)
const selectedManagerType = ref('')
const selectedManager = ref('')
const availableManagers = ref([])

// Remove manager role
const removeManagerRole = (roleId: number) => {
    if (!confirm('Are you sure you want to remove this manager role?')) {
        return;
    }

    router.delete(route('admin.departments.remove-manager', {
        department: props.department.id,
        role: roleId
    }), {
        preserveState: true,
        onSuccess: () => {
            alert('Manager role removed successfully!');
        },
        onError: (errors) => {
            console.error('Remove failed:', errors);
            alert('Failed to remove manager role. Please try again.');
        }
    });
}

// Load manager candidates
const loadManagerCandidates = async () => {
    try {
        const response = await fetch(route('admin.departments.manager-candidates', props.department.id))
        availableManagers.value = await response.json()
        showAddManagerModal.value = true
    } catch (error) {
        console.error('Failed to load manager candidates:', error)
        alert('Failed to load manager candidates')
    }
}

// Assign new manager
const assignManager = () => {
    if (!selectedManager.value || !selectedManagerType.value) {
        alert('Please select both manager and role type')
        return
    }

    router.post(route('admin.departments.assign-manager', props.department.id), {
        user_id: selectedManager.value,
        role_type: selectedManagerType.value,
        is_primary: selectedManagerType.value === 'department_head'
    }, {
        preserveState: true,
        onSuccess: () => {
            alert('Manager assigned successfully!')
            showAddManagerModal.value = false
            selectedManager.value = ''
            selectedManagerType.value = ''
        },
        onError: (errors) => {
            console.error('Assign failed:', errors)
            alert('Failed to assign manager. Please try again.')
        }
    })
}

// Get role type badge color
const getRoleTypeColor = (roleType: string) => {
    const colors = {
        'direct_manager': 'bg-blue-100 text-blue-800',
        'project_manager': 'bg-green-100 text-green-800',
        'department_head': 'bg-purple-100 text-purple-800',
        'senior_manager': 'bg-red-100 text-red-800',
        'team_lead': 'bg-yellow-100 text-yellow-800',
    };
    return colors[roleType] || 'bg-gray-100 text-gray-800';
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
    { name: 'Departments', href: route('admin.departments.index') },
    { name: props.department.name, href: route('admin.departments.show', props.department.id) }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <!-- Department Header -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 h-16 w-16">
                            <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-lg font-bold text-blue-600">
                                    {{ department.department_code }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ department.name }}</h1>
                            <p class="text-sm text-gray-600">{{ department.description }}</p>
                            <div class="mt-2 flex items-center space-x-4">
                                <span class="text-sm text-gray-500">
                                    <strong>Code:</strong> {{ department.department_code }}
                                </span>
                                <span class="text-sm text-gray-500" v-if="department.parent_name">
                                    <strong>Parent:</strong> {{ department.parent_name }}
                                </span>
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': department.is_active,
                                        'bg-red-100 text-red-800': !department.is_active
                                    }"
                                >
                                    {{ department.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <Link
                            :href="route('admin.departments.edit', department.id)"
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center"
                        >
                            Edit Department
                        </Link>
                        <button
                            @click="loadManagerCandidates"
                            class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition w-full sm:w-auto"
                        >
                            Add Manager
                        </button>
                    </div>
                </div>

                <!-- Hierarchy Path -->
                <div v-if="department.hierarchy_path" class="border-t pt-4">
                    <div class="text-sm text-gray-600">
                        <strong>Hierarchy:</strong> {{ department.hierarchy_path }}
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ users.length }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Manager Roles</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ managerRoles.length }}</dd>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Sub-Departments</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ department.children_count || 0 }}</dd>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Courses</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ department.active_courses || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Manager Roles Section -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Manager Roles</h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <div v-for="role in managerRoles" :key="role.id" class="p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-600">
                                                {{ role.manager.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center space-x-2">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ role.manager.name }}
                                            </p>
                                            <span v-if="role.is_primary"
                                                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Primary
                                            </span>
                                        </div>
                                        <div class="mt-1 flex items-center space-x-2">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                  :class="getRoleTypeColor(role.role_type)">
                                                {{ role.role_display }}
                                            </span>
                                            <span class="text-xs text-gray-500">{{ role.manager.level }}</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ role.manager.email }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            Started: {{ role.start_date }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <button
                                        @click="removeManagerRole(role.id)"
                                        class="text-red-600 hover:text-red-900 text-sm"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- No managers state -->
                        <div v-if="managerRoles.length === 0" class="p-6 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No managers assigned</h3>
                            <p class="mt-1 text-sm text-gray-500">Start by adding a manager to this department.</p>
                            <div class="mt-6">
                                <button
                                    @click="loadManagerCandidates"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    Add Manager
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Users Section -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Department Users</h2>
                    </div>
                    <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                        <div v-for="user in users" :key="user.id" class="p-6">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-600">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ user.name }}
                                    </p>
                                    <div class="mt-1 flex items-center space-x-2">
                                        <span v-if="user.level"
                                              class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                              :class="getLevelColor(user.level)">
                                            {{ user.level }}
                                        </span>
                                        <span v-if="user.employee_code" class="text-xs text-gray-500">
                                            ID: {{ user.employee_code }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ user.email }}
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <Link
                                        :href="route('admin.users.organizational', user.id)"
                                        class="text-blue-600 hover:text-blue-900 text-sm"
                                    >
                                        View
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- No users state -->
                        <div v-if="users.length === 0" class="p-6 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No users assigned</h3>
                            <p class="mt-1 text-sm text-gray-500">Users need to be assigned to this department.</p>
                            <div class="mt-6">
                                <Link
                                    :href="route('admin.users.assignment')"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    Assign Users
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sub-Departments Section -->
            <div v-if="department.children && department.children.length > 0" class="mt-6">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Sub-Departments</h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <div v-for="child in department.children" :key="child.id" class="p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-blue-600">
                                                {{ child.department_code }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ child.name }}
                                        </p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ child.description }}
                                        </p>
                                        <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500">
                                            <span>{{ child.users_count }} users</span>
                                            <span>{{ child.managers_count }} managers</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <Link
                                        :href="route('admin.departments.show', child.id)"
                                        class="text-blue-600 hover:text-blue-900 text-sm"
                                    >
                                        View Details
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Manager Modal -->
        <div v-if="showAddManagerModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showAddManagerModal = false"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Add Manager to {{ department.name }}
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Manager</label>
                                <select
                                    v-model="selectedManager"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Select Manager</option>
                                    <option v-for="manager in availableManagers" :key="manager.id" :value="manager.id">
                                        {{ manager.name }} ({{ manager.level }}) - {{ manager.department }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Role Type</label>
                                <select
                                    v-model="selectedManagerType"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Select Role Type</option>
                                    <option value="department_head">Department Head</option>
                                    <option value="senior_manager">Senior Manager</option>
                                    <option value="direct_manager">Direct Manager</option>
                                    <option value="project_manager">Project Manager</option>
                                    <option value="team_lead">Team Lead</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="assignManager"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Assign Manager
                        </button>
                        <button
                            @click="showAddManagerModal = false"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
