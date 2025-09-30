<!--
  User Management & Assignment Page
  Interface for bulk user management, department/level assignments with comprehensive filtering
-->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref } from 'vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
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
    Alert,
    AlertDescription,
} from '@/components/ui/alert'
import {
    Import,
    Download,
    Users,
    Building2,
    Tag,
    Crown,
    AlertTriangle,
    X,
    Eye,
    Settings,
    ChevronLeft,
    ChevronRight
} from 'lucide-vue-next'

const props = defineProps({
    users: Object, // Paginated users
    departments: Array,
    userLevels: Array,
    stats: Object,
})

const selectedUsers = ref([])
const bulkAction = ref('')
const selectedDepartment = ref('')
const selectedLevel = ref('')

// Bulk assign department
const bulkAssignDepartment = () => {
    if (selectedUsers.value.length === 0) {
        alert('Please select users first');
        return;
    }

    if (!selectedDepartment.value) {
        alert('Please select a department');
        return;
    }

    router.post('/admin/users/bulk-assign-department', {
        user_ids: selectedUsers.value,
        department_id: selectedDepartment.value
    }, {
        preserveState: true,
        onSuccess: () => {
            alert('Users assigned to department successfully!');
            selectedUsers.value = [];
        },
        onError: (errors) => {
            console.error('Bulk assignment failed:', errors);
            alert('Failed to assign users. Please try again.');
        }
    });
}

// Bulk assign level
const bulkAssignLevel = () => {
    if (selectedUsers.value.length === 0) {
        alert('Please select users first');
        return;
    }

    if (!selectedLevel.value) {
        alert('Please select a level');
        return;
    }

    router.post('/admin/users/bulk-assign-level', {
        user_ids: selectedUsers.value,
        user_level_id: selectedLevel.value
    }, {
        preserveState: true,
        onSuccess: () => {
            alert('Users assigned to level successfully!');
            selectedUsers.value = [];
        },
        onError: (errors) => {
            console.error('Bulk assignment failed:', errors);
            alert('Failed to assign users. Please try again.');
        }
    });
}

// Toggle all users selection
const toggleAll = (checked: boolean) => {
    if (checked) {
        selectedUsers.value = props.users.data.map(user => user.id);
    } else {
        selectedUsers.value = [];
    }
}

