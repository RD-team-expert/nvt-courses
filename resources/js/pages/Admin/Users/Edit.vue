<!--
  Edit User Page
  Interface for editing existing user information, roles, and passwords
-->
<script setup lang="ts">
import { defineProps } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
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
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import {
    Edit3,
    User,
    Mail,
    Shield,
    Lock,
    X,
    Loader2,
    Eye,
    AlertCircle
} from 'lucide-vue-next'

const props = defineProps({
    user: Object,
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Users', href: route('admin.users.index') },
    { name: 'Edit User', href: route('admin.users.edit', props.user.id) }
]

// Initialize the form with existing user data
const form = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role || 'user',
    password: '',
    password_confirmation: '',
})

// Handle role selection
const handleRoleChange = (value: string) => {
    form.role = value
}

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

function submit() {
    form.put(`/admin/users/${props.user.id}`)
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-foreground">Edit User</h1>
                <p class="text-sm text-muted-foreground mt-1">Update user information, role, and password</p>
            </div>

            <!-- Current User Preview -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Eye class="mr-2 h-5 w-5" />
                        Current User Information
                    </CardTitle>
                    <CardDescription>Review the current user details before making changes</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center space-x-4">
                        <Avatar class="h-16 w-16">
                            <AvatarFallback class="bg-muted text-muted-foreground text-lg font-semibold">
                                {{ getUserInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="flex-1">
                            <div class="text-lg font-semibold text-foreground">{{ user.name }}</div>
                            <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                            <div class="flex items-center space-x-2 mt-2">
                                <Badge :variant="getRoleVariant(user.role)">{{ user.role }}</Badge>
                                <span class="text-xs text-muted-foreground">User ID: {{ user.id }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Edit Form -->
            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <Edit3 class="mr-2 h-5 w-5" />
                        Edit User Information
                    </CardTitle>
                    <CardDescription>Update the user's details and system permissions</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name Field -->
                        <div class="space-y-2">
                            <Label for="name" class="flex items-center">
                                <User class="mr-2 h-4 w-4" />
                                Name
                            </Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter full name"
                                :disabled="form.processing"
                                required
                            />
                            <div v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</div>
                        </div>

                        <!-- Email Field -->
                        <div class="space-y-2">
                            <Label for="email" class="flex items-center">
                                <Mail class="mr-2 h-4 w-4" />
                                Email Address
                            </Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="Enter email address"
                                :disabled="form.processing"
                                required
                            />
                            <div v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</div>
                        </div>

                        <!-- Role Field -->
                        <div class="space-y-2">
                            <Label for="role" class="flex items-center">
                                <Shield class="mr-2 h-4 w-4" />
                                User Role
                            </Label>
                            <Select :model-value="form.role" @update:model-value="handleRoleChange">
                                <SelectTrigger id="role" :disabled="form.processing">
                                    <SelectValue placeholder="Select user role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="user">User</SelectItem>
                                    <SelectItem value="admin">Administrator</SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground">
                                Users have basic access, Administrators have full system access
                            </p>
                            <div v-if="form.errors.role" class="text-sm text-destructive">{{ form.errors.role }}</div>
                        </div>

                        <!-- Password Section -->
                        <div class="space-y-4 p-4 border rounded-lg bg-muted/20">
                            <div class="flex items-center space-x-2">
                                <AlertCircle class="h-4 w-4 text-amber-500" />
                                <h3 class="text-sm font-medium">Password Change</h3>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Leave password fields blank if you don't want to change the current password
                            </p>

                            <!-- Password Field -->
                            <div class="space-y-2">
                                <Label for="password" class="flex items-center">
                                    <Lock class="mr-2 h-4 w-4" />
                                    New Password
                                </Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    placeholder="Enter new password (optional)"
                                    :disabled="form.processing"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Password should be at least 8 characters long with mixed case letters, numbers, and symbols
                                </p>
                                <div v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</div>
                            </div>

                            <!-- Confirm Password Field -->
                            <div class="space-y-2">
                                <Label for="password_confirmation" class="flex items-center">
                                    <Lock class="mr-2 h-4 w-4" />
                                    Confirm New Password
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    placeholder="Confirm new password"
                                    :disabled="form.processing"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Re-enter the new password to confirm it matches
                                </p>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full sm:w-auto"
                            >
                                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                <Edit3 v-else class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Updating User...' : 'Update User' }}
                            </Button>
                            <Button
                                :as="Link"
                                href="/admin/users"
                                variant="outline"
                                :disabled="form.processing"
                                class="w-full sm:w-auto"
                            >
                                <X class="mr-2 h-4 w-4" />
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Help Information -->
            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle class="text-base">Edit Guidelines</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-primary rounded-full mt-2 flex-shrink-0"></div>
                        <div>
                            <p class="text-sm font-medium">Profile Updates</p>
                            <p class="text-xs text-muted-foreground">Changes to name and email will be reflected immediately across the system</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-primary rounded-full mt-2 flex-shrink-0"></div>
                        <div>
                            <p class="text-sm font-medium">Role Changes</p>
                            <p class="text-xs text-muted-foreground">Role modifications affect user permissions and system access levels</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-primary rounded-full mt-2 flex-shrink-0"></div>
                        <div>
                            <p class="text-sm font-medium">Password Security</p>
                            <p class="text-xs text-muted-foreground">Only provide new password if user requested a change or security requires it</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
