<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref } from 'vue'
import { type BreadcrumbItemType } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle
} from '@/components/ui/dialog'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle
} from '@/components/ui/alert-dialog'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Separator } from '@/components/ui/separator'

// Icons
import {
    User,
    Users,
    Building2,
    BookOpen,
    Plus,
    Edit,
    Trash2,
    ExternalLink,
    AlertCircle,
    CheckCircle,
    Info,
    XCircle
} from 'lucide-vue-next'

const props = defineProps({
    department: Object,
    managerRoles: Array,
    users: Array,
})

// Modal states
const showAddManagerModal = ref(false)
const showNotification = ref(false)
const showConfirmation = ref(false)
const showLoading = ref(false)

// Form states
const selectedManagerType = ref('')
const selectedManager = ref('')
const availableManagers = ref([])

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

// Helper function to show notifications
const showNotificationModal = (type: string, title: string, message: string) => {
    notification.value = { type, title, message }
    showNotification.value = true
}

// Helper function to show confirmations
const showConfirmationModal = (title: string, message: string, action: () => void) => {
    confirmation.value = { title, message, action }
    showConfirmation.value = true
}

// Remove manager role with confirmation
const removeManagerRole = (roleId: number) => {
    showConfirmationModal(
        'Remove Manager Role',
        'Are you sure you want to remove this manager role? This action cannot be undone.',
        () => {
            showLoading.value = true
            loading.value.message = 'Removing manager role...'

            router.delete(route('admin.departments.remove-manager', {
                department: props.department.id,
                role: roleId
            }), {
                preserveState: true,
                onSuccess: () => {
                    showLoading.value = false
                    showNotificationModal('success', 'Success', 'Manager role removed successfully!')
                },
                onError: (errors) => {
                    showLoading.value = false
                    console.error('Remove failed:', errors)
                    showNotificationModal('error', 'Error', 'Failed to remove manager role. Please try again.')
                }
            })
        }
    )
}

// Load manager candidates
const loadManagerCandidates = async () => {
    try {
        showLoading.value = true
        loading.value.message = 'Loading available managers...'

        const response = await fetch(route('admin.departments.manager-candidates', props.department.id))
        availableManagers.value = await response.json()

        showLoading.value = false
        showAddManagerModal.value = true
    } catch (error) {
        showLoading.value = false
        console.error('Failed to load manager candidates:', error)
        showNotificationModal('error', 'Error', 'Failed to load manager candidates. Please try again.')
    }
}

