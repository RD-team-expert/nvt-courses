<!--
  Edit User Level Page
  Interface for editing organizational hierarchy levels and their management permissions
-->
<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref } from 'vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
    Save,
    X,
    Loader2,
    Users,
    Shield,
    Eye,
    Edit3
} from 'lucide-vue-next'

const props = defineProps({
    userLevel: Object,
    existingLevels: Array,
})

const form = useForm({
    code: props.userLevel.code,
    name: props.userLevel.name,
    hierarchy_level: props.userLevel.hierarchy_level,
    description: props.userLevel.description,
    can_manage_levels: props.userLevel.can_manage_levels || [],
})

function submit() {
    form.put(route('admin.user-levels.update', props.userLevel.id), {
        onSuccess: () => {
            // Will redirect automatically on success
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        }
    })
}

// Handle select changes
const handleHierarchyChange = (value: string) => {
    form.hierarchy_level = value
}

// Toggle manageable level
const toggleManageableLevel = (levelCode: string) => {
    const index = form.can_manage_levels.indexOf(levelCode)
    if (index > -1) {
        form.can_manage_levels.splice(index, 1)
    } else {
        form.can_manage_levels.push(levelCode)
    }
}

// Get level color scheme
const getLevelColorScheme = (level: number | string) => {
    const lvl = parseInt(level.toString())
    switch (lvl) {
        case 1: return { bg: 'bg-blue-100', text: 'text-blue-600', border: 'border-blue-200' }
        case 2: return { bg: 'bg-green-100', text: 'text-green-600', border: 'border-green-200' }
        case 3: return { bg: 'bg-orange-100', text: 'text-orange-600', border: 'border-orange-200' }
        case 4: return { bg: 'bg-red-100', text: 'text-red-600', border: 'border-red-200' }
        case 5:return { bg: 'bg-indigo-100', text: 'text-indigo-600', variant: 'secondary' }
        case 6:  return { bg: 'bg-pink-100', text: 'text-pink-600', variant: 'secondary' }
        default: return { bg: 'bg-purple-100', text: 'text-purple-600', border: 'border-purple-200' }
    }
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'User Levels', href: route('admin.user-levels.index') },
    { name: props.userLevel.name, href: route('admin.user-levels.show', props.userLevel.id) },
    { name: 'Edit', href: route('admin.user-levels.edit', props.userLevel.id) }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-foreground">Edit User Level: {{ userLevel.name }}</h1>
                <p class="text-sm text-muted-foreground mt-1">Modify organizational hierarchy level properties and management permissions</p>
            </div>

            <form @submit.prevent="submit" class="max-w-4xl space-y-6">
                <!-- Main Form Fields -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Edit3 class="mr-2 h-5 w-5" />
                            Level Configuration
                        </CardTitle>
                        <CardDescription>Update the basic properties and hierarchy position of this user level</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Hierarchy Level -->
                            <div class="space-y-2">
                                <Label for="hierarchy_level">Hierarchy Level</Label>
                                <Select :model-value="form.hierarchy_level.toString()" @update:model-value="handleHierarchyChange">
                                    <SelectTrigger id="hierarchy_level" :disabled="form.processing">
                                        <SelectValue placeholder="Select Level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Level 1 (Employees)</SelectItem>
                                        <SelectItem value="2">Level 2 (Managers)</SelectItem>
                                        <SelectItem value="3">Level 3 (Senior Managers)</SelectItem>
                                        <SelectItem value="4">Level 4 (Directors)</SelectItem>
                                        <SelectItem value="5">Level 5 (Executive)</SelectItem>
                                        <SelectItem value="6">Level 6 (Business Owners)</SelectItem>

                                    </SelectContent>
                                </Select>
                                <p class="text-xs text-muted-foreground">Higher numbers indicate higher authority levels</p>
                                <div v-if="form.errors.hierarchy_level" class="text-sm text-destructive">{{ form.errors.hierarchy_level }}</div>
                            </div>

                            <!-- Level Code -->
                            <div class="space-y-2">
                                <Label for="code">Level Code</Label>
                                <Input
                                    id="code"
                                    v-model="form.code"
                                    :disabled="form.processing"
                                    maxlength="10"
                                    placeholder="e.g., L1, L2, MGR"
                                    required
                                />
                                <p class="text-xs text-muted-foreground">Short identifier (e.g., L1, L2, L3, L4)</p>
                                <div v-if="form.errors.code" class="text-sm text-destructive">{{ form.errors.code }}</div>
                            </div>

                            <!-- Level Name -->
                            <div class="space-y-2 md:col-span-2">
                                <Label for="name">Level Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    :disabled="form.processing"
                                    placeholder="e.g., Employee, Direct Manager, Senior Manager"
                                    required
                                />
                                <div v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</div>
                            </div>

                            <!-- Description -->
                            <div class="space-y-2 md:col-span-2">
                                <Label for="description">Description</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    :disabled="form.processing"
                                    placeholder="Describe the responsibilities and scope of this level"
                                    rows="3"
                                />
                                <div v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Current Level Preview -->
                <Card class="border-primary/20 bg-primary/5">
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Eye class="mr-2 h-5 w-5" />
                            Current Level
                        </CardTitle>
                        <CardDescription>Current state of this user level in the system</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center space-x-4">
                            <Avatar class="h-16 w-16">
                                <AvatarFallback
                                    :class="[getLevelColorScheme(form.hierarchy_level).bg, getLevelColorScheme(form.hierarchy_level).text]"
                                    class="text-lg font-bold"
                                >
                                    {{ form.code }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1">
                                <div class="text-lg font-semibold text-foreground">{{ form.name }}</div>
                                <div class="text-sm text-muted-foreground">Hierarchy Level {{ form.hierarchy_level }}</div>
                                <div v-if="form.description" class="text-sm text-muted-foreground mt-1">{{ form.description }}</div>
                                <div v-if="form.can_manage_levels.length > 0" class="flex flex-wrap gap-1 mt-2">
                                    <Badge v-for="level in form.can_manage_levels" :key="level" variant="secondary" class="text-xs">
                                        Can manage: {{ level }}
                                    </Badge>
                                </div>
                                <div v-if="userLevel.users_count" class="mt-2">
                                    <Badge variant="outline">{{ userLevel.users_count }} users assigned</Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Management Permissions -->
                <Card v-if="existingLevels.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Users class="mr-2 h-5 w-5" />
                            Management Permissions
                        </CardTitle>
                        <CardDescription>Select which levels this level can manage and oversee</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="level in existingLevels"
                                    :key="level.code"
                                    class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-muted/50 transition-colors"
                                    :class="{ 'opacity-50': level.id === userLevel.id }"
                                >
                                    <Checkbox
                                        :id="`manage_${level.code}`"
                                        :checked="form.can_manage_levels.includes(level.code)"
                                        @update:checked="(checked) => checked ? form.can_manage_levels.push(level.code) : toggleManageableLevel(level.code)"
                                        :disabled="form.processing || level.id === userLevel.id"
                                    />
                                    <Label :for="`manage_${level.code}`" class="flex-1 cursor-pointer">
                                        <div class="font-medium">{{ level.code }} - {{ level.name }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            Level {{ level.hierarchy_level }}
                                            <span v-if="level.id === userLevel.id" class="text-amber-600">(Current Level)</span>
                                        </div>
                                    </Label>
                                </div>
                            </div>

                            <p class="text-xs text-muted-foreground">Leave empty if this level cannot manage other users. A level cannot manage itself.</p>
                            <div v-if="form.errors.can_manage_levels" class="text-sm text-destructive">{{ form.errors.can_manage_levels }}</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto"
                    >
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <Save v-else class="mr-2 h-4 w-4" />
                        <span v-if="form.processing">Updating Level...</span>
                        <span v-else>Update User Level</span>
                    </Button>
                    <Button
                        :as="Link"
                        :href="route('admin.user-levels.show', userLevel.id)"
                        variant="outline"
                        :disabled="form.processing"
                        class="w-full sm:w-auto"
                    >
                        <X class="mr-2 h-4 w-4" />
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
