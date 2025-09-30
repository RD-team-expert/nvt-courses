<!--
  User Management Index Page
  Interface for listing, managing, and paginating users with CRUD operations
-->
<script setup lang="ts">
import { defineProps } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import Pagination from '@/components/Pagination.vue'
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
    Users,
    UserPlus,
    Edit,
    Trash2,
    ChevronLeft,
    ChevronRight
} from 'lucide-vue-next'

const props = defineProps({
    users: Object, // Changed from Array to Object to support pagination
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'User Management', href: route('admin.users.index') }
]

function confirmDelete(e) {
    if (!confirm('Are you sure you want to delete this user?')) {
        e.preventDefault()
    }
}

// Handle delete user with confirmation
const deleteUser = (userId: number) => {
    router.delete(`/admin/users/${userId}`, {
        preserveState: true,
        onSuccess: () => {
            // Success handling can be improved with toast notifications
        },
        onError: (errors) => {
            console.error('Delete failed:', errors)
            // Error handling can be improved with toast notifications
        }
    })
}

// Handle pagination
const handlePageChange = (page) => {
    router.get(route('admin.users.index'), {
        page
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    })
}

// Get role badge variant
const getRoleVariant = (role: string) => {
    switch (role?.toLowerCase()) {
        case 'admin': return 'default'
        case 'user': return 'secondary'
        case 'manager': return 'outline'
        default: return 'secondary'
    }
}

// Get user initials for avatar
const getUserInitials = (name: string) => {
    return name.split(' ').map(n => n.charAt(0).toUpperCase()).join('').slice(0, 2)
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">User Management</h1>
                    <p class="text-sm text-muted-foreground mt-1">Manage system users, roles, and permissions</p>
                </div>
                <Button :as="Link" href="/admin/users/create">
                    <UserPlus class="mr-2 h-4 w-4" />
                    Create New User
                </Button>
            </div>

            <!-- Users Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Users class="mr-2 h-5 w-5" />
                        System Users
                    </CardTitle>
                    <CardDescription>
                        Showing {{ props.users?.data?.length || 0 }} users
                        {{ props.users?.total ? `of ${props.users.total} total` : '' }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead class="hidden sm:table-cell">Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="user in users.data" :key="user.id" class="hover:bg-muted/50">
                                    <TableCell>
                                        <div class="flex items-center space-x-3">
                                            <Avatar class="h-8 w-8">
                                                <AvatarFallback class="bg-muted text-muted-foreground text-sm">
                                                    {{ getUserInitials(user.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium text-foreground">{{ user.name }}</div>
                                                <div class="text-xs text-muted-foreground sm:hidden mt-1">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="hidden sm:table-cell">
                                        <div class="text-muted-foreground">{{ user.email }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getRoleVariant(user.role)">
                                            {{ user.role }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end space-x-2">
                                            <Button
                                                :as="Link"
                                                :href="`/admin/users/${user.id}/edit`"
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
                                                        class="text-destructive hover:text-destructive hover:bg-destructive/10"
                                                    >
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>Delete User</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            Are you sure you want to delete <strong>{{ user.name }}</strong>? This action cannot be undone and will permanently remove the user from the system.
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                        <AlertDialogAction
                                                            @click="deleteUser(user.id)"
                                                            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                                        >
                                                            Delete User
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
                        <div v-if="!users.data || users.data.length === 0" class="text-center py-12">
                            <Users class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 class="text-lg font-medium text-foreground mb-2">No users found</h3>
                            <p class="text-sm text-muted-foreground mb-6">Get started by creating your first user</p>
                            <Button :as="Link" href="/admin/users/create">
                                <UserPlus class="mr-2 h-4 w-4" />
                                Create New User
                            </Button>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="users.data && users.data.length > 0" class="flex justify-between items-center pt-4 border-t">
                        <Button
                            v-if="users.prev_page_url"
                            :as="Link"
                            :href="users.prev_page_url"
                            variant="outline"
                            size="sm"
                            preserve-scroll
                        >
                            <ChevronLeft class="mr-2 h-4 w-4" />
                            Previous
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            disabled
                        >
                            <ChevronLeft class="mr-2 h-4 w-4" />
                            Previous
                        </Button>

                        <!-- Pagination info -->
                        <div class="text-sm text-muted-foreground hidden sm:block">
              <span v-if="users.from && users.to && users.total">
                Showing {{ users.from }} to {{ users.to }} of {{ users.total }} users
              </span>
                            <span v-else-if="users.data">
                {{ users.data.length }} users
              </span>
                        </div>

                        <Button
                            v-if="users.next_page_url"
                            :as="Link"
                            :href="users.next_page_url"
                            variant="outline"
                            size="sm"
                            preserve-scroll
                        >
                            Next
                            <ChevronRight class="ml-2 h-4 w-4" />
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            disabled
                        >
                            Next
                            <ChevronRight class="ml-2 h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Original pagination component (optional - you can remove if not needed) -->
                    <Pagination
                        v-if="false && users.data && users.data.length > 0"
                        :links="users.links"
                        @page-changed="handlePageChange"
                        class="mt-4"
                    />

                    <!-- Debug pagination data -->
                    <pre v-if="false" class="text-xs mt-2">{{ JSON.stringify(users, null, 2) }}</pre>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
