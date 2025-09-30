<!--
  Create User Level Page
  Interface for creating new organizational hierarchy levels with management permissions
-->
<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref } from 'vue'
import { type BreadcrumbItemType } from '@/types/index'
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
import {
    Save,
    X,
    Loader2,
    Users,
    Shield,
    Eye,
    Lightbulb
} from 'lucide-vue-next'

const props = defineProps({
    existingLevels: Array,
})

const form = useForm({
    code: '',
    name: '',
    hierarchy_level: '',
    description: '',
    can_manage_levels: [],
})

const availableLevels = ref(props.existingLevels || [])

function submit() {
    form.post('/admin/user-levels', {
        onSuccess: () => {
            // Will redirect automatically on success
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
        }
    })
}

// Generate suggestions based on hierarchy level
const generateSuggestions = () => {
    const level = parseInt(form.hierarchy_level)

    if (level === 1) {
        form.code = 'L1'
        form.name = 'Employee'
        form.description = 'Front-line employees with no direct reports'
        form.can_manage_levels = []
    } else if (level === 2) {
        form.code = 'L2'
        form.name = 'Direct Manager'
        form.description = 'Team managers with direct reports'
        form.can_manage_levels = ['L1']
    } else if (level === 3) {
        form.code = 'L3'
        form.name = 'Senior Manager'
        form.description = 'Department heads and senior managers'
        form.can_manage_levels = ['L1', 'L2']
    } else if (level === 4) {
        form.code = 'L4'
        form.name = 'Director'
        form.description = 'Executive level with multiple department oversight'
        form.can_manage_levels = ['L1', 'L2', 'L3']
    }
}

// Handle select changes
const handleHierarchyChange = (value: string) => {
    form.hierarchy_level = value
    generateSuggestions()
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
        default: return { bg: 'bg-purple-100', text: 'text-purple-600', border: 'border-purple-200' }
    }
}

// Quick setup template data
const quickSetupTemplates = [
    { level: '1', code: 'L1', name: 'Employee Level', description: 'Front-line employees' },
    { level: '2', code: 'L2', name: 'Manager Level', description: 'Team managers' },
    { level: '3', code: 'L3', name: 'Senior Manager', description: 'Department heads' },
    { level: '4', code: 'L4', name: 'Director Level', description: 'Executive level' }
]

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'User Levels', href: route('admin.user-levels.index') },
    { name: 'Create Level', href: route('admin.user-levels.create') }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-foreground">Create New User Level</h1>
                <p class="text-sm text-muted-foreground mt-1">Define a new organizational hierarchy level with management permissions and responsibilities</p>
            </div>

            <form @submit.prevent="submit" class="max-w-4xl space-y-6">
                <!-- Main Form Fields -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Shield class="mr-2 h-5 w-5" />
                            Level Configuration
                        </CardTitle>
                        <CardDescription>Define the basic properties and hierarchy position of this user level</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Hierarchy Level -->
                            <div class="space-y-2">
                                <Label for="hierarchy_level">Hierarchy Level</Label>
                                <Select :model-value="form.hierarchy_level" @update:model-value="handleHierarchyChange">
                                    <SelectTrigger id="hierarchy_level" :disabled="form.processing">
                                        <SelectValue placeholder="Select Level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Level 1 (Employees)</SelectItem>
                                        <SelectItem value="2">Level 2 (Managers)</SelectItem>
                                        <SelectItem value="3">Level 3 (Senior Managers)</SelectItem>
                                        <SelectItem value="4">Level 4 (Directors)</SelectItem>
                                        <SelectItem value="5">Level 5 (Executive)</SelectItem>
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

                <!-- Management Permissions -->
                <Card v-if="availableLevels.length > 0">
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
                                <div v-for="level in availableLevels" :key="level.code" class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-muted/50 transition-colors">
                                    <Checkbox
                                        :id="`manage_${level.code}`"
                                        :checked="form.can_manage_levels.includes(level.code)"
                                        @update:checked="(checked) => checked ? form.can_manage_levels.push(level.code) : toggleManageableLevel(level.code)"
                                        :disabled="form.processing"
                                    />
                                    <Label :for="`manage_${level.code}`" class="flex-1 cursor-pointer">
                                        <div class="font-medium">{{ level.code }} - {{ level.name }}</div>
                                        <div class="text-xs text-muted-foreground">Level {{ level.hierarchy_level }}</div>
                                    </Label>
                                </div>
                            </div>

                            <p class="text-xs text-muted-foreground">Leave empty if this level cannot manage other users</p>
                            <div v-if="form.errors.can_manage_levels" class="text-sm text-destructive">{{ form.errors.can_manage_levels }}</div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Preview Card -->
                <Card v-if="form.code" class="border-primary/20 bg-primary/5">
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Eye class="mr-2 h-5 w-5" />
                            Preview
                        </CardTitle>
                        <CardDescription>How this level will appear in the system</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center space-x-4">
                            <div
                                class="shrink-0 h-16 w-16 rounded-full flex items-center justify-center border-2"
                                :class="[getLevelColorScheme(form.hierarchy_level).bg, getLevelColorScheme(form.hierarchy_level).text, getLevelColorScheme(form.hierarchy_level).border]"
                            >
                                <span class="text-lg font-bold">{{ form.code }}</span>
                            </div>
                            <div class="flex-1">
                                <div class="text-lg font-semibold text-foreground">{{ form.name || 'Level Name' }}</div>
                                <div class="text-sm text-muted-foreground">Hierarchy Level {{ form.hierarchy_level || '?' }}</div>
                                <div v-if="form.description" class="text-sm text-muted-foreground mt-1">{{ form.description }}</div>
                                <div v-if="form.can_manage_levels.length > 0" class="flex flex-wrap gap-1 mt-2">
                                    <Badge v-for="level in form.can_manage_levels" :key="level" variant="secondary" class="text-xs">
                                        Can manage: {{ level }}
                                    </Badge>
                                </div>
                            </div>
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
                        <span v-if="form.processing">Creating Level...</span>
                        <span v-else>Create User Level</span>
                    </Button>
                    <Button
                        :as="Link"
                        :href="route('admin.user-levels.index')"
                        variant="outline"
                        :disabled="form.processing"
                        class="w-full sm:w-auto"
                    >
                        <X class="mr-2 h-4 w-4" />
                        Cancel
                    </Button>
                </div>

                <!-- Quick Setup Templates -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Lightbulb class="mr-2 h-5 w-5" />
                            Quick Setup Templates
                        </CardTitle>
                        <CardDescription>Click on a template to quickly configure common organizational levels</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <Card
                                v-for="template in quickSetupTemplates"
                                :key="template.level"
                                class="cursor-pointer transition-all duration-200 hover:shadow-md hover:scale-105 border-2 hover:border-primary/50"
                                @click="handleHierarchyChange(template.level)"
                            >
                                <CardContent class="p-4">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="h-10 w-10 rounded-full flex items-center justify-center text-xs font-bold"
                                            :class="[getLevelColorScheme(template.level).bg, getLevelColorScheme(template.level).text]"
                                        >
                                            {{ template.code }}
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium text-sm">{{ template.name }}</div>
                                            <div class="text-xs text-muted-foreground">{{ template.description }}</div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </div>
    </AdminLayout>
</template>
