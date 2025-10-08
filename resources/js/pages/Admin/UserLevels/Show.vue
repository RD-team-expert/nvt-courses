<!--
  Enhanced User Level Details Page with Tier Support
  Detailed view of a user level with tier management and user assignment
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
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
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
    ChevronRight,
    Layers,
    Target,
    TrendingUp,
    Award,
    Info
} from 'lucide-vue-next'

const props = defineProps({
    userLevel: Object,
    users: Array,
    availableUsers: Array,
    // NEW: Tier information
    tiers: Array,
    stats: Object,
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
const selectedUsers = ref<number[]>([])

// NEW: Tier assignment states
const selectedTier = ref<number | null>(null)
const showTierAssignmentMode = ref(false)

// Enhanced filtering and search
const searchQuery = ref('')
const selectedDepartment = ref('all')
const selectedStatus = ref('all')
const selectedTierFilter = ref('all') // NEW: Tier filter
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

const isUserSelected = (userId: number): boolean => {
    return selectedUsers.value.includes(userId)
}

const toggleUserSelection = (userId: number, checked: boolean) => {
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

// NEW: Get users grouped by tier for display
const usersByTier = computed(() => {
    const grouped = {}

    // Initialize with all tiers
    props.tiers?.forEach(tier => {
        grouped[tier.id] = {
            tier,
            users: []
        }
    })

    // Add "No Tier" group
    grouped['no_tier'] = {
        tier: { id: null, tier_name: 'No Tier Assigned', tier_order: 999 },
        users: []
    }

    // Group users by their tiers
    props.users?.forEach(user => {
        const tierKey = user.user_level_tier_id || 'no_tier'
        if (grouped[tierKey]) {
            grouped[tierKey].users.push(user)
        }
    })

    return grouped
})

// Enhanced filtered users with tier support
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
    if (selectedDepartment.value && selectedDepartment.value !== 'all') {
        filtered = filtered.filter(user => user.department === selectedDepartment.value)
    }

    // Status filter
    if (selectedStatus.value && selectedStatus.value !== 'all') {
        filtered = filtered.filter(user => user.status === selectedStatus.value)
    }

    // NEW: Tier filter
    if (selectedTierFilter.value && selectedTierFilter.value !== 'all') {
        if (selectedTierFilter.value === 'no_tier') {
            filtered = filtered.filter(user => !user.user_level_tier_id)
        } else {
            filtered = filtered.filter(user => user.user_level_tier_id?.toString() === selectedTierFilter.value)
        }
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
watch([searchQuery, selectedDepartment, selectedStatus, selectedTierFilter], () => {
    currentPage.value = 1
})

// Select/Deselect all on current page
const selectAllOnPage = ref(false)
const toggleSelectAllOnPage = () => {
    if (selectAllOnPage.value) {
        paginatedUsers.value.forEach(user => {
            if (!selectedUsers.value.includes(user.id)) {
                selectedUsers.value.push(user.id)
            }
        })
    } else {
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
    selectedDepartment.value = 'all'
    selectedStatus.value = 'all'
    selectedTierFilter.value = 'all'
    currentPage.value = 1
}

// Remove user from level with confirmation
const removeUserFromLevel = (userId: number, userName: string) => {
    showConfirmationModal(
        'Remove User from Level',
        `Are you sure you want to remove "${userName}" from the ${props.userLevel.name} level? This will unassign their current level and tier.`,
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

// NEW: Remove user from tier only
const removeUserFromTier = (userId: number, userName: string, tierName: string) => {
    showConfirmationModal(
        'Remove User from Tier',
        `Are you sure you want to remove "${userName}" from the ${tierName}? They will remain in the ${props.userLevel.name} level but without a tier assignment.`,
        () => {
            showLoading.value = true
            loading.value.message = 'Removing user from tier...'

            router.post(route('admin.user-levels.assign-tier'), {
                user_id: userId,
                user_level_tier_id: null
            }, {
                preserveState: true,
                onSuccess: () => {
                    showLoading.value = false
                    showNotificationModal('success', 'Success', 'User removed from tier successfully!')
                },
                onError: (errors) => {
                    showLoading.value = false
                    console.error('Remove from tier failed:', errors)
                    const errorMessage = errors.user_level_tier_id || 'Failed to remove user from tier. Please try again.'
                    showNotificationModal('error', 'Error', errorMessage)
                }
            })
        }
    )
}

const loadAvailableUsers = () => {
    availableUsers.value = props.availableUsers
    showAssignUserModal.value = true
    showTierAssignmentMode.value = false

    // Reset modal state
    searchQuery.value = ''
    selectedDepartment.value = 'all'
    selectedStatus.value = 'all'
    selectedTierFilter.value = 'all'
    selectedUsers.value = []
    selectedTier.value = null
    currentPage.value = 1
}

const closeModal = () => {
    showAssignUserModal.value = false
    selectedUsers.value = []
    searchQuery.value = ''
    selectedDepartment.value = 'all'
    selectedStatus.value = 'all'
    selectedTierFilter.value = 'all'
    selectedTier.value = null
    showTierAssignmentMode.value = false
    currentPage.value = 1
}

// NEW: Enhanced assign users with tier support
const assignUsersToLevel = () => {
    if (selectedUsers.value.length === 0) {
        showNotificationModal('warning', 'No Selection', 'Please select at least one user to assign.')
        return
    }

    const actionText = showTierAssignmentMode.value && selectedTier.value
        ? `assign ${selectedUsers.value.length} user(s) to the ${props.userLevel.name} level with tier assignment`
        : `assign ${selectedUsers.value.length} user(s) to the ${props.userLevel.name} level`

    showConfirmationModal(
        'Assign Users to Level',
        `Are you sure you want to ${actionText}?`,
        () => {
            showLoading.value = true
            loading.value.message = 'Assigning users to level...'

            const data = {
                user_ids: selectedUsers.value,
                user_level_id: props.userLevel.id
            }

            // Add tier if specified
            if (showTierAssignmentMode.value && selectedTier.value) {
                data.user_level_tier_id = selectedTier.value
            }

            router.post(route('admin.user-levels.bulk-assign'), data, {
                preserveState: true,
                onSuccess: () => {
                    showLoading.value = false
                    showNotificationModal('success', 'Success', 'Users assigned successfully!')
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

// NEW: Get tier badge variant
const getTierBadgeVariant = (tierOrder: number) => {
    switch (tierOrder) {
        case 1: return 'default'
        case 2: return 'secondary'
        case 3: return 'outline'
        default: return 'secondary'
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
            <!-- Enhanced User Level Header -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
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
                                    <!-- NEW: Tier count badge -->
                                    <Badge v-if="tiers && tiers.length > 0" variant="outline" class="bg-purple-50">
                                        <Layers class="mr-1 h-3 w-3" />
                                        {{ tiers.length }} Performance Tiers
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <!-- NEW: Enhanced Tier Management Button -->
                            <Button
                                :as="Link"
                                :href="route('admin.user-level-tiers.index', userLevel.id)"
                                variant="outline"
                                class="border-purple-200 hover:bg-purple-50"
                            >
                                <Layers class="mr-2 h-4 w-4 text-purple-600" />
                                Manage Tiers ({{ stats?.total_tiers || 0 }})
                            </Button>

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

            <!-- Enhanced Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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

                <!-- NEW: Tiers Stats -->
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-indigo-500 rounded-lg flex items-center justify-center">
                                    <Layers class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">With Tiers</div>
                                <div class="text-2xl font-bold text-foreground">{{ users.filter(u => u.user_level_tier_id).length }}</div>
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

            <!-- NEW: Enhanced Users Display with Tier Support -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center">
                                <Users class="mr-2 h-5 w-5" />
                                Users with {{ userLevel.name }} Level
                            </CardTitle>
                            <CardDescription>Manage users assigned to this organizational level and their performance tiers</CardDescription>
                        </div>

                    </div>
                </CardHeader>
                <CardContent>
                    <!-- NEW: Tabbed view for better organization -->
                    <Tabs v-if="users && users.length > 0" default-value="by_tier" class="w-full">
                        <TabsList class="grid w-full grid-cols-2">
                            <TabsTrigger value="by_tier" class="flex items-center">
                                <Layers class="mr-2 h-4 w-4" />
                                By Performance Tier
                            </TabsTrigger>
                            <TabsTrigger value="all_users" class="flex items-center">
                                <Users class="mr-2 h-4 w-4" />
                                All Users
                            </TabsTrigger>
                        </TabsList>

                        <!-- By Tier Tab -->
                        <TabsContent value="by_tier" class="space-y-6">
                            <div v-for="(tierGroup, tierId) in usersByTier" :key="tierId" class="space-y-3">
                                <div v-if="tierGroup.users.length > 0">
                                    <!-- Tier Header -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <Badge
                                                v-if="tierGroup.tier.id"
                                                :variant="getTierBadgeVariant(tierGroup.tier.tier_order)"
                                                class="text-sm"
                                            >
                                                T{{ tierGroup.tier.tier_order }} - {{ tierGroup.tier.tier_name }}
                                            </Badge>
                                            <Badge
                                                v-else
                                                variant="outline"
                                                class="text-sm border-dashed"
                                            >
                                                <Target class="mr-1 h-3 w-3" />
                                                {{ tierGroup.tier.tier_name }}
                                            </Badge>
                                            <span class="text-sm text-muted-foreground">{{ tierGroup.users.length }} users</span>
                                        </div>
                                    </div>

                                    <!-- Users in this tier -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <Card
                                            v-for="user in tierGroup.users"
                                            :key="user.id"
                                            class="hover:shadow-md transition-shadow duration-200"
                                        >
                                            <CardContent class="p-4">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex items-center space-x-3 flex-1">
                                                        <Avatar class="h-8 w-8">
                                                            <AvatarFallback class="bg-muted text-muted-foreground text-xs">
                                                                {{ user.name.charAt(0).toUpperCase() }}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="font-medium text-foreground truncate">{{ user.name }}</div>
                                                            <div class="text-xs text-muted-foreground truncate">{{ user.email }}</div>
                                                            <div class="flex items-center space-x-2 mt-1">
                                                                <Badge :variant="getUserStatusVariant(user.status)" class="text-xs">
                                                                    {{ user.status }}
                                                                </Badge>
                                                                <span v-if="user.department" class="text-xs text-muted-foreground">
                                                                    {{ user.department }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex flex-col space-y-1 ml-2">
                                                        <Button
                                                            :as="Link"
                                                            :href="route('admin.users.organizational', user.id)"
                                                            variant="ghost"
                                                            size="sm"
                                                            class="h-7 w-7 p-0"
                                                        >
                                                            <Eye class="h-3 w-3" />
                                                        </Button>
                                                        <Button
                                                            v-if="user.user_level_tier_id"
                                                            @click="removeUserFromTier(user.id, user.name, tierGroup.tier.tier_name)"
                                                            variant="ghost"
                                                            size="sm"
                                                            class="h-7 w-7 p-0 text-orange-600 hover:text-orange-600 hover:bg-orange-50"
                                                        >
                                                            <Target class="h-3 w-3" />
                                                        </Button>
                                                        <Button
                                                            @click="removeUserFromLevel(user.id, user.name)"
                                                            variant="ghost"
                                                            size="sm"
                                                            class="h-7 w-7 p-0 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                        >
                                                            <Trash2 class="h-3 w-3" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </div>
                                </div>
                            </div>

                            <!-- No tiers message -->
                        </TabsContent>

                        <!-- All Users Tab -->
                        <TabsContent value="all_users">
                            <div class="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>User</TableHead>
                                            <TableHead>Performance Tier</TableHead>
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
                                            <!-- NEW: Tier Column -->
                                            <TableCell>
                                                <div v-if="user.user_level_tier" class="flex items-center space-x-2">
                                                    <Badge :variant="getTierBadgeVariant(user.user_level_tier.tier_order)" class="text-xs">
                                                        T{{ user.user_level_tier.tier_order }}
                                                    </Badge>
                                                    <span class="text-sm">{{ user.user_level_tier.tier_name }}</span>
                                                </div>
                                                <div v-else class="flex items-center space-x-2">
                                                    <Badge variant="outline" class="text-xs border-dashed">
                                                        <Target class="mr-1 h-2 w-2" />
                                                        No Tier
                                                    </Badge>
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
                                                        v-if="user.user_level_tier_id"
                                                        @click="removeUserFromTier(user.id, user.name, user.user_level_tier.tier_name)"
                                                        variant="ghost"
                                                        size="sm"
                                                        class="text-orange-600 hover:text-orange-600 hover:bg-orange-50"
                                                    >
                                                        <Target class="h-4 w-4" />
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
                            </div>
                        </TabsContent>
                    </Tabs>

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
                </CardContent>
            </Card>
        </div>

        <!-- Enhanced Assign Users Modal with Tier Support -->
        <Dialog v-model:open="showAssignUserModal">
            <DialogContent class="max-w-5xl max-h-[90vh] overflow-hidden">
                <DialogHeader>
                    <DialogTitle class="flex items-center">
                        <UserPlus class="mr-2 h-5 w-5" />
                        Assign Users to {{ userLevel.name }} Level
                    </DialogTitle>
                    <DialogDescription>
                        Select users to assign to this organizational level with optional performance tier assignment
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">


                    <!-- Enhanced Search and Filters -->
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
                                <SelectItem value="all">All Departments</SelectItem>
                                <SelectItem v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Status Filter -->
                        <Select :model-value="selectedStatus" @update:model-value="(value) => selectedStatus = value">
                            <SelectTrigger class="w-32">
                                <SelectValue placeholder="All Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Status</SelectItem>
                                <SelectItem v-for="status in statuses" :key="status" :value="status">{{ status }}</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- NEW: Tier Filter -->
                        <Select :model-value="selectedTierFilter" @update:model-value="(value) => selectedTierFilter = value">
                            <SelectTrigger class="w-40">
                                <SelectValue placeholder="All Tiers" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Tiers</SelectItem>
                                <SelectItem value="no_tier">No Tier</SelectItem>
                                <SelectItem v-for="tier in tiers" :key="tier.id" :value="tier.id.toString()">
                                    T{{ tier.tier_order }} - {{ tier.tier_name }}
                                </SelectItem>
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
                                :checked="selectAllOnPage"
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
                                        :checked="isUserSelected(user.id)"
                                        @update:checked="(checked) => toggleUserSelection(user.id, checked)"
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
                                            <!-- NEW: Show current tier -->
                                            <Badge v-if="user.user_level_tier" :variant="getTierBadgeVariant(user.user_level_tier.tier_order)" class="text-xs">
                                                T{{ user.user_level_tier.tier_order }} - {{ user.user_level_tier.tier_name }}
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
                        <span v-if="showTierAssignmentMode && selectedTier">
                            to Tier {{ tiers?.find(t => t.id === selectedTier)?.tier_order }}
                        </span>
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
