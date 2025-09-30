<!--
  User Level Details Page
  Detailed view of a user level with assigned users and management interface
-->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, computed, watch } from 'vue'
import { type BreadcrumbItemType } from '@/types'
import NotificationModal from '@/components/modals/NotificationModal.vue'
import ConfirmationModal from '@/components/modals/ConfirmationModal.vue'
import LoadingModal from '@/components/modals/LoadingModal.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Checkbox } from '@/components/ui/checkbox'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    Edit,
    UserPlus,
    Users,
    UserCheck,
    Building2,
    Crown,
    Search,
    X,
    Eye,
    Trash2,
    ChevronLeft,
    ChevronRight
} from 'lucide-vue-next'

const props = defineProps({
    userLevel: Object,
    users: Array,
    availableUsers: Array, // Available users from controller
})

// Modal states
const showNotification = ref(false)
const showConfirmation = ref(false)
const showLoading = ref(false)
const showAssignUserModal = ref(false)

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

const availableUsers = ref(props.availableUsers)
const selectedUsers = ref([])

// Enhanced filtering and search
const searchQuery = ref('')
const selectedDepartment = ref('')
const selectedStatus = ref('')
const currentPage = ref(1)
const usersPerPage = 10

// Helper functions
const showNotificationModal = (type: string, title: string, message: string) => {
    notification.value = { type, title, message }
    showNotification.value = true
}

const showConfirmationModal = (title: string, message: string, action: () => void) => {
    confirmation.value = { title, message, action }
    showConfirmation.value = true
}

// Get unique departments for filter
const departments = computed(() => {
    const depts = [...new Set(availableUsers.value.map(user => user.department).filter(Boolean))]
    return depts.sort()
})

// Get unique statuses for filter
const statuses = computed(() => {
    const statuses = [...new Set(availableUsers.value.map(user => user.status).filter(Boolean))]
    return statuses.sort()
})

// Filtered and searched users
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

// Paginated users
const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * usersPerPage
    const end = start + usersPerPage
    return filteredUsers.value.slice(start, end)
})

// Pagination info
const totalPages = computed(() => Math.ceil(filteredUsers.value.length / usersPerPage))
const showingFrom = computed(() => (currentPage.value - 1) * usersPerPage + 1)
const showingTo = computed(() => Math.min(currentPage.value * usersPerPage, filteredUsers.value.length))

// Reset pagination when filters change
watch([searchQuery, selectedDepartment, selectedStatus], () => {
    currentPage.value = 1
})

// Select/Deselect all on current page
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

// Check if all users on current page are selected
const allPageUsersSelected = computed(() => {
    return paginatedUsers.value.length > 0 &&
        paginatedUsers.value.every(user => selectedUsers.value.includes(user.id))
})

// Update selectAllOnPage when selection changes
watch([selectedUsers, paginatedUsers], () => {
    selectAllOnPage.value = allPageUsersSelected.value
}, { deep: true })

// Clear all filters
const clearFilters = () => {
    searchQuery.value = ''
    selectedDepartment.value = ''
    selectedStatus.value = ''
    currentPage.value = 1
}

// Remove user from level with confirmation
const removeUserFromLevel = (userId: number, userName: string) => {
    showConfirmationModal(
        'Remove User from Level',
        `Are you sure you want to remove "${userName}" from the ${props.userLevel.name} level? This will unassign their current level.`,
        () => {
            showLoading.value = true
            loading.value.message = 'Removing user from level...'

            router.post(route('admin.user-levels.remove-user'), {
                user_id: userId
            }, {
                preserveState: true,
                onSuccess: () => {
                    showLoading.value = false
                    showNotificationModal('success', 'Success', 'User removed from level successfully!')
                },
                onError: (errors) => {
                    showLoading.value = false
                    console.error('Remove failed:', errors)
                    const errorMessage = errors.remove_user || 'Failed to remove user from level. Please try again.'
                    showNotificationModal('error', 'Error', errorMessage)
                }
            })
        }
    )
}

