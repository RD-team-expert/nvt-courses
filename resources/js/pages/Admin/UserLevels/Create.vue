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

// ✅ Enhanced suggestions with Project Manager having hierarchy level 0
const generateSuggestions = () => {
    const level = form.hierarchy_level

    if (level === '0') {
        form.code = 'L2PM'
        form.name = 'Project Manager'
        form.description = 'Project coordinators with no direct management authority'
        form.can_manage_levels = []
    } else if (level === '1') {
        form.code = 'L1'
        form.name = 'Employee'
        form.description = 'Front-line employees with no direct reports'
        form.can_manage_levels = []
    } else if (level === '2') {
        form.code = 'L2'
        form.name = 'Direct Manager'
        form.description = 'Team managers with direct reports'
        form.can_manage_levels = ['L1']
    } else if (level === '3') {
        form.code = 'L3'
        form.name = 'Senior Manager'
        form.description = 'Department heads and senior managers'
        form.can_manage_levels = ['L1', 'L2']
    } else if (level === '4') {
        form.code = 'L4'
        form.name = 'Director'
        form.description = 'Executive level with multiple department oversight'
        form.can_manage_levels = ['L1', 'L2', 'L3']
    } else if (level === '5') {
        form.code = 'L5'
        form.name = 'Executive'
        form.description = 'C-level executives and senior leadership'
        form.can_manage_levels = ['L1', 'L2', 'L3', 'L4']
    }
}

// Handle select changes
const handleHierarchyChange = (value: string) => {
    form.hierarchy_level = value
    generateSuggestions()
}