// Get level badge color scheme
const getLevelColorScheme = (level: string) => {
    const colors = {
        'L1': 'secondary',
        'L2': 'default',
        'L3': 'outline',
        'L4': 'destructive',
    };
    return colors[level] || 'secondary';
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

// Clear selection
const clearSelection = () => {
    selectedUsers.value = []
}

// Handle department selection
const handleDepartmentChange = (value: string) => {
    selectedDepartment.value = value === 'select' ? '' : value
}

// Handle level selection
const handleLevelChange = (value: string) => {
    selectedLevel.value = value === 'select' ? '' : value
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'User Management', href: route('admin.users.assignment') },
    { name: 'User Assignment', href: route('admin.users.assignment') }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">User Management & Assignment</h1>
                    <p class="text-sm text-muted-foreground mt-1">Manage user assignments, departments, and organizational levels</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Button :as="Link" :href="route('admin.users.import')" variant="outline">
                        <Import class="mr-2 h-4 w-4" />
                        Import Users
                    </Button>
                    <Button :as="Link" :href="route('admin.users.export')">
                        <Download class="mr-2 h-4 w-4" />
                        Export Users
                    </Button>
                </div>
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
                                <div class="text-2xl font-bold text-foreground">{{ stats.total_users || 0 }}</div>
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
                                <div class="text-2xl font-bold text-foreground">{{ stats.users_with_departments || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <Tag class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">With Levels</div>
                                <div class="text-2xl font-bold text-foreground">{{ stats.users_with_levels || 0 }}</div>
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
                                <div class="text-sm font-medium text-muted-foreground">With Managers</div>
                                <div class="text-2xl font-bold text-foreground">{{ stats.users_with_managers || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Bulk Actions -->
            <Alert v-if="selectedUsers.length > 0" class="border-amber-200 bg-amber-50">
                <AlertTriangle class="h-4 w-4 text-amber-600" />
                <AlertDescription>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-amber-800 font-medium">{{ selectedUsers.length }} user(s) selected</span>
                        <Button @click="clearSelection" variant="ghost" size="sm" class="text-amber-600 hover:text-amber-800">
                            <X class="mr-1 h-3 w-3" />
                            Clear Selection
                        </Button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Bulk Assign Department -->
                        <div class="flex gap-2">
                            <Select :model-value="selectedDepartment || 'select'" @update:model-value="handleDepartmentChange">
                                <SelectTrigger class="flex-1">
                                    <SelectValue placeholder="Select Department" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="select">Select Department</SelectItem>
                                    <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id.toString()">
                                        {{ dept.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button @click="bulkAssignDepartment" variant="outline">
                                <Building2 class="mr-2 h-4 w-4" />
                                Assign Dept
                            </Button>
                        </div>

                        <!-- Bulk Assign Level -->
                        <div class="flex gap-2">
                            <Select :model-value="selectedLevel || 'select'" @update:model-value="handleLevelChange">
                                <SelectTrigger class="flex-1">
                                    <SelectValue placeholder="Select Level" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="select">Select Level</SelectItem>
                                    <SelectItem v-for="level in userLevels" :key="level.id" :value="level.id.toString()">
                                        {{ level.code }} - {{ level.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button @click="bulkAssignLevel" variant="outline">
                                <Tag class="mr-2 h-4 w-4" />
                                Assign Level
                            </Button>
                        </div>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- Users Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Users class="mr-2 h-5 w-5" />
                        User Management
                    </CardTitle>
                    <CardDescription>Manage user assignments and organizational structure</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12">
                                        <Checkbox
                                            @update:checked="toggleAll"
                                            :checked="selectedUsers.length === users.data.length && users.data.length > 0"
                                            :indeterminate="selectedUsers.length > 0 && selectedUsers.length < users.data.length"
                                        />
                                    </TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Department</TableHead>
                                    <TableHead>Level</TableHead>
                                    <TableHead>Manager</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="user in users.data"
                                    :key="user.id"
                                    :class="{ 'bg-primary/5': selectedUsers.includes(user.id) }"
                                    class="hover:bg-muted/50"
                                >
                                    <TableCell>
                                        <Checkbox
                                            v-model:checked="selectedUsers"
                                            :value="user.id"
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
                                        <div v-if="user.department">
                                            <div class="font-medium text-foreground">{{ user.department.name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ user.department.department_code }}</div>
                                        </div>
                                        <div v-else class="text-sm text-muted-foreground italic">
                                            No department assigned
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge v-if="user.user_level" :variant="getLevelColorScheme(user.user_level.code)">
                                            {{ user.user_level.code }} - {{ user.user_level.name }}
                                        </Badge>
                                        <span v-else class="text-sm text-muted-foreground italic">
                                            No level assigned
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="user.managers && user.managers.length > 0" class="space-y-1">
                                            <div v-for="manager in user.managers.slice(0, 2)" :key="manager.id" class="text-sm">
                                                <span class="font-medium text-foreground">{{ manager.name }}</span>
                                                <Badge variant="outline" class="ml-1 text-xs">{{ manager.pivot.role_type }}</Badge>
                                            </div>
                                            <div v-if="user.managers.length > 2" class="text-xs text-muted-foreground">
                                                +{{ user.managers.length - 2 }} more
                                            </div>
                                        </div>
                                        <div v-else class="text-sm text-muted-foreground italic">
                                            No manager assigned
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getUserStatusVariant(user.status)">
                                            {{ user.status }}
                                        </Badge>
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
                                                :as="Link"
                                                :href="route('admin.users.assign-form', user.id)"
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <Settings class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Pagination -->
                        <div v-if="users.links && users.links.length > 3" class="flex items-center justify-between pt-4">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Button
                                    v-if="users.prev_page_url"
                                    :as="Link"
                                    :href="users.prev_page_url"
                                    variant="outline"
                                >
                                    <ChevronLeft class="mr-2 h-4 w-4" />
                                    Previous
                                </Button>
                                <Button
                                    v-if="users.next_page_url"
                                    :as="Link"
                                    :href="users.next_page_url"
                                    variant="outline"
                                >
                                    Next
                                    <ChevronRight class="ml-2 h-4 w-4" />
                                </Button>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div class="text-sm text-muted-foreground">
                                    Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                                </div>
                                <div class="flex space-x-1">
                                    <template v-for="link in users.links" :key="link.label">
                                        <Button
                                            v-if="link.url"
                                            :as="Link"
                                            :href="link.url"
                                            :variant="link.active ? 'default' : 'outline'"
                                            size="sm"
                                            v-html="link.label"
                                        />
                                        <Button
                                            v-else
                                            variant="outline"
                                            size="sm"
                                            disabled
                                            v-html="link.label"
                                        />
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-if="!users.data || users.data.length === 0" class="text-center py-12">
                            <Users class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 class="text-lg font-medium text-foreground mb-2">No users found</h3>
                            <p class="text-sm text-muted-foreground mb-6">Start by importing users or check your filters.</p>
                            <Button :as="Link" :href="route('admin.users.import')">
                                <Import class="mr-2 h-4 w-4" />
                                Import Users
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