// Enhanced loadAvailableUsers function
const loadAvailableUsers = () => {
    availableUsers.value = props.availableUsers
    showAssignUserModal.value = true

    // Reset modal state
    searchQuery.value = ''
    selectedDepartment.value = ''
    selectedStatus.value = ''
    selectedUsers.value = []
    currentPage.value = 1
}

// Close modal and reset state
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
        showNotificationModal('warning', 'No Selection', 'Please select at least one user to assign.')
        return
    }

    showConfirmationModal(
        'Assign Users to Level',
        `Are you sure you want to assign ${selectedUsers.value.length} user(s) to the ${props.userLevel.name} level?`,
        () => {
            showLoading.value = true
            loading.value.message = 'Assigning users to level...'

            router.post(route('admin.user-levels.bulk-assign'), {
                user_ids: selectedUsers.value,
                user_level_id: props.userLevel.id
            }, {
                preserveState: true,
                onSuccess: () => {
                    showLoading.value = false
                    showNotificationModal('success', 'Success', 'Users assigned to level successfully!')
                    closeModal()
                },
                onError: (errors) => {
                    showLoading.value = false
                    console.error('Assign failed:', errors)
                    const errorMessage = errors.bulk_assign || 'Failed to assign users. Please try again.'
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

// Get level badge color scheme
const getLevelColorScheme = (levelCode: string) => {
    const colors = {
        'L1': { bg: 'bg-blue-100', text: 'text-blue-600', variant: 'secondary' },
        'L2': { bg: 'bg-green-100', text: 'text-green-600', variant: 'secondary' },
        'L3': { bg: 'bg-orange-100', text: 'text-orange-600', variant: 'outline' },
        'L4': { bg: 'bg-red-100', text: 'text-red-600', variant: 'destructive' },
    }
    return colors[levelCode] || { bg: 'bg-gray-100', text: 'text-gray-600', variant: 'secondary' }
}

// Get user status variant
const getUserStatusVariant = (status: string) => {
    switch (status) {
        case 'active': return 'default'
        case 'inactive': return 'destructive'
        case 'on_leave': return 'secondary'
        default: return 'outline'
    }
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
        <div class="px-4 sm:px-0 space-y-6">
            <!-- User Level Header -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-center space-x-4">
                            <Avatar class="h-16 w-16">
                                <AvatarFallback
                                    :class="[getLevelColorScheme(userLevel.code).bg, getLevelColorScheme(userLevel.code).text]"
                                    class="text-xl font-bold"
                                >
                                    {{ userLevel.code }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <h1 class="text-2xl font-bold text-foreground">{{ userLevel.name }}</h1>
                                <p class="text-sm text-muted-foreground">{{ userLevel.description }}</p>
                                <div class="mt-2 flex items-center space-x-4 flex-wrap gap-2">
                                    <Badge variant="outline">Code: {{ userLevel.code }}</Badge>
                                    <Badge variant="outline">Level {{ userLevel.hierarchy_level }}</Badge>
                                    <Badge v-if="userLevel.is_management_level" variant="secondary">
                                        <Crown class="mr-1 h-3 w-3" />
                                        Management Level
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                            <Button :as="Link" :href="route('admin.user-levels.edit', userLevel.id)" variant="outline">
                                <Edit class="mr-2 h-4 w-4" />
                                Edit Level
                            </Button>
                            <Button @click="loadAvailableUsers">
                                <UserPlus class="mr-2 h-4 w-4" />
                                Assign Users
                            </Button>
                        </div>
                    </div>

                    <!-- Management Permissions -->
                    <div v-if="userLevel.manageable_levels && userLevel.manageable_levels.length > 0" class="border-t pt-4 mt-4">
                        <div class="text-sm font-medium text-foreground mb-2">Can Manage:</div>
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="level in userLevel.manageable_levels" :key="level.code" variant="outline">
                                {{ level.code }} - {{ level.name }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                                    <Users class="h-5 w-5 text-primary-foreground" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Total Users</div>
                                <div class="text-2xl font-bold text-foreground">{{ users.length }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <UserCheck class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Active Users</div>
                                <div class="text-2xl font-bold text-foreground">{{ users.filter(u => u.status === 'active').length }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <Building2 class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">With Departments</div>
                                <div class="text-2xl font-bold text-foreground">{{ users.filter(u => u.department).length }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                    <Crown class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Manager Roles</div>
                                <div class="text-2xl font-bold text-foreground">{{ users.filter(u => u.manager_roles_count > 0).length }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Users Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Users class="mr-2 h-5 w-5" />
                        Users with {{ userLevel.name }} Level
                    </CardTitle>
                    <CardDescription>Manage users assigned to this organizational level</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Department</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Joined</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in users" :key="user.id" class="hover:bg-muted/50">
                                    <TableCell>
                                        <div class="flex items-center space-x-3">
                                            <Avatar>
                                                <AvatarFallback class="bg-muted text-muted-foreground">
                                                    {{ user.name.charAt(0).toUpperCase() }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium text-foreground">{{ user.name }}</div>
                                                <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                                                <div v-if="user.employee_code" class="text-xs text-muted-foreground">ID: {{ user.employee_code }}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="user.department" class="text-foreground">{{ user.department }}</div>
                                        <div v-else class="text-muted-foreground italic">No department assigned</div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getUserStatusVariant(user.status)">
                                            {{ user.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">
                                        {{ user.created_at || 'N/A' }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <Button
                                                :as="Link"
                                                :href="route('admin.users.organizational', user.id)"
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                @click="removeUserFromLevel(user.id, user.name)"
                                                variant="ghost"
                                                size="sm"
                                                class="text-destructive hover:text-destructive hover:bg-destructive/10"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Empty state -->
                        <div v-if="!users || users.length === 0" class="text-center py-12">
                            <Users class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 class="text-lg font-medium text-foreground mb-2">No users assigned</h3>
                            <p class="text-sm text-muted-foreground mb-6">Start by assigning users to this level.</p>
                            <Button @click="loadAvailableUsers">
                                <UserPlus class="mr-2 h-4 w-4" />
                                Assign Users
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Enhanced Assign Users Modal -->
        <Dialog v-model:open="showAssignUserModal">
            <DialogContent class="max-w-4xl max-h-[90vh] overflow-hidden">
                <DialogHeader>
                    <DialogTitle class="flex items-center">
                        <UserPlus class="mr-2 h-5 w-5" />
                        Assign Users to {{ userLevel.name }} Level
                    </DialogTitle>
                    <DialogDescription>
                        Select users to assign to this organizational level
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <!-- Search and Filters -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Search Input -->
                        <div class="flex-1 relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                placeholder="Search by name, email, or employee ID..."
                                class="pl-10"
                            />
                        </div>

                        <!-- Department Filter -->
                        <Select :model-value="selectedDepartment" @update:model-value="(value) => selectedDepartment = value">
                            <SelectTrigger class="w-48">
                                <SelectValue placeholder="All Departments" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Departments</SelectItem>
                                <SelectItem v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Status Filter -->
                        <Select :model-value="selectedStatus" @update:model-value="(value) => selectedStatus = value">
                            <SelectTrigger class="w-32">
                                <SelectValue placeholder="All Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Status</SelectItem>
                                <SelectItem v-for="status in statuses" :key="status" :value="status">{{ status }}</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Clear Filters -->
                        <Button @click="clearFilters" variant="outline">
                            <X class="mr-2 h-4 w-4" />
                            Clear
                        </Button>
                    </div>

                    <!-- Results Summary -->
                    <div class="flex items-center justify-between text-sm text-muted-foreground">
                        <div>
                            Showing {{ showingFrom }} - {{ showingTo }} of {{ filteredUsers.length }} users
                            <span v-if="selectedUsers.length > 0" class="ml-2 text-primary font-medium">
                                ({{ selectedUsers.length }} selected)
                            </span>
                        </div>
                        <div v-if="filteredUsers.length > usersPerPage" class="text-xs">
                            Page {{ currentPage }} of {{ totalPages }}
                        </div>
                    </div>

                    <!-- Users List with Pagination -->
                    <Card class="h-96">
                        <!-- Select All Header -->
                        <div class="p-4 border-b flex items-center bg-muted/50">
                            <Checkbox
                                v-model:checked="selectAllOnPage"
                                @update:checked="toggleSelectAllOnPage"
                            />
                            <span class="ml-3 text-sm font-medium">
                                Select all on this page ({{ paginatedUsers.length }} users)
                            </span>
                        </div>

                        <!-- Users List -->
                        <div class="flex-1 overflow-y-auto">
                            <div v-for="user in paginatedUsers" :key="user.id" class="p-4 border-b hover:bg-muted/50">
                                <div class="flex items-center space-x-3">
                                    <Checkbox
                                        v-model:checked="selectedUsers"
                                        :value="user.id"
                                        @update:checked="(checked) => {
                                            if (checked) {
                                                if (!selectedUsers.includes(user.id)) selectedUsers.push(user.id)
                                            } else {
                                                const index = selectedUsers.indexOf(user.id)
                                                if (index > -1) selectedUsers.splice(index, 1)
                                            }
                                        }"
                                    />
                                    <Avatar class="h-8 w-8">
                                        <AvatarFallback class="bg-muted text-muted-foreground text-xs">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex-1">
                                        <div class="font-medium text-foreground">{{ user.name }}</div>
                                        <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                                        <div class="flex items-center space-x-4 mt-1">
                                            <span v-if="user.employee_code" class="text-xs text-muted-foreground">ID: {{ user.employee_code }}</span>
                                            <span v-if="user.department" class="text-xs text-muted-foreground">{{ user.department }}</span>
                                            <Badge :variant="getUserStatusVariant(user.status)" class="text-xs">
                                                {{ user.status }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- No Results -->
                            <div v-if="filteredUsers.length === 0" class="p-8 text-center text-muted-foreground">
                                <Users class="mx-auto h-12 w-12 mb-4" />
                                <h3 class="text-lg font-medium text-foreground mb-2">No users found</h3>
                                <p class="text-sm">Try adjusting your search or filters.</p>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="totalPages > 1" class="p-4 border-t flex items-center justify-between">
                            <Button
                                @click="currentPage = Math.max(1, currentPage - 1)"
                                :disabled="currentPage === 1"
                                variant="outline"
                                size="sm"
                            >
                                <ChevronLeft class="h-4 w-4" />
                                Previous
                            </Button>

                            <div class="flex space-x-1">
                                <Button
                                    v-for="page in Math.min(totalPages, 5)"
                                    :key="page"
                                    @click="currentPage = page"
                                    :variant="currentPage === page ? 'default' : 'outline'"
                                    size="sm"
                                >
                                    {{ page }}
                                </Button>
                                <span v-if="totalPages > 5" class="px-3 py-1 text-sm text-muted-foreground">...</span>
                            </div>

                            <Button
                                @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                variant="outline"
                                size="sm"
                            >
                                Next
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </Card>
                </div>

                <DialogFooter>
                    <Button @click="closeModal" variant="outline">Cancel</Button>
                    <Button
                        @click="assignUsersToLevel"
                        :disabled="selectedUsers.length === 0"
                    >
                        <UserPlus class="mr-2 h-4 w-4" />
                        Assign {{ selectedUsers.length }} User{{ selectedUsers.length !== 1 ? 's' : '' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

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
            confirm-text="Yes, Continue"
            cancel-text="Cancel"
            type="warning"
            @confirm="handleConfirmation"
            @cancel="closeConfirmation"
        />

        <LoadingModal
            :show="showLoading"
            :message="loading.message"
        />
    </AdminLayout>
</template>