// Assign new manager
const assignManager = () => {
    if (!selectedManager.value || !selectedManagerType.value) {
        showNotificationModal('warning', 'Validation Error', 'Please select both manager and role type.')
        return
    }

    showLoading.value = true
    loading.value.message = 'Assigning manager...'

    router.post(route('admin.departments.assign-manager', props.department.id), {
        user_id: selectedManager.value,
        role_type: selectedManagerType.value,
        is_primary: selectedManagerType.value === 'director'
    }, {
        preserveState: true,
        onSuccess: () => {
            showLoading.value = false
            showAddManagerModal.value = false
            selectedManager.value = ''
            selectedManagerType.value = ''
            showNotificationModal('success', 'Success', 'Manager assigned successfully!')
        },
        onError: (errors) => {
            showLoading.value = false
            console.error('Assign failed:', errors)
            showNotificationModal('error', 'Error', 'Failed to assign manager. Please try again.')
        }
    })
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

const closeAddManagerModal = () => {
    showAddManagerModal.value = false
    selectedManager.value = ''
    selectedManagerType.value = ''
}

// Get role type badge variant
const getRoleTypeVariant = (roleType: string) => {
    const variants = {
        'direct_manager': 'secondary',
        'project_manager': 'outline',
        'director': 'default',
        'senior_manager': 'destructive',
        'supervisor': 'secondary',
    };
    return variants[roleType] || 'outline';
}

// Get level badge variant
const getLevelVariant = (level) => {
    if (!level) return 'outline';

    const variants = {
        'L1': 'secondary',
        'L2': 'outline',
        'L3': 'secondary',
        'L4': 'destructive',
    };
    return variants[level] || 'outline';
}

// Get notification icon
const getNotificationIcon = (type: string) => {
    const icons = {
        'success': CheckCircle,
        'error': XCircle,
        'warning': AlertCircle,
        'info': Info
    };
    return icons[type] || Info;
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Departments', href: route('admin.departments.index') },
    { name: props.department.name, href: route('admin.departments.show', props.department.id) }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Department Header -->
            <Card>
                <CardHeader>
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-center space-x-4">
                            <Avatar class="h-16 w-16">
                                <AvatarFallback class="bg-primary/10 text-primary text-lg font-bold">
                                    {{ department.department_code }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <CardTitle class="text-2xl">{{ department.name }}</CardTitle>
                                <CardDescription class="mt-1">{{ department.description }}</CardDescription>
                                <div class="mt-2 flex items-center flex-wrap gap-4 text-sm text-muted-foreground">
                                    <span><strong>Code:</strong> {{ department.department_code }}</span>
                                    <span v-if="department.parent_name">
                                        <strong>Parent:</strong> {{ department.parent_name }}
                                    </span>
                                    <Badge :variant="department.is_active ? 'default' : 'destructive'">
                                        {{ department.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                            <Button asChild class="w-full sm:w-auto">
                                <Link :href="route('admin.departments.edit', department.id)">
                                    <Edit class="h-4 w-4 mr-2" />
                                    Edit Department
                                </Link>
                            </Button>
                            <Button @click="loadManagerCandidates" variant="outline" class="w-full sm:w-auto">
                                <Plus class="h-4 w-4 mr-2" />
                                Add Manager
                            </Button>
                        </div>
                    </div>

                    <!-- Hierarchy Path -->
                    <div v-if="department.hierarchy_path">
                        <Separator class="my-4" />
                        <div class="text-sm text-muted-foreground">
                            <strong>Hierarchy:</strong> {{ department.hierarchy_path }}
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-primary rounded-md flex items-center justify-center">
                                    <Users class="h-5 w-5 text-primary-foreground" />
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <p class="text-sm font-medium text-muted-foreground truncate">Total Users</p>
                                <p class="text-lg font-medium">{{ users.length }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <User class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <p class="text-sm font-medium text-muted-foreground truncate">Manager Roles</p>
                                <p class="text-lg font-medium">{{ managerRoles.length }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                    <Building2 class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <p class="text-sm font-medium text-muted-foreground truncate">Sub-Departments</p>
                                <p class="text-lg font-medium">{{ department.children_count || 0 }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                    <BookOpen class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <p class="text-sm font-medium text-muted-foreground truncate">Active Courses</p>
                                <p class="text-lg font-medium">{{ department.active_courses || 0 }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Manager Roles Section -->
                <Card>
                    <CardHeader>
                        <CardTitle>Manager Roles</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="divide-y divide-border">
                            <div v-for="role in managerRoles" :key="role.id" class="p-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <Avatar>
                                            <AvatarFallback>
                                                {{ role.manager.name.charAt(0).toUpperCase() }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center space-x-2">
                                                <p class="text-sm font-medium truncate">
                                                    {{ role.manager.name }}
                                                </p>
                                                <Badge v-if="role.is_primary" variant="default">
                                                    Primary
                                                </Badge>
                                            </div>
                                            <div class="mt-1 flex items-center space-x-2">
                                                <Badge :variant="getRoleTypeVariant(role.role_type)">
                                                    {{ role.role_display }}
                                                </Badge>
                                                <span class="text-xs text-muted-foreground">{{ role.manager.level }}</span>
                                            </div>
                                            <p class="text-xs text-muted-foreground mt-1">
                                                {{ role.manager.email }}
                                            </p>
                                            <p class="text-xs text-muted-foreground mt-1">
                                                Started: {{ role.start_date }}
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        @click="removeManagerRole(role.id)"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            <!-- No managers state -->
                            <div v-if="managerRoles.length === 0" class="p-6 text-center">
                                <User class="mx-auto h-12 w-12 text-muted-foreground" />
                                <CardTitle class="mt-2 text-sm">No managers assigned</CardTitle>
                                <CardDescription class="mt-1">Start by adding a manager to this department.</CardDescription>
                                <div class="mt-6">
                                    <Button @click="loadManagerCandidates">
                                        <Plus class="h-4 w-4 mr-2" />
                                        Add Manager
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Department Users Section -->
                <Card>
                    <CardHeader>
                        <CardTitle>Department Users</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="divide-y divide-border max-h-96 overflow-y-auto">
                            <div v-for="user in users" :key="user.id" class="p-6">
                                <div class="flex items-center space-x-4">
                                    <Avatar>
                                        <AvatarFallback>
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium truncate">
                                            {{ user.name }}
                                        </p>
                                        <div class="mt-1 flex items-center space-x-2">
                                            <Badge v-if="user.level" :variant="getLevelVariant(user.level)">
                                                {{ user.level }}
                                            </Badge>
                                            <span v-if="user.employee_code" class="text-xs text-muted-foreground">
                                                ID: {{ user.employee_code }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-1">
                                            {{ user.email }}
                                        </p>
                                    </div>
                                    <Button asChild variant="ghost" size="sm">
                                        <Link :href="route('admin.users.organizational', user.id)">
                                            <ExternalLink class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>

                            <!-- No users state -->
                            <div v-if="users.length === 0" class="p-6 text-center">
                                <Users class="mx-auto h-12 w-12 text-muted-foreground" />
                                <CardTitle class="mt-2 text-sm">No users assigned</CardTitle>
                                <CardDescription class="mt-1">Users need to be assigned to this department.</CardDescription>
                                <div class="mt-6">
                                    <Button asChild>
                                        <Link :href="route('admin.users.assignment')">
                                            <Plus class="h-4 w-4 mr-2" />
                                            Assign Users
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sub-Departments Section -->
            <Card v-if="department.children && department.children.length > 0">
                <CardHeader>
                    <CardTitle>Sub-Departments</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="divide-y divide-border">
                        <div v-for="child in department.children" :key="child.id" class="p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <Avatar>
                                        <AvatarFallback class="bg-primary/10 text-primary">
                                            {{ child.department_code }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium">
                                            {{ child.name }}
                                        </p>
                                        <p class="text-sm text-muted-foreground mt-1">
                                            {{ child.description }}
                                        </p>
                                        <div class="mt-2 flex items-center space-x-4 text-xs text-muted-foreground">
                                            <span>{{ child.users_count }} users</span>
                                            <span>{{ child.managers_count }} managers</span>
                                        </div>
                                    </div>
                                </div>
                                <Button asChild variant="ghost" size="sm">
                                    <Link :href="route('admin.departments.show', child.id)">
                                        <ExternalLink class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Add Manager Modal -->
        <Dialog :open="showAddManagerModal" @update:open="closeAddManagerModal">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center">
                        <User class="h-5 w-5 mr-2" />
                        Add Manager to {{ department.name }}
                    </DialogTitle>
                    <DialogDescription>
                        Select a manager and assign them a role in this department.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="manager">Manager</Label>
                        <Select v-model="selectedManager">
                            <SelectTrigger>
                                <SelectValue placeholder="Select Manager" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="manager in availableManagers"
                                    :key="manager.id"
                                    :value="manager.id.toString()"
                                >
                                    {{ manager.name }} ({{ manager.level }}) - {{ manager.department }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label for="role-type">Role Type</Label>
                        <Select v-model="selectedManagerType">
                            <SelectTrigger>
                                <SelectValue placeholder="Select Role Type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="director">Director</SelectItem>
                                <SelectItem value="senior_manager">Senior Manager</SelectItem>
                                <SelectItem value="direct_manager">Direct Manager</SelectItem>
                                <SelectItem value="project_manager">Project Manager</SelectItem>
                                <SelectItem value="supervisor">Supervisor</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="closeAddManagerModal">
                        Cancel
                    </Button>
                    <Button
                        @click="assignManager"
                        :disabled="!selectedManager || !selectedManagerType"
                    >
                        Assign Manager
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Notification Alert -->
        <Dialog :open="showNotification" @update:open="closeNotification">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center">
                        <component :is="getNotificationIcon(notification.type)" class="h-5 w-5 mr-2" />
                        {{ notification.title }}
                    </DialogTitle>
                </DialogHeader>
                <div class="py-4">
                    <Alert>
                        <component :is="getNotificationIcon(notification.type)" class="h-4 w-4" />
                        <AlertDescription>
                            {{ notification.message }}
                        </AlertDescription>
                    </Alert>
                </div>
                <DialogFooter>
                    <Button @click="closeNotification">
                        OK
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Confirmation Dialog -->
        <AlertDialog :open="showConfirmation" @update:open="closeConfirmation">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle class="flex items-center">
                        <AlertCircle class="h-5 w-5 mr-2 text-destructive" />
                        {{ confirmation.title }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {{ confirmation.message }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="closeConfirmation">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction @click="handleConfirmation" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                        Yes, Remove
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Loading Dialog -->
        <Dialog :open="showLoading" @update:open="() => {}">
            <DialogContent class="sm:max-w-[300px]">
                <div class="flex items-center justify-center py-6">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mr-3"></div>
                    <span>{{ loading.message }}</span>
                </div>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
