<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref } from 'vue'
import { type BreadcrumbItemType } from '@/types'
import NotificationModal from '@/components/modals/NotificationModal.vue'
import ConfirmationModal from '@/components/modals/ConfirmationModal.vue'
import LoadingModal from '@/components/modals/LoadingModal.vue'

const props = defineProps({
    departments: Array,
    stats: Object,
})

// Modal states
const showNotification = ref(false)
const showConfirmation = ref(false)
const showLoading = ref(false)

// Notification states
const notification = ref({
    type: 'info',
    title: '',
    message: ''
})

// Confirmation states
const confirmation = ref({
    title: '',
    message: '',
    action: null as (() => void) | null
})

const loading = ref({
    message: 'Loading...'
})

// Helper function to show notifications
const showNotificationModal = (type: string, title: string, message: string) => {
    notification.value = { type, title, message }
    showNotification.value = true
}

// Helper function to show confirmations
const showConfirmationModal = (title: string, message: string, action: () => void) => {
    confirmation.value = { title, message, action }
    showConfirmation.value = true
}

// Delete department function with confirmation
const deleteDepartment = (departmentId: number, departmentName: string) => {
    showConfirmationModal(
        'Delete Department',
        `Are you sure you want to delete "${departmentName}"? This action cannot be undone and will permanently remove all associated data.`,
        () => {
            showLoading.value = true
            loading.value.message = 'Deleting department...'

            router.delete(route('admin.departments.destroy', departmentId), {
                preserveState: true,
                onSuccess: () => {
                    showLoading.value = false
                    showNotificationModal('success', 'Success', 'Department deleted successfully!')
                },
                onError: (errors) => {
                    showLoading.value = false
                    console.error('Delete failed:', errors)

                    // Handle specific error messages
                    const errorMessage = errors.delete || 'Failed to delete department. Please try again.'
                    showNotificationModal('error', 'Error', errorMessage)
                }
            })
        }
    )
}

// Handle confirmation
const handleConfirmation = () => {
    showConfirmation.value = false
    if (confirmation.value.action) {
        confirmation.value.action()
    }
}

// Close modals
const closeNotification = () => {
    showNotification.value = false
}

const closeConfirmation = () => {
    showConfirmation.value = false
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Organizational Management', href: route('admin.departments.index') },
    { name: 'Departments', href: route('admin.departments.index') }
]

// Render department tree recursively
const renderDepartmentTree = (department: any, level = 0) => {
    const indentClass = level > 0 ? `pl-${level * 4}` : '';
    return {
        department,
        indentClass,
        level
    };
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-4">
                <h1 class="text-xl sm:text-2xl font-bold">Department Management</h1>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Link
                        :href="route('admin.departments.create')"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center"
                    >
                        Add New Department
                    </Link>
                    <Link
                        :href="route('admin.manager-roles.create')"
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition w-full sm:w-auto text-center"
                    >
                        Assign Manager Roles
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Departments</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.total_departments || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Departments</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.active_departments || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">With Managers</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.departments_with_managers || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Departments Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Department
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Hierarchy
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Managers
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Users
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
                        <template v-for="department in departments" :key="department.id">
                            <!-- Main Department Row -->
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-blue-600">
                                                    {{ department.department_code }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ department.name }}</div>
                                            <div class="text-sm text-gray-500">{{ department.description }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ department.hierarchy_path }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col space-y-1">
                                        <div v-for="manager in department.managers" :key="manager.name" class="text-sm">
                                            <span class="font-medium text-gray-900">{{ manager.name }}</span>
                                            <span class="text-gray-500">({{ manager.role_type }})</span>
                                            <span v-if="manager.is_primary" class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Primary
                                            </span>
                                        </div>
                                        <div v-if="department.managers.length === 0" class="text-sm text-gray-400">
                                            No managers assigned
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ department.users_count }} users
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': department.is_active,
                                            'bg-red-100 text-red-800': !department.is_active
                                        }"
                                    >
                                        {{ department.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <Link
                                            :href="route('admin.departments.show', department.id)"
                                            class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-600 hover:bg-blue-200 rounded text-xs font-medium transition-colors"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('admin.departments.edit', department.id)"
                                            class="inline-flex items-center px-2 py-1 bg-indigo-100 text-indigo-600 hover:bg-indigo-200 rounded text-xs font-medium transition-colors"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="deleteDepartment(department.id, department.name)"
                                            class="inline-flex items-center px-2 py-1 bg-red-100 text-red-600 hover:bg-red-200 rounded text-xs font-medium transition-colors"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Child Departments -->
                            <tr v-for="child in department.children" :key="child.id" class="bg-gray-50 hover:bg-gray-100 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center pl-8">
                                        <div class="shrink-0 h-8 w-8">
                                            <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                                <span class="text-xs font-medium text-gray-600">
                                                    {{ child.department_code }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-700">{{ child.name }}</div>
                                            <div class="text-xs text-gray-500">{{ child.description }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">{{ child.hierarchy_path }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">{{ child.managers_count }} managers</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    {{ child.users_count }} users
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': child.is_active,
                                            'bg-red-100 text-red-800': !child.is_active
                                        }"
                                    >
                                        {{ child.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <Link
                                            :href="route('admin.departments.show', child.id)"
                                            class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-600 hover:bg-blue-200 rounded text-xs font-medium transition-colors"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('admin.departments.edit', child.id)"
                                            class="inline-flex items-center px-2 py-1 bg-indigo-100 text-indigo-600 hover:bg-indigo-200 rounded text-xs font-medium transition-colors"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="deleteDepartment(child.id, child.name)"
                                            class="inline-flex items-center px-2 py-1 bg-red-100 text-red-600 hover:bg-red-200 rounded text-xs font-medium transition-colors"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div v-if="!departments || departments.length === 0" class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No departments found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new department.</p>
                        <div class="mt-6">
                            <Link
                                :href="route('admin.departments.create')"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                            >
                                Add New Department
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Components -->
        <NotificationModal
            :show="showNotification"
            :type="notification.type"
            :title="notification.title"
            :message="notification.message"
            :auto-close="notification.type === 'success'"
            :duration="4000"
            @close="closeNotification"
        />

        <ConfirmationModal
            :show="showConfirmation"
            :title="confirmation.title"
            :message="confirmation.message"
            confirm-text="Yes, Delete"
            cancel-text="Cancel"
            type="danger"
            @confirm="handleConfirmation"
            @cancel="closeConfirmation"
        />

        <LoadingModal
            :show="showLoading"
            :message="loading.message"
        />
    </AdminLayout>
</template>
