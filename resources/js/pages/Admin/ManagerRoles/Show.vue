<!--
  Manager Role Details Page
  View detailed information about a specific manager role assignment
-->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { computed, ref } from 'vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog'
import {
    Edit,
    List,
    Grid3X3,
    Trash2,
    Building,
    User,
    Crown,
    Calendar,
    AlertTriangle
} from 'lucide-vue-next'

const props = defineProps({
    role: Object,
})

const showTerminateDialog = ref(false)

// Terminate role
const terminateRole = () => {
    router.delete(route('admin.manager-roles.destroy', props.role.id), {
        onSuccess: () => {
            // Will redirect to index page automatically
        },
        onError: (errors) => {
            console.error('Terminate failed:', errors)
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

// Get authority level variant
const getAuthorityVariant = (level: number) => {
    const variants = {
        1: 'destructive',    // High Authority
        2: 'secondary',      // Medium Authority
        3: 'default',        // Low Authority
    }
    return variants[level] || 'outline'
}

// Get authority level label
const getAuthorityLabel = (level: number) => {
    const labels = {
        1: 'High Authority',
        2: 'Medium Authority',
        3: 'Low Authority',
    }
    return labels[level] || `Level ${level}`
}

// ✅ FIXED: Compute breadcrumbs to avoid route generation errors
const breadcrumbs = computed<BreadcrumbItemType[]>(() => {
    if (!props.role?.id) {
        return [
            { name: 'Dashboard', href: route('dashboard') },
            { name: 'Manager Roles', href: route('admin.manager-roles.index') },
            { name: 'Role Details', href: '#' }
        ]
    }

    return [
        { name: 'Dashboard', href: route('dashboard') },
        { name: 'Manager Roles', href: route('admin.manager-roles.index') },
        { name: 'Role Details', href: route('admin.manager-roles.show', props.role.id) }
    ]
})
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Manager Role Details</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ role?.role_display || 'Unknown Role' }} assignment for {{ role?.manager?.name || 'Unknown User' }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                    <Button
                        v-if="role?.id"
                        :as="Link"
                        :href="route('admin.manager-roles.edit', role.id)"
                        class="w-full sm:w-auto"
                    >
                        <Edit class="mr-2 h-4 w-4" />
                        Edit Role
                    </Button>

                    <AlertDialog v-if="role?.is_active">
                        <AlertDialogTrigger asChild>
                            <Button variant="destructive" class="w-full sm:w-auto">
                                <Trash2 class="mr-2 h-4 w-4" />
                                Terminate Role
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Terminate Manager Role</AlertDialogTitle>
                                <AlertDialogDescription>
                                    Are you sure you want to terminate this manager role? This action cannot be undone and will permanently remove the management assignment.
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
                </div>
            </div>

            <div v-if="role" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Role Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Role Overview Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Role Overview</CardTitle>
                            <CardDescription>Basic information about the manager role assignment</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Manager Info -->
                                <div class="space-y-3">
                                    <h3 class="text-sm font-medium text-muted-foreground">Manager</h3>
                                    <div class="flex items-center space-x-4">
                                        <Avatar class="h-12 w-12">
                                            <AvatarFallback class="text-lg">
                                                {{ role.manager?.name ? role.manager.name.charAt(0).toUpperCase() : '?' }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <div class="font-medium text-foreground">{{ role.manager?.name || 'Unknown User' }}</div>
                                            <div class="text-sm text-muted-foreground">{{ role.manager?.email || 'N/A' }}</div>
                                            <div v-if="role.manager?.level" class="text-xs text-muted-foreground mt-1">{{ role.manager.level }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Department Info -->
                                <div class="space-y-3">
                                    <h3 class="text-sm font-medium text-muted-foreground">Department</h3>
                                    <div class="flex items-center space-x-4">
                                        <div class="shrink-0 h-12 w-12 rounded-lg bg-primary/10 flex items-center justify-center">
                                            <Building class="w-6 h-6 text-primary" />
                                        </div>
                                        <div>
                                            <div class="font-medium text-foreground">{{ role.department?.name || 'Unknown Department' }}</div>
                                            <div class="text-sm text-muted-foreground">{{ role.department?.code || 'N/A' }}</div>
                                            <div v-if="role.department?.parent" class="text-xs text-muted-foreground mt-1">Parent: {{ role.department.parent }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Role Details Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Role Details</CardTitle>
                            <CardDescription>Specific configuration and settings for this role</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Role Type</div>
                                    <Badge :variant="getRoleTypeVariant(role.role_type)">
                                        {{ role.role_display || 'Unknown Role' }}
                                    </Badge>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Authority Level</div>
                                    <Badge :variant="getAuthorityVariant(role.authority_level)">
                                        {{ getAuthorityLabel(role.authority_level) }}
                                    </Badge>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Primary Role</div>
                                    <div>
                                        <Badge v-if="role.is_primary" variant="default">
                                            <Crown class="mr-1 h-3 w-3" />
                                            Yes
                                        </Badge>
                                        <span v-else class="text-sm text-muted-foreground">No</span>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Status</div>
                                    <Badge :variant="role.is_active ? 'default' : 'destructive'">
                                        {{ role.is_active ? 'Active' : 'Terminated' }}
                                    </Badge>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Start Date</div>
                                    <div class="text-sm text-foreground">{{ role.start_date || 'N/A' }}</div>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">End Date</div>
                                    <div class="text-sm text-foreground">{{ role.end_date || 'Permanent' }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Managed User Card -->
                    <Card v-if="role.managed_user">
                        <CardHeader>
                            <CardTitle>Direct Report</CardTitle>
                            <CardDescription>Employee directly managed by this role</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center space-x-4">
                                <Avatar class="h-12 w-12">
                                    <AvatarFallback class="bg-green-100 text-green-600">
                                        {{ role.managed_user.name.charAt(0).toUpperCase() }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <div class="font-medium text-foreground">{{ role.managed_user.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ role.managed_user.email }}</div>
                                    <div v-if="role.managed_user.level" class="text-xs text-muted-foreground mt-1">{{ role.managed_user.level }}</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-else>
                        <CardHeader>
                            <CardTitle>Management Scope</CardTitle>
                            <CardDescription>Area of responsibility for this management role</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center space-x-3">
                                <Building class="w-6 h-6 text-muted-foreground" />
                                <span class="text-sm text-muted-foreground">Department-wide management role</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Notes Card -->
                    <Card v-if="role.notes">
                        <CardHeader>
                            <CardTitle>Notes</CardTitle>
                            <CardDescription>Additional information and comments</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ role.notes }}</p>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                            <CardDescription>Common actions for this role</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <Button
                                v-if="role.id"
                                :as="Link"
                                :href="route('admin.manager-roles.edit', role.id)"
                                variant="outline"
                                class="w-full justify-start"
                            >
                                <Edit class="mr-3 h-4 w-4" />
                                Edit Role
                            </Button>

                            <Button
                                :as="Link"
                                :href="route('admin.manager-roles.index')"
                                variant="outline"
                                class="w-full justify-start"
                            >
                                <List class="mr-3 h-4 w-4" />
                                View All Roles
                            </Button>

                            <Button
                                :as="Link"
                                :href="route('admin.manager-roles.matrix')"
                                variant="outline"
                                class="w-full justify-start"
                            >
                                <Grid3X3 class="mr-3 h-4 w-4" />
                                Matrix View
                            </Button>

                            <Separator />

                            <AlertDialog v-if="role.is_active">
                                <AlertDialogTrigger asChild>
                                    <Button variant="outline" class="w-full justify-start text-destructive hover:text-destructive">
                                        <Trash2 class="mr-3 h-4 w-4" />
                                        Terminate Role
                                    </Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Terminate Manager Role</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            Are you sure you want to terminate this manager role? This action cannot be undone and will permanently remove the management assignment.
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
                        </CardContent>
                    </Card>

                    <!-- Role Metadata -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Role Information</CardTitle>
                            <CardDescription>System metadata and tracking details</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-1">
                                <div class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Created By</div>
                                <div class="text-sm text-foreground">{{ role.created_by || 'Unknown' }}</div>
                            </div>

                            <Separator />

                            <div class="space-y-1">
                                <div class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Created At</div>
                                <div class="text-sm text-foreground">{{ role.created_at || 'N/A' }}</div>
                            </div>

                            <Separator />

                            <div v-if="role.start_date" class="space-y-1">
                                <div class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Duration</div>
                                <div class="text-sm text-foreground">
                                    {{ role.end_date ? `${role.start_date} to ${role.end_date}` : `Since ${role.start_date}` }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Loading/Error State -->
            <Card v-else>
                <CardContent class="p-12 text-center">
                    <AlertTriangle class="mx-auto h-12 w-12 text-muted-foreground" />
                    <h3 class="mt-2 text-sm font-medium text-foreground">Role not found</h3>
                    <p class="mt-1 text-sm text-muted-foreground">The requested manager role could not be loaded.</p>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
