<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    userLevels: Array,
    stats: Object,
})

// Delete user level
const deleteUserLevel = (levelId: number) => {
    if (!confirm('Are you sure you want to delete this user level? This action cannot be undone.')) {
        return;
    }

    router.delete(route('admin.user-levels.destroy', levelId), {
        preserveState: true,
        onSuccess: () => {
            alert('User level deleted successfully!');
        },
        onError: (errors) => {
            console.error('Delete failed:', errors);
            alert('Failed to delete user level. Please try again.');
        }
    });
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Organizational Management', href: route('admin.user-levels.index') },
    { name: 'User Levels', href: route('admin.user-levels.index') }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-4">
                <h1 class="text-xl sm:text-2xl font-bold">User Level Management</h1>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Link
                        :href="route('admin.user-levels.create')"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center"
                    >
                        Add New Level
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Levels</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.total_levels || 0 }}</dd>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Management Levels</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.management_levels || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Users Assigned</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.total_users_assigned || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Levels Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Level
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Hierarchy
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Can Manage
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Users Count
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="level in userLevels" :key="level.id">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full flex items-center justify-center"
                                         :class="{
                                             'bg-blue-100 text-blue-600': level.hierarchy_level === 1,
                                             'bg-green-100 text-green-600': level.hierarchy_level === 2,
                                             'bg-orange-100 text-orange-600': level.hierarchy_level === 3,
                                             'bg-red-100 text-red-600': level.hierarchy_level === 4,
                                         }">
                                        <span class="text-sm font-bold">{{ level.code }}</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ level.name }}</div>
                                    <div class="text-sm text-gray-500">{{ level.description }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                Level {{ level.hierarchy_level }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div v-if="level.can_manage_levels && level.can_manage_levels.length > 0" class="flex flex-wrap gap-1">
                                <span v-for="managedLevel in level.can_manage_levels" :key="managedLevel"
                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ managedLevel }}
                                </span>
                            </div>
                            <div v-else class="text-sm text-gray-400">None</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ level.users_count }} users
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <Link
                                :href="route('admin.user-levels.show', level.id)"
                                class="text-blue-600 hover:text-blue-900 mr-3 transition-colors"
                            >
                                View
                            </Link>
                            <Link
                                :href="route('admin.user-levels.edit', level.id)"
                                class="text-indigo-600 hover:text-indigo-900 mr-3 transition-colors"
                            >
                                Edit
                            </Link>
                            <button
                                @click="deleteUserLevel(level.id)"
                                class="text-red-600 hover:text-red-900 transition-colors"
                                :disabled="level.users_count > 0"
                                :class="{ 'opacity-50 cursor-not-allowed': level.users_count > 0 }"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Empty state -->
                <div v-if="!userLevels || userLevels.length === 0" class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No user levels found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating organizational levels (L1, L2, L3, L4).</p>
                        <div class="mt-6">
                            <Link
                                :href="route('admin.user-levels.create')"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Add New Level
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
