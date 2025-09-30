<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref } from 'vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Loader2, Building, CheckCircle, Users } from 'lucide-vue-next'

const props = defineProps({
    departments: Array,
    stats: Object,
})

// Modal states
const showNotification = ref(false)
const showConfirmation = ref(false)
const showLoading = ref(false)

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

// Delete department function with confirmation
const deleteDepartment = (departmentId: number, departmentName: string) => {
    showConfirmationModal(
        'Delete Department',
        `Are you sure you want to delete "${departmentName}"? This action cannot be undone and will permanently remove all associated data.`,
        () => {
            showLoading.value = true
            loading.value.message = 'Deleting department...'

            router.delete(route('admin.departments.destroy', departmentId), {
                preserveState: true,
                onSuccess: () => {
                    showLoading.value = false
                    showNotificationModal('success', 'Success', 'Department deleted successfully!')
                },
                onError: (errors) => {
                    showLoading.value = false
                    console.error('Delete failed:', errors)

                    // Handle specific error messages
                    const errorMessage = errors.delete || 'Failed to delete department. Please try again.'
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

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Organizational Management', href: route('admin.departments.index') },
    { name: 'Departments', href: route('admin.departments.index') }
]

// Get status variant for badges
const getStatusVariant = (isActive: boolean) => {
    return isActive ? 'default' : 'destructive'
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h1 class="text-xl sm:text-2xl font-bold text-foreground">Department Management</h1>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Button :as="Link" :href="route('admin.departments.create')" class="w-full sm:w-auto">
                        Add New Department
                    </Button>
                    <Button
                        :as="Link"
                        :href="route('admin.manager-roles.create')"
                        variant="secondary"
                        class="w-full sm:w-auto"
                    >
                        Assign Manager Roles
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-primary rounded-md flex items-center justify-center">
                                    <Building class="h-5 w-5 text-primary-foreground" />
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Total Departments</div>
                                <div class="text-lg font-medium text-foreground">{{ stats.total_departments || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <CheckCircle class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Active Departments</div>
                                <div class="text-lg font-medium text-foreground">{{ stats.active_departments || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                    <Users class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">With Managers</div>
                                <div class="text-lg font-medium text-foreground">{{ stats.departments_with_managers || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Departments Table -->
            <Card>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Department</TableHead>
                                <TableHead>Hierarchy</TableHead>
                                <TableHead>Managers</TableHead>
                                <TableHead>Users</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-for="department in departments" :key="department.id">
                                <!-- Main Department Row -->
                                <TableRow>
                                    <TableCell>
                                        <div class="flex items-center">
                                            <Avatar class="h-10 w-10">
                                                <AvatarFallback class="bg-primary/10 text-primary">
                                                    {{ department.department_code }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="ml-4">
                                                <div class="font-medium text-foreground">{{ department.name }}</div>
                                                <div class="text-sm text-muted-foreground">{{ department.description }}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm text-foreground">{{ department.hierarchy_path }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-col space-y-1">
                                            <div v-for="manager in department.managers" :key="manager.name" class="text-sm">
                                                <span class="font-medium text-foreground">{{ manager.name }}</span>
                                                <span class="text-muted-foreground">({{ manager.role_type }})</span>
                                                <Badge v-if="manager.is_primary" variant="secondary" class="ml-1">
                                                    Primary
                                                </Badge>
                                            </div>
                                            <div v-if="department.managers.length === 0" class="text-sm text-muted-foreground">
                                                No managers assigned
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-foreground">
                                        {{ department.users_count }} users
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(department.is_active)">
                                            {{ department.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <Button
                                                :as="Link"
                                                :href="route('admin.departments.show', department.id)"
                                                variant="outline"
                                                size="sm"
                                            >
                                                View
                                            </Button>
                                            <Button
                                                :as="Link"
                                                :href="route('admin.departments.edit', department.id)"
                                                variant="outline"
                                                size="sm"
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                @click="deleteDepartment(department.id, department.name)"
                                                variant="destructive"
                                                size="sm"
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <!-- Child Departments -->
                                <TableRow v-for="child in department.children" :key="child.id" class="bg-muted/50">
                                    <TableCell>
                                        <div class="flex items-center pl-8">
                                            <Avatar class="h-8 w-8">
                                                <AvatarFallback class="bg-muted text-muted-foreground text-xs">
                                                    {{ child.department_code }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="ml-4">
                                                <div class="font-medium text-foreground">{{ child.name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ child.description }}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm text-foreground">{{ child.hierarchy_path }}</div>
                                    </TableCell>
                                    <TableCell class="text-foreground">{{ child.managers_count }} managers</TableCell>
                                    <TableCell class="text-foreground">{{ child.users_count }} users</TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(child.is_active)">
                                            {{ child.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <Button
                                                :as="Link"
                                                :href="route('admin.departments.show', child.id)"
                                                variant="outline"
                                                size="sm"
                                            >
                                                View
                                            </Button>
                                            <Button
                                                :as="Link"
                                                :href="route('admin.departments.edit', child.id)"
                                                variant="outline"
                                                size="sm"
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                @click="deleteDepartment(child.id, child.name)"
                                                variant="destructive"
                                                size="sm"
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </div>

                <!-- Empty state -->
                <div v-if="!departments || departments.length === 0" class="text-center py-12">
                    <div class="text-muted-foreground">
                        <Building class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <h3 class="mt-2 text-sm font-medium text-foreground">No departments found</h3>
                        <p class="mt-1 text-sm text-muted-foreground">Get started by creating a new department.</p>
                        <div class="mt-6">
                            <Button :as="Link" :href="route('admin.departments.create')">
                                Add New Department
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Notification Alert -->
        <Alert v-if="showNotification" class="fixed top-4 right-4 w-96 z-50" :variant="notification.type === 'error' ? 'destructive' : 'default'">
            <AlertDescription>
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-medium">{{ notification.title }}</div>
                        <div class="text-sm">{{ notification.message }}</div>
                    </div>
                    <Button @click="closeNotification" variant="ghost" size="sm" class="h-6 w-6 p-0">
                        ×
                    </Button>
                </div>
            </AlertDescription>
        </Alert>

        <!-- Confirmation Dialog -->
        <AlertDialog v-model:open="showConfirmation">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ confirmation.title }}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {{ confirmation.message }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="closeConfirmation">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="handleConfirmation" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                        Yes, Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Loading Dialog -->
        <AlertDialog v-model:open="showLoading">
            <AlertDialogContent class="sm:max-w-md">
                <AlertDialogHeader>
                    <AlertDialogTitle class="flex items-center gap-2">
                        <Loader2 class="h-4 w-4 animate-spin" />
                        Processing
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {{ loading.message }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
            </AlertDialogContent>
        </AlertDialog>
    </AdminLayout>
</template>
