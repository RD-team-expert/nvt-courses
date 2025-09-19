<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { computed } from 'vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    role: Object,
})

// Terminate role
const terminateRole = () => {
    if (!confirm('Are you sure you want to terminate this manager role?')) {
        return;
    }

    router.delete(route('admin.manager-roles.destroy', props.role.id), {
        onSuccess: () => {
            // Will redirect to index page automatically
        },
        onError: (errors) => {
            console.error('Terminate failed:', errors);
            alert('Failed to terminate role. Please try again.');
        }
    });
}

// Get role type color
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

// Get authority level color
const getAuthorityColor = (level: number) => {
    const colors = {
        1: 'bg-red-100 text-red-800',    // High Authority
        2: 'bg-yellow-100 text-yellow-800', // Medium Authority
        3: 'bg-green-100 text-green-800',   // Low Authority
    };
    return colors[level] || 'bg-gray-100 text-gray-800';
}

// ✅ FIXED: Compute breadcrumbs to avoid route generation errors
const breadcrumbs = computed<BreadcrumbItemType[]>(() => {
    if (!props.role?.id) {
        return [
            { name: 'Dashboard', href: route('dashboard') },
            { name: 'Manager Roles', href: route('admin.manager-roles.index') },
            { name: 'Role Details', href: '#' }
        ];
    }

    return [
        { name: 'Dashboard', href: route('dashboard') },
        { name: 'Manager Roles', href: route('admin.manager-roles.index') },
        { name: 'Role Details', href: route('admin.manager-roles.show', props.role.id) }
    ];
});
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold">Manager Role Details</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ role?.role_display || 'Unknown Role' }} assignment for {{ role?.manager?.name || 'Unknown User' }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Link
                        :href="route('admin.manager-roles.edit', role.id)"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center"
                        v-if="role?.id"
                    >
                        Edit Role
                    </Link>
                    <button
                        @click="terminateRole"
                        class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition w-full sm:w-auto"
                        v-if="role?.is_active"
                    >
                        Terminate Role
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" v-if="role">
                <!-- Main Role Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Role Overview Card -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Role Overview</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Manager Info -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-3">Manager</h3>
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0 h-12 w-12">
                                        <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-lg font-medium text-gray-600">
                                                {{ role.manager?.name ? role.manager.name.charAt(0).toUpperCase() : '?' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ role.manager?.name || 'Unknown User' }}</div>
                                        <div class="text-sm text-gray-500">{{ role.manager?.email || 'N/A' }}</div>
                                        <div v-if="role.manager?.level" class="text-xs text-gray-400 mt-1">{{ role.manager.level }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Department Info -->
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-3">Department</h3>
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0 h-12 w-12">
                                        <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ role.department?.name || 'Unknown Department' }}</div>
                                        <div class="text-sm text-gray-500">{{ role.department?.code || 'N/A' }}</div>
                                        <div v-if="role.department?.parent" class="text-xs text-gray-400 mt-1">Parent: {{ role.department.parent }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Role Details Card -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Role Details</h2>

                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-700">Role Type</dt>
                                <dd class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="getRoleTypeColor(role.role_type)">
                                        {{ role.role_display || 'Unknown Role' }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-700">Authority Level</dt>
                                <dd class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="getAuthorityColor(role.authority_level)">
                                        {{ role.authority_display || `Level ${role.authority_level}` }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-700">Primary Role</dt>
                                <dd class="mt-1">
                                    <span v-if="role.is_primary"
                                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        Yes
                                    </span>
                                    <span v-else class="text-sm text-gray-500">No</span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-700">Status</dt>
                                <dd class="mt-1">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': role.is_active,
                                            'bg-red-100 text-red-800': !role.is_active
                                        }"
                                    >
                                        {{ role.is_active ? 'Active' : 'Terminated' }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-700">Start Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ role.start_date || 'N/A' }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-700">End Date</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ role.end_date || 'Permanent' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Managed User Card -->
                    <div v-if="role.managed_user" class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Direct Report</h2>

                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0 h-12 w-12">
                                <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                    <span class="text-lg font-medium text-green-600">
                                        {{ role.managed_user.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ role.managed_user.name }}</div>
                                <div class="text-sm text-gray-500">{{ role.managed_user.email }}</div>
                                <div v-if="role.managed_user.level" class="text-xs text-gray-400 mt-1">{{ role.managed_user.level }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Management Scope</h2>
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span class="text-sm text-gray-600">Department-wide management role</span>
                        </div>
                    </div>

                    <!-- Notes Card -->
                    <div v-if="role.notes" class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Notes</h2>
                        <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ role.notes }}</p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>

                        <div class="space-y-3">
                            <Link
                                :href="route('admin.manager-roles.edit', role.id)"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors w-full"
                                v-if="role.id"
                            >
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit Role
                            </Link>

                            <Link
                                :href="route('admin.manager-roles.index')"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors w-full"
                            >
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                </svg>
                                View All Roles
                            </Link>

                            <Link
                                :href="route('admin.manager-roles.matrix')"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors w-full"
                            >
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Matrix View
                            </Link>

                            <button
                                @click="terminateRole"
                                class="flex items-center px-3 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 transition-colors w-full"
                                v-if="role.is_active"
                            >
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Terminate Role
                            </button>
                        </div>
                    </div>

                    <!-- Role Metadata -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Role Information</h2>

                        <dl class="space-y-3">
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created By</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ role.created_by || 'Unknown' }}</dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created At</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ role.created_at || 'N/A' }}</dd>
                            </div>

                            <div v-if="role.start_date">
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Duration</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    {{ role.end_date ? `${role.start_date} to ${role.end_date}` : `Since ${role.start_date}` }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Loading/Error State -->
            <div v-else class="text-center py-12">
                <div class="text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.316 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Role not found</h3>
                    <p class="mt-1 text-sm text-gray-500">The requested manager role could not be loaded.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
