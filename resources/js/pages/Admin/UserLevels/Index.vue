<!--
  User Level Management Page
  Interface for managing organizational hierarchy levels and their permissions
-->
<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { ref } from 'vue'
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
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog'
import {
    Plus,
    Tag,
    Users,
    UserCheck,
    Eye,
    Edit,
    Trash2,
    Shield
} from 'lucide-vue-next'

const props = defineProps({
    userLevels: Array,
    stats: Object,
})

const showDeleteDialog = ref(false)
const levelToDelete = ref(null)

// Delete user level
const deleteUserLevel = (levelId: number) => {
    router.delete(route('admin.user-levels.destroy', levelId), {
        preserveState: true,
        onSuccess: () => {
            // Success handled by redirect
        },
        onError: (errors) => {
            console.error('Delete failed:', errors)
            // Error handling can be improved with toast notifications
        }
    })
}

// Get level color scheme
const getLevelColorScheme = (level: number) => {
    switch (level) {
        case 1: return { bg: 'bg-blue-100', text: 'text-blue-600', variant: 'secondary' }
        case 2: return { bg: 'bg-green-100', text: 'text-green-600', variant: 'secondary' }
        case 3: return { bg: 'bg-orange-100', text: 'text-orange-600', variant: 'outline' }
        case 4: return { bg: 'bg-red-100', text: 'text-red-600', variant: 'destructive' }
        default: return { bg: 'bg-purple-100', text: 'text-purple-600', variant: 'secondary' }
    }
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Organizational Management', href: route('admin.user-levels.index') },
    { name: 'User Levels', href: route('admin.user-levels.index') }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">User Level Management</h1>
                    <p class="text-sm text-muted-foreground mt-1">Manage organizational hierarchy levels and their permissions</p>
                </div>

                <Button :as="Link" :href="route('admin.user-levels.create')" class="w-full sm:w-auto">
                    <Plus class="mr-2 h-4 w-4" />
                    Add New Level
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                                    <Tag class="h-5 w-5 text-primary-foreground" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Total Levels</div>
                                <div class="text-2xl font-bold text-foreground">{{ stats.total_levels || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <Shield class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Management Levels</div>
                                <div class="text-2xl font-bold text-foreground">{{ stats.management_levels || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <UserCheck class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="text-sm font-medium text-muted-foreground">Users Assigned</div>
                                <div class="text-2xl font-bold text-foreground">{{ stats.total_users_assigned || 0 }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- User Levels Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Users class="mr-2 h-5 w-5" />
                        Organizational Levels
                    </CardTitle>
                    <CardDescription>Manage hierarchy levels and their management permissions</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Level</TableHead>
                                    <TableHead>Hierarchy</TableHead>
                                    <TableHead>Can Manage</TableHead>
                                    <TableHead>Users Count</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="level in userLevels" :key="level.id" class="hover:bg-muted/50">
                                    <TableCell>
                                        <div class="flex items-center space-x-3">
                                            <Avatar class="h-10 w-10">
                                                <AvatarFallback
                                                    :class="[getLevelColorScheme(level.hierarchy_level).bg, getLevelColorScheme(level.hierarchy_level).text]"
                                                    class="font-bold text-sm"
                                                >
                                                    {{ level.code }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium text-foreground">{{ level.name }}</div>
                                                <div class="text-sm text-muted-foreground">{{ level.description }}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            Level {{ level.hierarchy_level }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="level.can_manage_levels && level.can_manage_levels.length > 0" class="flex flex-wrap gap-1">
                                            <Badge v-for="managedLevel in level.can_manage_levels" :key="managedLevel" variant="outline" class="text-xs">
                                                {{ managedLevel }}
                                            </Badge>
                                        </div>
                                        <div v-else class="text-sm text-muted-foreground">None</div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">{{ level.users_count }} users</Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <Button
                                                :as="Link"
                                                :href="route('admin.user-levels.show', level.id)"
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                :as="Link"
                                                :href="route('admin.user-levels.edit', level.id)"
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        :disabled="level.users_count > 0"
                                                        class="text-destructive hover:text-destructive hover:bg-destructive/10"
                                                    >
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>Delete User Level</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            Are you sure you want to delete the "{{ level.name }}" level? This action cannot be undone.
                                                            <div v-if="level.users_count > 0" class="mt-2 p-2 bg-destructive/10 text-destructive text-sm rounded">
                                                                <strong>Warning:</strong> This level has {{ level.users_count }} users assigned and cannot be deleted.
                                                            </div>
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction
                                                            @click="deleteUserLevel(level.id)"
                                                            :disabled="level.users_count > 0"
                                                            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                        >
                                                            Delete Level
                                                        </AlertDialogAction>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Empty state -->
                        <div v-if="!userLevels || userLevels.length === 0" class="text-center py-12">
                            <Tag class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 class="text-lg font-medium text-foreground mb-2">No user levels found</h3>
                            <p class="text-sm text-muted-foreground mb-6">Get started by creating organizational levels (L1, L2, L3, L4).</p>
                            <Button :as="Link" :href="route('admin.user-levels.create')">
                                <Plus class="mr-2 h-4 w-4" />
                                Add New Level
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
