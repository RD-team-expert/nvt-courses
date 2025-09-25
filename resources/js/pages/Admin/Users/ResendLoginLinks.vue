<script setup>
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    users: Object,
    courses: Array,
    filters: Object
})

const selectedUsers = ref([])
const showBulkModal = ref(false)

// Search form
const searchForm = useForm({
    search: props.filters.search || ''
})

// Individual resend form
const resendForm = useForm({
    course_id: ''
})

// Bulk resend form
const bulkForm = useForm({
    user_ids: [],
    course_id: ''
})

// Computed properties
const hasSelectedUsers = computed(() => selectedUsers.value.length > 0)
const allSelected = computed(() =>
    selectedUsers.value.length === props.users.data.length && props.users.data.length > 0
)

// Methods
function search() {
    searchForm.get(route('admin.resend-login-links.index'), {
        preserveState: true,
        replace: true
    })
}

function resendToUser(user, courseId) {
    resendForm.course_id = courseId
    resendForm.post(route('admin.resend-login-links.resend', user.id), {
        preserveScroll: true,
        onSuccess: () => {
            resendForm.reset()
        }
    })
}

function toggleAllUsers() {
    if (allSelected.value) {
        selectedUsers.value = []
    } else {
        selectedUsers.value = props.users.data.map(user => user.id)
    }
}

function openBulkModal() {
    bulkForm.user_ids = selectedUsers.value
    showBulkModal.value = true
}

function sendBulkEmails() {
    bulkForm.post(route('admin.resend-login-links.bulk'), {
        preserveScroll: true,
        onSuccess: () => {
            selectedUsers.value = []
            showBulkModal.value = false
            bulkForm.reset()
        }
    })
}

function formatLastSeen(date) {
    if (!date) return 'Never'
    return new Date(date).toLocaleDateString()
}
</script>

<template>
    <AdminLayout>
        <div class="px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Resend Login Links</h1>
                <p class="mt-2 text-gray-600">Send new secure login links to users with expired access</p>
            </div>

            <!-- Search and Bulk Actions -->
            <div class="mb-6 bg-white rounded-lg shadow-sm border p-6">
                <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                    <!-- Search -->
                    <div class="flex-1 max-w-md">
                        <form @submit.prevent="search">
                            <div class="relative">
                                <input
                                    v-model="searchForm.search"
                                    type="text"
                                    placeholder="Search users by name or email..."
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                />
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Bulk Actions -->
                    <div class="flex gap-3">
                        <button
                            v-if="hasSelectedUsers"
                            @click="openBulkModal"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 1.05c.39.05.78.05 1.17 0L20 8M3 8v8a2 2 0 002 2h14a2 2 0 002-2V8M3 8l2-5h14l2 5" />
                            </svg>
                            Send to {{ selectedUsers.length }} Users
                        </button>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    @change="toggleAllUsers"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Courses</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <input
                                    v-model="selectedUsers"
                                    :value="user.id"
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-700">{{ user.name.charAt(0) }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        Expired Access
                                    </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ formatLastSeen(user.login_token_expires_at) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ user.course_registrations?.length || 0 }} enrolled
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <select
                                        v-model="resendForm.course_id"
                                        class="text-sm border border-gray-300 rounded px-3 py-1 focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="">Select Course</option>
                                        <option v-for="course in courses" :key="course.id" :value="course.id">
                                            {{ course.name }}
                                        </option>
                                    </select>
                                    <button
                                        @click="resendToUser(user, resendForm.course_id)"
                                        :disabled="!resendForm.course_id || resendForm.processing"
                                        class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                                    >
                                        Send Link
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.links" class="bg-white px-4 py-3 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                        </div>
                        <div class="flex space-x-2">
                            <template v-for="(link, index) in users.links" :key="index">
                                <button
                                    v-if="link.url"
                                    @click="router.get(link.url)"
                                    :class="[
                                        'px-3 py-2 text-sm border rounded',
                                        link.active
                                            ? 'bg-blue-600 text-white border-blue-600'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-2 text-sm text-gray-400 border border-gray-300 rounded bg-gray-100"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Send Modal -->
            <div v-if="showBulkModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <h3 class="text-lg font-semibold mb-4">Send Login Links to Multiple Users</h3>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Course</label>
                        <select
                            v-model="bulkForm.course_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option value="">Choose a course...</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.name }}
                            </option>
                        </select>
                    </div>

                    <p class="text-sm text-gray-600 mb-6">
                        This will send new login links to {{ selectedUsers.length }} selected users.
                    </p>

                    <div class="flex gap-3">
                        <button
                            @click="sendBulkEmails"
                            :disabled="!bulkForm.course_id || bulkForm.processing"
                            class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                        >
                            <span v-if="bulkForm.processing">Sending...</span>
                            <span v-else>Send Login Links</span>
                        </button>
                        <button
                            @click="showBulkModal = false"
                            class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
