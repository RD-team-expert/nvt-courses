<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    users: Array,
    departments: Array,
    userLevels: Array,
    stats: Object,
})

const selectedUsers = ref([])
const selectedDepartment = ref('')
const selectedLevel = ref('')
const searchQuery = ref('')
const filterDepartment = ref('')
const filterLevel = ref('')
const currentPage = ref(1)
const usersPerPage = 20

// ✅ Filtered users
const filteredUsers = computed(() => {
    let filtered = props.users

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(user =>
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query) ||
            (user.employee_code && user.employee_code.toLowerCase().includes(query))
        )
    }

    if (filterDepartment.value) {
        filtered = filtered.filter(user => user.department === filterDepartment.value)
    }

    if (filterLevel.value) {
        filtered = filtered.filter(user => user.level_code === filterLevel.value)
    }

    return filtered
})

// ✅ Paginated users
const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * usersPerPage
    const end = start + usersPerPage
    return filteredUsers.value.slice(start, end)
})

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / usersPerPage))

// ✅ Bulk assignment
const bulkAssignUsers = () => {
    if (selectedUsers.value.length === 0) {
        alert('Please select at least one user')
        return
    }

    if (!selectedDepartment.value && !selectedLevel.value) {
        alert('Please select a department or level to assign')
        return
    }

    const data = {
        user_ids: selectedUsers.value
    }

    if (selectedDepartment.value) {
        data.department_id = selectedDepartment.value
    }

    if (selectedLevel.value) {
        data.user_level_id = selectedLevel.value
    }

    router.post(route('admin.users.bulk-assign'), data, {
        preserveState: true,
        onSuccess: () => {
            selectedUsers.value = []
            selectedDepartment.value = ''
            selectedLevel.value = ''
        },
        onError: (errors) => {
            console.error('Assignment failed:', errors)
            alert('Failed to assign users. Please try again.')
        }
    })
}

// ✅ Individual user assignment
const assignUserDepartment = (userId, departmentId) => {
    router.post(route('admin.users.assign-department', userId), {
        department_id: departmentId
    }, {
        preserveState: true
    })
}

const assignUserLevel = (userId, levelId) => {
    router.post(route('admin.users.assign-level', userId), {
        user_level_id: levelId
    }, {
        preserveState: true
    })
}

// ✅ Select all functionality
const selectAllFiltered = ref(false)
const toggleSelectAll = () => {
    if (selectAllFiltered.value) {
        selectedUsers.value = filteredUsers.value.map(user => user.id)
    } else {
        selectedUsers.value = []
    }
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'User Management', href: '#' },
    { name: 'User Assignment', href: route('admin.users.assignment') }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h1 class="text-xl sm:text-2xl font-bold">User Assignment</h1>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 515 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.total_users }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">With Departments</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.with_departments }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">With Levels</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.with_levels }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.728-.833-2.498 0L4.316 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Unassigned</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.without_assignments }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Assignment Panel -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Bulk Assignment</h2>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select
                            v-model="selectedDepartment"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Select Department</option>
                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                {{ dept.name }} ({{ dept.department_code }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">User Level</label>
                        <select
                            v-model="selectedLevel"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">Select Level</option>
                            <option v-for="level in userLevels" :key="level.id" :value="level.id">
                                {{ level.code }} - {{ level.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            @click="bulkAssignUsers"
                            :disabled="selectedUsers.length === 0 || (!selectedDepartment && !selectedLevel)"
                            class="w-full bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            Assign {{ selectedUsers.length }} User(s)
                        </button>
                    </div>

                    <div class="flex items-center text-sm text-gray-600">
                        {{ selectedUsers.length }} of {{ filteredUsers.length }} users selected
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Name, email, or employee ID..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Department</label>
                        <select
                            v-model="filterDepartment"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">All Departments</option>
                            <option v-for="dept in departments" :key="dept.name" :value="dept.name">
                                {{ dept.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Level</label>
                        <select
                            v-model="filterLevel"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">All Levels</option>
                            <option v-for="level in userLevels" :key="level.code" :value="level.code">
                                {{ level.code }} - {{ level.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button
                            @click="searchQuery = ''; filterDepartment = ''; filterLevel = ''"
                            class="w-full bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-medium text-gray-900">Users</h2>
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input
                                v-model="selectAllFiltered"
                                @change="toggleSelectAll"
                                type="checkbox"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <span class="ml-2 text-sm text-gray-700">Select all {{ filteredUsers.length }} users</span>
                        </label>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input
                                    type="checkbox"
                                    :checked="paginatedUsers.every(u => selectedUsers.includes(u.id))"
                                    @change="$event.target.checked ? selectedUsers.push(...paginatedUsers.map(u => u.id).filter(id => !selectedUsers.includes(id))) : selectedUsers = selectedUsers.filter(id => !paginatedUsers.map(u => u.id).includes(id))"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Level
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Department
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
                        <tr v-for="user in paginatedUsers" :key="user.id" class="hover:bg-gray-50">
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
                                    <div class="flex-shrink-0 h-10 w-10">
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
                                <span v-if="user.level" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ user.level }}
                                </span>
                                <span v-else class="text-sm text-gray-400 italic">No level</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="user.department" class="text-sm text-gray-900">{{ user.department }}</span>
                                <span v-else class="text-sm text-gray-400 italic">No department</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': user.status === 'active',
                                        'bg-red-100 text-red-800': user.status === 'inactive'
                                    }"
                                >
                                    {{ user.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <Link
                                    :href="route('admin.users.organizational', user.id)"
                                    class="text-blue-600 hover:text-blue-900 transition-colors"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="totalPages > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200">
                        <div class="flex justify-between flex-1 sm:hidden">
                            <button
                                @click="currentPage = Math.max(1, currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                            >
                                Previous
                            </button>
                            <button
                                @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                            >
                                Next
                            </button>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing {{ ((currentPage - 1) * usersPerPage) + 1 }} to {{ Math.min(currentPage * usersPerPage, filteredUsers.length) }} of {{ filteredUsers.length }} results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <button
                                        @click="currentPage = Math.max(1, currentPage - 1)"
                                        :disabled="currentPage === 1"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                                    >
                                        Previous
                                    </button>
                                    <button
                                        v-for="page in Math.min(totalPages, 5)"
                                        :key="page"
                                        @click="currentPage = page"
                                        :class="{
                                            'bg-blue-50 border-blue-500 text-blue-600': currentPage === page,
                                            'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': currentPage !== page
                                        }"
                                        class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                    >
                                        {{ page }}
                                    </button>
                                    <button
                                        @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                        :disabled="currentPage === totalPages"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                                    >
                                        Next
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
