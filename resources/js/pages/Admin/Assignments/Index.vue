<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'

const props = defineProps({
    assignments: Object
})

const formatDate = (dateString) => {
    if (!dateString) return '—'
    return new Date(dateString).toLocaleDateString()
}

const getStatusColor = (status) => {
    const colors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'accepted': 'bg-blue-100 text-blue-800',
        'declined': 'bg-red-100 text-red-800',
        'completed': 'bg-green-100 text-green-800'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
    <AdminLayout>
        <div class="px-4 sm:px-0">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="assignment in assignments.data" :key="assignment.id">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ assignment.course.name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ assignment.user.name }}</div>
                            <div class="text-sm text-gray-500">{{ assignment.user.email }}</div>
                        </td>
                        <td class="px-6 py-4">
                <span
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="getStatusColor(assignment.status)"
                >
                  {{ assignment.status.charAt(0).toUpperCase() + assignment.status.slice(1) }}
                </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ formatDate(assignment.assigned_at) }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                        </td>
                    </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="assignments.links" class="px-4 py-3 border-t">
                    <nav class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing {{ assignments.from }} to {{ assignments.to }} of {{ assignments.total }} assignments
                        </div>
                        <div class="flex space-x-1">
                            <Link
                                v-for="link in assignments.links"
                                :key="link.label"
                                :href="link.url"
                                v-html="link.label"
                                class="px-3 py-2 text-sm rounded border"
                                :class="{
                  'bg-blue-500 text-white': link.active,
                  'bg-white text-gray-700 hover:bg-gray-50': !link.active && link.url,
                  'bg-gray-100 text-gray-400 cursor-not-allowed': !link.url
                }"
                            />
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
