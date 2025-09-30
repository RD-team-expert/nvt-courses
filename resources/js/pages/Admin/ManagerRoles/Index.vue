<!--
  Manager Role Assignments Page
  Manage and overview all manager role assignments within the organization
-->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref } from 'vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
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
import {
    Users,
    CheckCircle,
    Building,
    Crown,
    Plus,
    Grid3X3,
    Eye,
    Edit,
    UserCheck,
    ChevronLeft,
    ChevronRight
} from 'lucide-vue-next'

const props = defineProps({
    roles: Object, // Paginated roles
    stats: Object,
})

// Show confirmation dialog
const showTerminateDialog = ref(false)
const roleToTerminate = ref(null)

// Open terminate confirmation
const confirmTerminate = (role) => {
    roleToTerminate.value = role
    showTerminateDialog.value = true
}

// Terminate role
const terminateRole = () => {
    if (!roleToTerminate.value) return

    router.delete(route('admin.manager-roles.destroy', roleToTerminate.value.id), {
        preserveState: true,
        onSuccess: () => {
            showTerminateDialog.value = false
            roleToTerminate.value = null
            // Success will be handled by the backend redirect or notification
        },
        onError: (errors) => {
            console.error('Terminate failed:', errors)
            showTerminateDialog.value = false
            roleToTerminate.value = null
            // Error handling can be improved with toast notifications
        }
    })
}

// Get role type badge variant
const getRoleTypeVariant = (roleType: string) => {
    const variants = {
        'direct_manager': 'default',
        'project_manager': 'secondary',
        'department_head': 'outline',
        'senior_manager': 'destructive',
        'team_lead': 'secondary',
    }
    return variants[roleType] || 'outline'
}

// Get status variant
const getStatusVariant = (isActive: boolean) => {
    return isActive ? 'default' : 'destructive'
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Organizational Management', href: route('admin.manager-roles.index') },
    { name: 'Manager Roles', href: route('admin.manager-roles.index') }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Manager Role Assignments</h1>
                    <p class="text-sm text-muted-foreground mt-1">Manage and overview all manager role assignments within the organization</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Button :as="Link" :href="route('admin.manager-roles.create')" class="w-full sm:w-auto">
                        <Plus class="mr-2 h-4 w-4" />
                        Assign New Role
                    </Button>
                    <Button :as="Link" :href="route('admin.manager-roles.matrix')" variant="secondary" class="w-full sm:w-auto">
                        <Grid3X3 class="mr-2 h-4 w-4" />
                        Matrix View
                    </Button>
                </div>
            </div>

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
                                <div class="text-sm font-medium text-muted-foreground">Total Roles</div>
                                <div class="text-lg font-medium text-foreground">{{ stats.total_roles || 0 }}</div>
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
                                <div class="text-sm font-medium text-muted-foreground">Active Roles</div>
                                <div class="text-lg font-medium text-foreground">{{ stats.active_roles || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                    <UserCheck class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Direct Managers</div>
                                <div class="text-lg font-medium text-foreground">{{ stats.direct_managers || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                    <Building class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Department Heads</div>
                                <div class="text-lg font-medium text-foreground">{{ stats.department_heads || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Roles Table -->
            <Card>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Manager</TableHead>
                                <TableHead>Role Type</TableHead>
                                <TableHead>Department</TableHead>
                                <TableHead>Manages</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="role in roles.data" :key="role.id">
                                <TableCell>
                                    <div class="flex items-center">
                                        <Avatar class="h-10 w-10">
                                            <AvatarFallback class="bg-muted">
                                                {{ role.manager.name.charAt(0).toUpperCase() }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="ml-4">
                                            <div class="font-medium text-foreground">{{ role.manager.name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ role.manager.email }}</div>
                                            <div class="text-xs text-muted-foreground">{{ role.manager.level }}</div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getRoleTypeVariant(role.role_type)">
                                        {{ role.role_display }}
                                    </Badge>
                                    <div v-if="role.is_primary" class="mt-1">
                                        <Badge variant="secondary" class="text-xs">
                                            <Crown class="mr-1 h-3 w-3" />
                                            Primary
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm text-foreground">{{ role.department.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ role.department.code }}</div>
                                </TableCell>
                                <TableCell>
                                    <div v-if="role.managed_user" class="text-sm">
                                        <div class="font-medium text-foreground">{{ role.managed_user.name }}</div>
                                        <div class="text-muted-foreground">{{ role.managed_user.email }}</div>
                                    </div>
                                    <div v-else class="text-sm text-muted-foreground">
                                        Department-wide role
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getStatusVariant(role.is_active)">
                                        {{ role.is_active ? 'Active' : 'Expired' }}
                                    </Badge>
                                    <div class="text-xs text-muted-foreground mt-1">
                                        Started: {{ role.start_date }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <Button
                                            :as="Link"
                                            :href="route('admin.manager-roles.show', role.id)"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <Eye class="h-4 w-4 mr-1" />
                                            View
                                        </Button>
                                        <Button
                                            :as="Link"
                                            :href="route('admin.manager-roles.edit', role.id)"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            <Edit class="h-4 w-4 mr-1" />
                                            Edit
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- FIXED: Simple pagination with buttons -->
                <div v-if="roles.links && roles.links.length > 3" class="border-t p-4">
                    <div class="flex items-center justify-between">
                        <!-- Mobile pagination -->
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Button
                                v-if="roles.prev_page_url"
                                :as="Link"
                                :href="roles.prev_page_url"
                                variant="outline"
                                size="sm"
                            >
                                <ChevronLeft class="h-4 w-4 mr-1" />
                                Previous
                            </Button>
                            <Button
                                v-if="roles.next_page_url"
                                :as="Link"
                                :href="roles.next_page_url"
                                variant="outline"
                                size="sm"
                            >
                                Next
                                <ChevronRight class="h-4 w-4 ml-1" />
                            </Button>
                        </div>

                        <!-- Desktop pagination -->
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground">
                                    Showing {{ roles.from }} to {{ roles.to }} of {{ roles.total }} results
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <template v-for="link in roles.links" :key="link.label">
                                    <Button
                                        v-if="link.url"
                                        :as="Link"
                                        :href="link.url"
                                        :variant="link.active ? 'default' : 'outline'"
                                        size="sm"
                                        class="min-w-[2.5rem]"
                                        v-html="link.label"
                                    />
                                    <span
                                        v-else
                                        class="px-3 py-2 text-sm text-muted-foreground"
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="!roles.data || roles.data.length === 0" class="text-center py-12">
                    <Users class="mx-auto h-12 w-12 text-muted-foreground" />
                    <h3 class="mt-2 text-sm font-medium text-foreground">No manager roles found</h3>
                    <p class="mt-1 text-sm text-muted-foreground">Get started by assigning manager roles to users.</p>
                    <div class="mt-6">
                        <Button :as="Link" :href="route('admin.manager-roles.create')">
                            <Plus class="mr-2 h-4 w-4" />
                            Assign New Role
                        </Button>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Terminate Role Confirmation Dialog -->
        <AlertDialog v-model:open="showTerminateDialog">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Terminate Manager Role</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to terminate the manager role for
                        <span class="font-semibold">{{ roleToTerminate?.manager?.name }}</span>?
                        This action cannot be undone and will permanently remove their management responsibilities.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="terminateRole" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                        Yes, Terminate Role
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AdminLayout>
</template>
