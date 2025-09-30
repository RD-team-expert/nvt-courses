<!--
  User Details Page
  Display comprehensive user information with edit and delete capabilities
-->
<script setup lang="ts">
import { defineProps } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
    ArrowLeft,
    User,
    Edit,
    Trash2,
    Mail,
    Shield,
    Calendar,
    Clock
} from 'lucide-vue-next'

const props = defineProps({
    user: Object,
})

// Get user initials for avatar
const getUserInitials = (name: string) => {
    return name.split(' ').map(n => n.charAt(0).toUpperCase()).join('').slice(0, 2)
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

// Format date with better readability
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Users', href: route('admin.users.index') },
    { name: props.user.name, href: route('admin.users.show', props.user.id) }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Back Navigation -->
            <Button :as="Link" href="/admin/users" variant="ghost" class="p-0">
                <ArrowLeft class="mr-2 h-4 w-4" />
                Back to Users
            </Button>

            <!-- User Profile Header -->
            <Card>
                <CardContent class="p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                        <div class="flex items-center space-x-4">
                            <Avatar class="h-16 w-16">
                                <AvatarFallback class="bg-primary text-primary-foreground text-xl font-bold">
                                    {{ getUserInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <h1 class="text-2xl font-bold text-foreground">{{ user.name }}</h1>
                                <p class="text-muted-foreground flex items-center">
                                    <Mail class="mr-1 h-4 w-4" />
                                    {{ user.email }}
                                </p>
                                <div class="mt-2">
                                    <Badge :variant="getRoleVariant(user.role)">
                                        <Shield class="mr-1 h-3 w-3" />
                                        {{ user.role }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                        <Button :as="Link" :href="`/admin/users/${user.id}/edit`">
                            <Edit class="mr-2 h-4 w-4" />
                            Edit User
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- User Information Details -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <User class="mr-2 h-5 w-5" />
                        User Information
                    </CardTitle>
                    <CardDescription>Personal details and account information</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <div class="text-sm font-medium text-muted-foreground">Full Name</div>
                                <div class="text-foreground font-medium">{{ user.name }}</div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-sm font-medium text-muted-foreground">Email Address</div>
                                <div class="text-foreground font-medium flex items-center">
                                    <Mail class="mr-2 h-4 w-4 text-muted-foreground" />
                                    {{ user.email }}
                                </div>
                            </div>
                        </div>

                        <!-- Role Information -->
                        <div class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">User Role</div>
                            <Badge :variant="getRoleVariant(user.role)" class="w-fit">
                                <Shield class="mr-1 h-3 w-3" />
                                {{ user.role }}
                            </Badge>
                        </div>

                        <!-- Account Timestamps -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <div class="text-sm font-medium text-muted-foreground">Account Created</div>
                                <div class="text-foreground flex items-center">
                                    <Calendar class="mr-2 h-4 w-4 text-muted-foreground" />
                                    {{ formatDate(user.created_at) }}
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="text-sm font-medium text-muted-foreground">Last Updated</div>
                                <div class="text-foreground flex items-center">
                                    <Clock class="mr-2 h-4 w-4 text-muted-foreground" />
                                    {{ formatDate(user.updated_at) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Action Buttons -->
            <Card>
                <CardHeader>
                    <CardTitle>Account Actions</CardTitle>
                    <CardDescription>Manage this user account and settings</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <Button :as="Link" :href="`/admin/users/${user.id}/edit`" variant="outline">
                            <Edit class="mr-2 h-4 w-4" />
                            Edit User
                        </Button>

                        <AlertDialog>
                            <AlertDialogTrigger asChild>
                                <Button variant="destructive">
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Delete User
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>Delete User Account</AlertDialogTitle>
                                    <AlertDialogDescription>
                                        Are you sure you want to delete <strong>{{ user.name }}</strong>'s account?
                                        This action cannot be undone and will permanently remove all user data,
                                        including their profile, settings, and associated records.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                    <AlertDialogAction
                                        :as="Link"
                                        :href="`/admin/users/${user.id}`"
                                        method="delete"
                                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                                    >
                                        Delete User
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    </div>
                </CardContent>
            </Card>

            <!-- User Statistics (Optional Enhancement) -->
            <Card>
                <CardHeader>
                    <CardTitle>Account Statistics</CardTitle>
                    <CardDescription>Account activity and usage information</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-4 border rounded-lg">
                            <div class="text-2xl font-bold text-primary">{{ user.id }}</div>
                            <div class="text-sm text-muted-foreground">User ID</div>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <div class="text-2xl font-bold text-green-600">Active</div>
                            <div class="text-sm text-muted-foreground">Account Status</div>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ Math.floor((new Date() - new Date(user.created_at)) / (1000 * 60 * 60 * 24)) }}
                            </div>
                            <div class="text-sm text-muted-foreground">Days Since Joined</div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