// ✅ NEW: Apply template configuration
const applyTemplate = (template: any) => {
    form.hierarchy_level = template.level
    form.code = template.code
    form.name = template.name
    form.description = template.description
    form.can_manage_levels = template.can_manage_levels || []
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

// ✅ Updated color scheme to handle level 0
const getLevelColorScheme = (level: number | string) => {
    const lvl = level?.toString()
    switch (lvl) {
        case '0': return { bg: 'bg-purple-100', text: 'text-purple-600', border: 'border-purple-200' }
        case '1': return { bg: 'bg-blue-100', text: 'text-blue-600', border: 'border-blue-200' }
        case '2': return { bg: 'bg-green-100', text: 'text-green-600', border: 'border-green-200' }
        case '3': return { bg: 'bg-orange-100', text: 'text-orange-600', border: 'border-orange-200' }
        case '4': return { bg: 'bg-red-100', text: 'text-red-600', border: 'border-red-200' }
        case '5': return { bg: 'bg-indigo-100', text: 'text-indigo-600', border: 'border-indigo-200' }
        default: return { bg: 'bg-gray-100', text: 'text-gray-600', border: 'border-gray-200' }
    }
}

// ✅ Updated templates with Project Manager as level 0
const quickSetupTemplates = [
    {
        level: '0',
        code: 'L2PM',
        name: 'Project Manager',
        description: 'Project coordinators with no direct management authority',
        can_manage_levels: []
    },
    {
        level: '1',
        code: 'L1',
        name: 'Employee',
        description: 'Front-line employees with no direct reports',
        can_manage_levels: []
    },
    {
        level: '2',
        code: 'L2',
        name: 'Direct Manager',
        description: 'Team managers with direct reports',
        can_manage_levels: ['L1']
    },
    {
        level: '3',
        code: 'L3',
        name: 'Senior Manager',
        description: 'Department heads and senior managers',
        can_manage_levels: ['L1', 'L2']
    },
    {
        level: '4',
        code: 'L4',
        name: 'Director',
        description: 'Executive level with multiple department oversight',
        can_manage_levels: ['L1', 'L2', 'L3']
    },
    {
        level: '5',
        code: 'L5',
        name: 'Executive',
        description: 'C-level executives and senior leadership',
        can_manage_levels: ['L1', 'L2', 'L3', 'L4']
    }
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
                <!-- Quick Setup Templates - Moved to top for better UX -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center">
                            <Lightbulb class="mr-2 h-5 w-5" />
                            Quick Setup Templates
                        </CardTitle>
                        <CardDescription>Click on a template to quickly configure common organizational levels</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <Card
                                v-for="template in quickSetupTemplates"
                                :key="`${template.level}-${template.code}`"
                                class="cursor-pointer transition-all duration-200 hover:shadow-md hover:scale-105 border-2 hover:border-primary/50"
                                :class="{ 'border-primary bg-primary/5': form.code === template.code }"
                                @click="applyTemplate(template)"
                            >
                                <CardContent class="p-4">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="h-12 w-12 rounded-full flex items-center justify-center text-xs font-bold"
                                            :class="[getLevelColorScheme(template.level).bg, getLevelColorScheme(template.level).text]"
                                        >
                                            {{ template.code }}
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium text-sm">{{ template.name }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ template.level === '0' ? 'No Management Level' : `Level ${template.level}` }}
                                            </div>
                                            <div class="text-xs text-muted-foreground mt-1">{{ template.description }}</div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </CardContent>
                </Card>

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
                                        <SelectItem value="0">Level 0 (Project Manager - No Management Authority)</SelectItem>
                                        <SelectItem value="1">Level 1 (Employees)</SelectItem>
                                        <SelectItem value="2">Level 2 (Direct Managers)</SelectItem>
                                        <SelectItem value="3">Level 3 (Senior Managers)</SelectItem>
                                        <SelectItem value="4">Level 4 (Directors)</SelectItem>
                                        <SelectItem value="5">Level 5 (Executives)</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p class="text-xs text-muted-foreground">
                                    Level 0 = No management authority (Project Managers)<br>
                                    Higher numbers = Higher authority levels
                                </p>
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
                                    placeholder="e.g., L1, L2, L2PM"
                                    required
                                />
                                <p class="text-xs text-muted-foreground">
                                    Short identifier (e.g., L2PM for Project Manager, L2 for Direct Manager)
                                </p>
                                <div v-if="form.errors.code" class="text-sm text-destructive">{{ form.errors.code }}</div>
                            </div>

                            <!-- Level Name -->
                            <div class="space-y-2 md:col-span-2">
                                <Label for="name">Level Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    :disabled="form.processing"
                                    placeholder="e.g., Employee, Direct Manager, Project Manager"
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
                        <CardDescription>
                            Select which levels this level can manage and oversee
                            <span v-if="form.hierarchy_level === '0'" class="block text-orange-600 font-medium mt-1">
                                ⚠️ Level 0 typically has no management authority
                            </span>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <!-- ✅ Show warning for Level 0 -->
                            <div v-if="form.hierarchy_level === '0'" class="p-4 bg-orange-50 border border-orange-200 rounded-lg">
                                <div class="flex items-start space-x-3">
                                    <div class="text-orange-500 mt-0.5">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-orange-800 font-semibold">Project Manager Level</h4>
                                        <p class="text-orange-700 text-sm mt-1">
                                            Level 0 is typically used for Project Managers who coordinate projects but don't have direct management authority over people. Leave management permissions empty unless this role requires specific oversight.
                                        </p>
                                    </div>
                                </div>
                            </div>

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
                                <span class="text-sm font-bold">{{ form.code }}</span>
                            </div>
                            <div class="flex-1">
                                <div class="text-lg font-semibold text-foreground">{{ form.name || 'Level Name' }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ form.hierarchy_level === '0' ? 'No Management Level' : `Hierarchy Level ${form.hierarchy_level || '?'}` }}
                                </div>
                                <div v-if="form.description" class="text-sm text-muted-foreground mt-1">{{ form.description }}</div>
                                <div v-if="form.can_manage_levels.length > 0" class="flex flex-wrap gap-1 mt-2">
                                    <Badge v-for="level in form.can_manage_levels" :key="level" variant="secondary" class="text-xs">
                                        Can manage: {{ level }}
                                    </Badge>
                                </div>
                                <div v-else-if="form.hierarchy_level === '0'" class="mt-2">
                                    <Badge variant="outline" class="text-xs text-orange-600 border-orange-300">
                                        No Management Authority
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
            </form>
        </div>
    </AdminLayout>
</template>
