<!--
  Create User Page
  Interface for creating new users with role assignment and validation
-->
<script setup lang="ts">
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
import {
    UserPlus,
    User,
    Mail,
    Shield,
    Lock,
    X,
    Loader2
} from 'lucide-vue-next'

const form = useForm({
    name: '',
    email: '',
    role: 'user',
    password: '',
    password_confirmation: '',
})

// Handle role selection
const handleRoleChange = (value: string) => {
    form.role = value
}

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Users', href: route('admin.users.index') },
    { name: 'Create User', href: route('admin.users.create') }
]

function submit() {
    form.post('/admin/users')
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-foreground">Create User</h1>
                <p class="text-sm text-muted-foreground mt-1">Add a new user to the system with appropriate role and permissions</p>
            </div>

            <!-- Form Card -->
            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle class="flex items-center">
                        <UserPlus class="mr-2 h-5 w-5" />
                        User Information
                    </CardTitle>
                    <CardDescription>Enter the user's details and assign their role in the system</CardDescription>
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

                        <!-- Password Field -->
                        <div class="space-y-2">
                            <Label for="password" class="flex items-center">
                                <Lock class="mr-2 h-4 w-4" />
                                Password
                            </Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="Enter secure password"
                                :disabled="form.processing"
                                required
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
                                Confirm Password
                            </Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="Confirm password"
                                :disabled="form.processing"
                                required
                            />
                            <p class="text-xs text-muted-foreground">
                                Re-enter the password to confirm it matches
                            </p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t">
                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full sm:w-auto"
                            >
                                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                <UserPlus v-else class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Creating User...' : 'Create User' }}
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

            <!-- Helper Information -->
            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle class="text-base">User Creation Guidelines</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-primary rounded-full mt-2 flex-shrink-0"></div>
                        <div>
                            <p class="text-sm font-medium">Email Verification</p>
                            <p class="text-xs text-muted-foreground">New users will receive an email verification link upon account creation</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-primary rounded-full mt-2 flex-shrink-0"></div>
                        <div>
                            <p class="text-sm font-medium">Role Permissions</p>
                            <p class="text-xs text-muted-foreground">User roles determine access levels and available features within the system</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 bg-primary rounded-full mt-2 flex-shrink-0"></div>
                        <div>
                            <p class="text-sm font-medium">Password Security</p>
                            <p class="text-xs text-muted-foreground">Passwords are encrypted and stored securely. Users can change their passwords after login</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
