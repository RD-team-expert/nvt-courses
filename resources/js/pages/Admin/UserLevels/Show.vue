<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, computed, watch } from 'vue'
import { type BreadcrumbItemType } from '@/types'

const props = defineProps({
    userLevel: Object,
    users: Array,
    availableUsers: Array,  // ✅ Available users from controller
})

const showAssignUserModal = ref(false)
const availableUsers = ref(props.availableUsers || [])
const selectedUsers = ref([])

// ✅ Enhanced filtering and search
const searchQuery = ref('')
const selectedDepartment = ref('')
const selectedStatus = ref('')
const currentPage = ref(1)
const usersPerPage = 10

// ✅ Get unique departments for filter
const departments = computed(() => {
    const depts = [...new Set(availableUsers.value.map(user => user.department).filter(Boolean))]
    return depts.sort()
})

// ✅ Get unique statuses for filter
const statuses = computed(() => {
    const statuses = [...new Set(availableUsers.value.map(user => user.status).filter(Boolean))]
    return statuses.sort()
})

// ✅ Filtered and searched users
const filteredUsers = computed(() => {
    let filtered = availableUsers.value

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(user =>
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query) ||
            (user.employee_code && user.employee_code.toLowerCase().includes(query))
        )
    }

    // Department filter
    if (selectedDepartment.value) {
        filtered = filtered.filter(user => user.department === selectedDepartment.value)
    }

    // Status filter
    if (selectedStatus.value) {
        filtered = filtered.filter(user => user.status === selectedStatus.value)
    }

    return filtered
})

// ✅ Paginated users
const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * usersPerPage
    const end = start + usersPerPage
    return filteredUsers.value.slice(start, end)
})

// ✅ Pagination info
const totalPages = computed(() => Math.ceil(filteredUsers.value.length / usersPerPage))
const showingFrom = computed(() => (currentPage.value - 1) * usersPerPage + 1)
const showingTo = computed(() => Math.min(currentPage.value * usersPerPage, filteredUsers.value.length))

// ✅ Reset pagination when filters change
watch([searchQuery, selectedDepartment, selectedStatus], () => {
    currentPage.value = 1
})

// ✅ Select/Deselect all on current page
const selectAllOnPage = ref(false)
const toggleSelectAllOnPage = () => {
    if (selectAllOnPage.value) {
        // Add all users on current page to selection
        paginatedUsers.value.forEach(user => {
            if (!selectedUsers.value.includes(user.id)) {
                selectedUsers.value.push(user.id)
            }
        })
    } else {
        // Remove all users on current page from selection
        const pageUserIds = paginatedUsers.value.map(user => user.id)
        selectedUsers.value = selectedUsers.value.filter(id => !pageUserIds.includes(id))
    }
}

// ✅ Check if all users on current page are selected
const allPageUsersSelected = computed(() => {
    return paginatedUsers.value.length > 0 &&
        paginatedUsers.value.every(user => selectedUsers.value.includes(user.id))
})

// ✅ Update selectAllOnPage when selection changes
watch([selectedUsers, paginatedUsers], () => {
    selectAllOnPage.value = allPageUsersSelected.value
}, { deep: true })

// ✅ Clear all filters
const clearFilters = () => {
    searchQuery.value = ''
    selectedDepartment.value = ''
    selectedStatus.value = ''
    currentPage.value = 1
}

// Remove user from level
const removeUserFromLevel = (userId: number) => {
    if (!confirm('Are you sure you want to remove this user from the level?')) {
        return;
    }

    router.post(route('admin.users.assign-level', userId), {
        user_level_id: null
    }, {
        preserveState: true,
        onSuccess: () => {
            alert('User removed from level successfully!');
        },
        onError: (errors) => {
            console.error('Remove failed:', errors);
            alert('Failed to remove user from level. Please try again.');
        }
    });
}

// ✅ Enhanced loadAvailableUsers function
const loadAvailableUsers = () => {
    availableUsers.value = props.availableUsers || []
    showAssignUserModal.value = true
    // Reset modal state
    searchQuery.value = ''
    selectedDepartment.value = ''
    selectedStatus.value = ''
    selectedUsers.value = []
    currentPage.value = 1
}

// ✅ Close modal and reset state
const closeModal = () => {
    showAssignUserModal.value = false
    selectedUsers.value = []
    searchQuery.value = ''
    selectedDepartment.value = ''
    selectedStatus.value = ''
    currentPage.value = 1
}

