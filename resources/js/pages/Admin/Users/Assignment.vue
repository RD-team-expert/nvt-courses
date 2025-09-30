<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, computed, watch } from 'vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
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
    Users,
    Building2,
    BarChart3,
    AlertTriangle,
    Search,
    X,
    Eye,
    ChevronLeft,
    ChevronRight,
    UserPlus,
    Tag
} from 'lucide-vue-next'

const props = defineProps({
    users: Array,
    departments: Array,
    userLevels: Array,
    stats: Object,
})

// ✅ Fix: Ensure selectedUsers is always an array
const selectedUsers = ref([])
const selectedDepartment = ref('')
const selectedLevel = ref('')
const searchQuery = ref('')
const filterDepartment = ref('')
const filterLevel = ref('')
const currentPage = ref(1)
const usersPerPage = 20

// Watch selectedUsers to ensure it's always an array
watch(selectedUsers, (newValue) => {
    if (!Array.isArray(newValue)) {
        selectedUsers.value = []
    }
}, { deep: true, immediate: true })

// ✅ Filtered users
const filteredUsers = computed(() => {
    let filtered = props.users || []

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

// ✅ Fix: Proper checkbox handling for individual users
const handleUserSelection = (userId, checked) => {
    if (!Array.isArray(selectedUsers.value)) {
        selectedUsers.value = []
    }

    if (checked) {
        if (!selectedUsers.value.includes(userId)) {
            selectedUsers.value.push(userId)
        }
    } else {
        const index = selectedUsers.value.indexOf(userId)
        if (index > -1) {
            selectedUsers.value.splice(index, 1)
        }
    }
}

// ✅ Fix: Check if user is selected
const isUserSelected = (userId) => {
    return Array.isArray(selectedUsers.value) && selectedUsers.value.includes(userId)
}

// ✅ Fix: Toggle current page selection
const togglePageSelection = (checked) => {
    if (!Array.isArray(selectedUsers.value)) {
        selectedUsers.value = []
    }

    if (checked) {
        const pageUserIds = paginatedUsers.value.map(u => u.id)
        pageUserIds.forEach(id => {
            if (!selectedUsers.value.includes(id)) {
                selectedUsers.value.push(id)
            }
        })
    } else {
        const pageUserIds = paginatedUsers.value.map(u => u.id)
        selectedUsers.value = selectedUsers.value.filter(id => !pageUserIds.includes(id))
    }
}

// ✅ Fix: Check if all current page users are selected
const allPageSelected = computed(() => {
    if (!Array.isArray(selectedUsers.value) || paginatedUsers.value.length === 0) {
        return false
    }
    return paginatedUsers.value.every(u => selectedUsers.value.includes(u.id))
})

// ✅ Fix: Check if some users on page are selected (for indeterminate state)
const somePageSelected = computed(() => {
    if (!Array.isArray(selectedUsers.value) || paginatedUsers.value.length === 0) {
        return false
    }
    return paginatedUsers.value.some(u => selectedUsers.value.includes(u.id)) && !allPageSelected.value
})

// ✅ Select all functionality
const selectAllFiltered = ref(false)
const toggleSelectAll = () => {
    if (!Array.isArray(selectedUsers.value)) {
        selectedUsers.value = []
    }

    if (selectAllFiltered.value) {
        selectedUsers.value = filteredUsers.value.map(user => user.id)
    } else {
        selectedUsers.value = []
    }
}

// ✅ Bulk assignment
const bulkAssignUsers = () => {
    if (!Array.isArray(selectedUsers.value) || selectedUsers.value.length === 0) {
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

// Clear all filters
const clearFilters = () => {
    searchQuery.value = ''
    filterDepartment.value = ''
    filterLevel.value = ''
    currentPage.value = 1
}

// Handle select changes
const handleDepartmentChange = (value) => {
    selectedDepartment.value = value === 'select' ? '' : value
}

const handleLevelChange = (value) => {
    selectedLevel.value = value === 'select' ? '' : value
}

const handleFilterDepartmentChange = (value) => {
    filterDepartment.value = value === 'all' ? '' : value
}

const handleFilterLevelChange = (value) => {
    filterLevel.value = value === 'all' ? '' : value
}

// Get user status variant
const getUserStatusVariant = (status) => {
    switch (status) {
        case 'active': return 'default'
        case 'inactive': return 'destructive'
        default: return 'secondary'
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
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-foreground">User Assignment</h1>
                <p class="text-sm text-muted-foreground mt-1">Assign users to departments and organizational levels with bulk operations</p>
            </div>

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
                                <div class="text-2xl font-bold text-foreground">{{ stats?.total_users || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <Building2 class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">With Departments</div>
                                <div class="text-2xl font-bold text-foreground">{{ stats?.with_departments || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <BarChart3 class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">With Levels</div>
                                <div class="text-2xl font-bold text-foreground">{{ stats?.with_levels || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center">
                                    <AlertTriangle class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Unassigned</div>
                                <div class="text-2xl font-bold text-foreground">{{ stats?.without_assignments || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Bulk Assignment Panel -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <UserPlus class="mr-2 h-5 w-5" />
                        Bulk Assignment
                    </CardTitle>
                    <CardDescription>Assign multiple users to departments and levels simultaneously</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                        <div class="space-y-2">
                            <Label>Department</Label>
                            <Select :model-value="selectedDepartment || 'select'" @update:model-value="handleDepartmentChange">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Department" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="select">Select Department</SelectItem>
                                    <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id.toString()">
                                        {{ dept.name }} ({{ dept.department_code }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label>User Level</Label>
                            <Select :model-value="selectedLevel || 'select'" @update:model-value="handleLevelChange">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Level" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="select">Select Level</SelectItem>
                                    <SelectItem v-for="level in userLevels" :key="level.id" :value="level.id.toString()">
                                        {{ level.code }} - {{ level.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-end">
                            <Button
                                @click="bulkAssignUsers"
                                :disabled="!Array.isArray(selectedUsers) || selectedUsers.length === 0 || (!selectedDepartment && !selectedLevel)"
                                class="w-full"
                            >
                                <UserPlus class="mr-2 h-4 w-4" />
                                Assign {{ Array.isArray(selectedUsers) ? selectedUsers.length : 0 }} User(s)
                            </Button>
                        </div>

                        <div class="flex items-center text-sm text-muted-foreground">
                            <Badge variant="secondary">{{ Array.isArray(selectedUsers) ? selectedUsers.length : 0 }}</Badge>
                            <span class="ml-2">of {{ filteredUsers.length }} users selected</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Filters and Search -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Search class="mr-2 h-5 w-5" />
                        Filters & Search
                    </CardTitle>
                    <CardDescription>Filter and search users by various criteria</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="space-y-2">
                            <Label>Search</Label>
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Name, email, or employee ID..."
                                    class="pl-10"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label>Filter by Department</Label>
                            <Select :model-value="filterDepartment || 'all'" @update:model-value="handleFilterDepartmentChange">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Departments" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Departments</SelectItem>
                                    <SelectItem v-for="dept in departments" :key="dept.name" :value="dept.name">
                                        {{ dept.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label>Filter by Level</Label>
                            <Select :model-value="filterLevel || 'all'" @update:model-value="handleFilterLevelChange">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Levels" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Levels</SelectItem>
                                    <SelectItem v-for="level in userLevels" :key="level.code" :value="level.code">
                                        {{ level.code }} - {{ level.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-end">
                            <Button @click="clearFilters" variant="outline" class="w-full">
                                <X class="mr-2 h-4 w-4" />
                                Clear Filters
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Users Table -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center">
                                <Users class="mr-2 h-5 w-5" />
                                Users
                            </CardTitle>
                            <CardDescription>Manage user assignments and organizational structure</CardDescription>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    :checked="selectAllFiltered"
                                    @update:checked="(checked) => { selectAllFiltered = checked; toggleSelectAll() }"
                                />
                                <Label class="text-sm">Select all {{ filteredUsers.length }} users</Label>
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12">
                                        <Checkbox
                                            :checked="allPageSelected"
                                            @update:checked="togglePageSelection"
                                            :indeterminate="somePageSelected"
                                        />
                                    </TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Level</TableHead>
                                    <TableHead>Department</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in paginatedUsers" :key="user.id" class="hover:bg-muted/50">
                                    <TableCell>
                                        <Checkbox
                                            :checked="isUserSelected(user.id)"
                                            @update:checked="(checked) => handleUserSelection(user.id, checked)"
                                        />
                                    </TableCell>
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
                                        <Badge v-if="user.level" variant="secondary">
                                            {{ user.level }}
                                        </Badge>
                                        <span v-else class="text-sm text-muted-foreground italic">No level</span>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="user.department" class="text-foreground">{{ user.department }}</span>
                                        <span v-else class="text-sm text-muted-foreground italic">No department</span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getUserStatusVariant(user.status)">
                                            {{ user.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button
                                            :as="Link"
                                            :href="route('admin.users.organizational', user.id)"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Pagination -->
                        <div v-if="totalPages > 1" class="flex items-center justify-between pt-4">
                            <div class="flex justify-between flex-1 sm:hidden">
                                <Button
                                    @click="currentPage = Math.max(1, currentPage - 1)"
                                    :disabled="currentPage === 1"
                                    variant="outline"
                                >
                                    <ChevronLeft class="mr-2 h-4 w-4" />
                                    Previous
                                </Button>
                                <Button
                                    @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                    :disabled="currentPage === totalPages"
                                    variant="outline"
                                >
                                    Next
                                    <ChevronRight class="ml-2 h-4 w-4" />
                                </Button>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div class="text-sm text-muted-foreground">
                                    Showing {{ ((currentPage - 1) * usersPerPage) + 1 }} to {{ Math.min(currentPage * usersPerPage, filteredUsers.length) }} of {{ filteredUsers.length }} results
                                </div>
                                <div class="flex space-x-1">
                                    <Button
                                        @click="currentPage = Math.max(1, currentPage - 1)"
                                        :disabled="currentPage === 1"
                                        variant="outline"
                                        size="sm"
                                    >
                                        <ChevronLeft class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        v-for="page in Math.min(totalPages, 5)"
                                        :key="page"
                                        @click="currentPage = page"
                                        :variant="currentPage === page ? 'default' : 'outline'"
                                        size="sm"
                                    >
                                        {{ page }}
                                    </Button>
                                    <Button
                                        @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                        :disabled="currentPage === totalPages"
                                        variant="outline"
                                        size="sm"
                                    >
                                        <ChevronRight class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
