<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    user: Object,
    reportingChain: Array,
    chainLength: Number,
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

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Users', href: route('admin.users.index') },
    { name: props.user?.name || 'User', href: '#' },
    { name: 'Reporting Chain', href: route('admin.users.reporting-chain', props.user?.id) }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold">Reporting Chain</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Management hierarchy for {{ user?.name || 'Unknown User' }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Link
                        :href="route('admin.users.organizational', user?.id)"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center"
                        v-if="user?.id"
                    >
                        Back to Profile
                    </Link>
                </div>
            </div>

            <!-- Chain Overview -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">Chain Overview</h2>
                        <p class="text-sm text-gray-600">{{ chainLength }} management levels above this user</p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600">{{ chainLength }}</div>
                        <div class="text-sm text-gray-500">Management Levels</div>
                    </div>
                </div>
            </div>

            <!-- Reporting Chain Visualization -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Management Hierarchy</h2>

                <div class="space-y-6">
                    <!-- Current User (Bottom of Chain) -->
                    <div class="flex items-center">
                        <div class="flex items-center space-x-4 bg-blue-50 rounded-lg p-4 flex-1">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center">
                                    <span class="text-lg font-medium text-blue-600">
                                        {{ user?.name?.charAt(0).toUpperCase() || '?' }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">{{ user?.name || 'Unknown User' }}</div>
                                <div class="text-sm text-gray-500">{{ user?.email || 'No email' }}</div>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span v-if="user?.level"
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ user.level }}
                                    </span>
                                    <span v-if="user?.department" class="text-xs text-gray-400">{{ user.department }}</span>
                                </div>
                            </div>
                            <div class="text-xs text-blue-600 font-medium">YOU ARE HERE</div>
                        </div>
                    </div>

                    <!-- Chain Arrow Up -->
                    <div v-if="reportingChain && reportingChain.length > 0" class="flex justify-center">
                        <div class="flex flex-col items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                            </svg>
                            <span class="text-xs text-gray-500 mt-1">Reports To</span>
                        </div>
                    </div>

                    <!-- Management Chain -->
                    <div v-for="(manager, index) in reportingChain" :key="manager.id" class="space-y-4">
                        <div class="flex items-center">
                            <div class="flex items-center space-x-4 bg-gray-50 rounded-lg p-4 flex-1"
                                 :class="{ 'bg-red-50': index === reportingChain.length - 1 }">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center"
                                         :class="index === reportingChain.length - 1 ? 'bg-red-200' : 'bg-gray-200'">
                                        <span class="text-lg font-medium"
                                              :class="index === reportingChain.length - 1 ? 'text-red-600' : 'text-gray-600'">
                                            {{ manager.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900">{{ manager.name }}</div>
                                    <div class="text-sm text-gray-500">{{ manager.email }}</div>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span v-if="manager.level"
                                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                              :class="getLevelColor(manager.level_hierarchy)">
                                            {{ manager.level }}
                                        </span>
                                        <span v-if="manager.department" class="text-xs text-gray-400">{{ manager.department }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="text-right">
                                        <div class="text-xs text-gray-500">Management Level</div>
                                        <div class="text-sm font-medium text-gray-900">{{ manager.position_level }}</div>
                                    </div>
                                    <div v-if="index === reportingChain.length - 1"
                                         class="text-xs text-red-600 font-medium ml-4">
                                        TOP EXECUTIVE
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chain Arrow Up -->
                        <div v-if="index < reportingChain.length - 1" class="flex justify-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                                </svg>
                                <span class="text-xs text-gray-500 mt-1">Reports To</span>
                            </div>
                        </div>
                    </div>

                    <!-- No Chain Message -->
                    <div v-if="!reportingChain || reportingChain.length === 0" class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No reporting chain found</h3>
                        <p class="mt-1 text-sm text-gray-500">This user doesn't have any managers assigned in the system.</p>
                    </div>
                </div>
            </div>

            <!-- Chain Summary -->
            <div v-if="reportingChain && reportingChain.length > 0" class="mt-6 bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Chain Summary</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ chainLength }}</div>
                        <div class="text-sm text-gray-500">Management Levels</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">
                            {{ reportingChain[reportingChain.length - 1]?.level_hierarchy || 'N/A' }}
                        </div>
                        <div class="text-sm text-gray-500">Top Executive Level</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">
                            {{ new Set(reportingChain.map(m => m.department)).size }}
                        </div>
                        <div class="text-sm text-gray-500">Departments Involved</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