// Assign selected users to this level
const assignUsersToLevel = () => {
    if (selectedUsers.value.length === 0) {
        alert('Please select at least one user')
        return
    }

    router.post(route('admin.user-levels.bulk-assign'), {
        user_ids: selectedUsers.value,
        user_level_id: props.userLevel.id
    }, {
        preserveState: true,
        onSuccess: () => {
            alert('Users assigned to level successfully!')
            closeModal()
        },
        onError: (errors) => {
            console.error('Assign failed:', errors)
            alert('Failed to assign users. Please try again.')
        }
    })
}

// Get level badge color
const getLevelColor = (levelCode: string) => {
    const colors = {
        'L1': 'bg-blue-100 text-blue-800',
        'L2': 'bg-green-100 text-green-800',
        'L3': 'bg-orange-100 text-orange-800',
        'L4': 'bg-red-100 text-red-800',
    };
    return colors[levelCode] || 'bg-gray-100 text-gray-800';
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'User Levels', href: route('admin.user-levels.index') },
    { name: props.userLevel.name, href: route('admin.user-levels.show', props.userLevel.id) }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <!-- Keep all your existing template content exactly the same until the modal -->
        <div class="px-4 sm:px-0">
            <!-- User Level Header -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0 h-16 w-16">
                            <div class="h-16 w-16 rounded-full flex items-center justify-center"
                                 :class="getLevelColor(userLevel.code)">
                                <span class="text-xl font-bold">{{ userLevel.code }}</span>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ userLevel.name }}</h1>
                            <p class="text-sm text-gray-600">{{ userLevel.description }}</p>
                            <div class="mt-2 flex items-center space-x-4">
                                <span class="text-sm text-gray-500">
                                    <strong>Code:</strong> {{ userLevel.code }}
                                </span>
                                <span class="text-sm text-gray-500">
                                    <strong>Hierarchy:</strong> Level {{ userLevel.hierarchy_level }}
                                </span>
                                <span v-if="userLevel.is_management_level" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    Management Level
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <Link
                            :href="route('admin.user-levels.edit', userLevel.id)"
                            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition w-full sm:w-auto text-center"
                        >
                            Edit Level
                        </Link>
                        <button
                            @click="loadAvailableUsers"
                            class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition w-full sm:w-auto"
                        >
                            Assign Users
                        </button>
                    </div>
                </div>

                <!-- Management Permissions -->
                <div v-if="userLevel.manageable_levels && userLevel.manageable_levels.length > 0" class="border-t pt-4">
                    <div class="text-sm text-gray-600 mb-2">
                        <strong>Can Manage:</strong>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="level in userLevel.manageable_levels" :key="level.code"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                            {{ level.code }} - {{ level.name }}
                        </span>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ users.filter(u => u.status === 'active').length }}</dd>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">With Departments</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ users.filter(u => u.department).length }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Manager Roles</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ users.filter(u => u.manager_roles_count > 0).length }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Users with {{ userLevel.name }} Level</h2>
                </div>
                <div class="overflow-hidden overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Department
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Joined
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in users" :key="user.id">
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
                                <div v-if="user.department" class="text-sm text-gray-900">
                                    {{ user.department }}
                                </div>
                                <div v-else class="text-sm text-gray-400 italic">
                                    No department assigned
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ user.created_at || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <Link
                                    :href="route('admin.users.organizational', user.id)"
                                    class="text-blue-600 hover:text-blue-900 mr-3 transition-colors"
                                >
                                    View
                                </Link>
                                <button
                                    @click="removeUserFromLevel(user.id)"
                                    class="text-red-600 hover:text-red-900 transition-colors"
                                >
                                    Remove
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <!-- Empty state -->
                    <div v-if="!users || users.length === 0" class="text-center py-12">
                        <div class="text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 515 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No users assigned</h3>
                            <p class="mt-1 text-sm text-gray-500">Start by assigning users to this level.</p>
                            <div class="mt-6">
                                <button
                                    @click="loadAvailableUsers"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    Assign Users
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ ENHANCED Assign Users Modal -->
        <div v-if="showAssignUserModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

                <!-- ✅ Larger modal for better user experience -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <!-- ✅ Modal Header -->
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Assign Users to {{ userLevel.name }} Level
                            </h3>
                            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- ✅ Search and Filters -->
                        <div class="mb-4 space-y-4">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <!-- Search Input -->
                                <div class="flex-1">
                                    <div class="relative">
                                        <input
                                            v-model="searchQuery"
                                            type="text"
                                            placeholder="Search by name, email, or employee ID..."
                                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Department Filter -->
                                <div class="sm:w-48">
                                    <select
                                        v-model="selectedDepartment"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">All Departments</option>
                                        <option v-for="dept in departments" :key="dept" :value="dept">
                                            {{ dept }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Status Filter -->
                                <div class="sm:w-32">
                                    <select
                                        v-model="selectedStatus"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">All Status</option>
                                        <option v-for="status in statuses" :key="status" :value="status">
                                            {{ status }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Clear Filters -->
                                <button
                                    @click="clearFilters"
                                    class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors"
                                >
                                    Clear
                                </button>
                            </div>

                            <!-- ✅ Results Summary -->
                            <div class="flex items-center justify-between text-sm text-gray-600">
                                <div>
                                    Showing {{ showingFrom }}-{{ showingTo }} of {{ filteredUsers.length }} users
                                    <span v-if="selectedUsers.length > 0" class="ml-2 text-blue-600 font-medium">
                                        ({{ selectedUsers.length }} selected)
                                    </span>
                                </div>
                                <div v-if="filteredUsers.length > usersPerPage" class="text-xs">
                                    Page {{ currentPage }} of {{ totalPages }}
                                </div>
                            </div>
                        </div>

                        <!-- ✅ Users List with Pagination -->
                        <div class="border rounded-lg">
                            <!-- Select All Header -->
                            <div class="px-4 py-3 bg-gray-50 border-b flex items-center">
                                <input
                                    v-model="selectAllOnPage"
                                    @change="toggleSelectAllOnPage"
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label class="ml-3 text-sm font-medium text-gray-700">
                                    Select all on this page ({{ paginatedUsers.length }} users)
                                </label>
                            </div>

                            <!-- Users List -->
                            <div class="max-h-96 overflow-y-auto">
                                <div v-for="user in paginatedUsers" :key="user.id" class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50">
                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="selectedUsers"
                                            :value="user.id"
                                            :id="`user_${user.id}`"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        />
                                        <label :for="`user_${user.id}`" class="ml-3 flex items-center flex-1 cursor-pointer">
                                            <div class="flex-shrink-0 h-8 w-8">
                                                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-gray-600">
                                                        {{ user.name.charAt(0).toUpperCase() }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                                <div class="text-sm text-gray-500">{{ user.email }}</div>
                                                <div class="flex items-center space-x-4 mt-1">
                                                    <span v-if="user.employee_code" class="text-xs text-gray-400">
                                                        ID: {{ user.employee_code }}
                                                    </span>
                                                    <span v-if="user.department" class="text-xs text-gray-400">
                                                        {{ user.department }}
                                                    </span>
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                                          :class="{
                                                              'bg-green-100 text-green-700': user.status === 'active',
                                                              'bg-red-100 text-red-700': user.status === 'inactive',
                                                              'bg-yellow-100 text-yellow-700': user.status === 'on_leave'
                                                          }">
                                                        {{ user.status }}
                                                    </span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- ✅ No Results -->
                            <div v-if="filteredUsers.length === 0" class="px-4 py-8 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 515 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                                <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filters.</p>
                            </div>
                        </div>

                        <!-- ✅ Pagination -->
                        <div v-if="totalPages > 1" class="mt-4 flex items-center justify-between">
                            <button
                                @click="currentPage = Math.max(1, currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="px-3 py-1 text-sm border rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                            >
                                Previous
                            </button>

                            <div class="flex space-x-1">
                                <button
                                    v-for="page in Math.min(totalPages, 5)"
                                    :key="page"
                                    @click="currentPage = page"
                                    :class="{
                                        'bg-blue-500 text-white': currentPage === page,
                                        'text-gray-700 hover:bg-gray-100': currentPage !== page
                                    }"
                                    class="px-3 py-1 text-sm border rounded-md"
                                >
                                    {{ page }}
                                </button>
                                <span v-if="totalPages > 5" class="px-3 py-1 text-sm text-gray-500">...</span>
                            </div>

                            <button
                                @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="px-3 py-1 text-sm border rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                            >
                                Next
                            </button>
                        </div>
                    </div>

                    <!-- ✅ Modal Footer -->
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="assignUsersToLevel"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm"
                            :disabled="selectedUsers.length === 0"
                            :class="{ 'opacity-50 cursor-not-allowed': selectedUsers.length === 0 }"
                        >
                            Assign {{ selectedUsers.length }} User{{ selectedUsers.length !== 1 ? 's' : '' }}
                        </button>
                        <button
                            @click="closeModal"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
