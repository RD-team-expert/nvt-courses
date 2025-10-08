<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'

// Icons
import {
    ArrowLeft,
    Save,
    Info,
    BookOpen,
    Clock,
    Hash,
    FileText,
    Settings,
    Loader2
} from 'lucide-vue-next'

// Types
interface Course {
    id: number
    name: string
    description?: string
}

// Props
const props = defineProps<{
    course: Course
    nextOrderNumber: number
}>()

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Courses', href: '/admin/course-online' },
    { title: props.course.name, href: `/admin/course-online/${props.course.id}` },
    { title: 'Modules', href: `/admin/course-modules/${props.course.id}` },
    { title: 'Create', href: '#' }
]

// Form
const form = useForm({
    name: '',
    description: '',
    order_number: 1,
    estimated_duration: null as number | null,
    is_required: true,
    is_active: true
})

// Computed
const estimatedHours = computed(() => {
    if (!form.estimated_duration) return null
    return Math.round((form.estimated_duration / 60) * 10) / 10
})

const hasErrors = computed(() => {
    return Object.keys(form.errors).length > 0
})

// Initialize form
onMounted(() => {
    form.order_number = props.nextOrderNumber
})

// Submit form
const submit = () => {
    form.post(route('admin.course-modules.store', props.course.id), {
        onSuccess: () => {
            // Form will be automatically redirected by the controller
        }
    })
}

// Handle checkbox changes
const handleRequiredChange = (checked: boolean) => {
    form.is_required = checked
}

const handleActiveChange = (checked: boolean) => {
    form.is_active = checked
}
</script>

<template>
    <Head title="Create Module" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-8">
            <div class="space-y-1">
                <div class="flex items-center gap-3 mb-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link :href="route('admin.course-modules.index', course.id)">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Modules
                        </Link>
                    </Button>
                </div>
                <h1 class="text-3xl font-bold tracking-tight">Create New Module</h1>
                <p class="text-muted-foreground">
                    Add a new module to "<span class="font-medium">{{ course.name }}</span>"
                </p>
            </div>
        </div>

        <!-- Form Card -->
        <form @submit.prevent="submit" class="space-y-8">
            <!-- Main Information -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <BookOpen class="h-5 w-5" />
                        Module Information
                    </CardTitle>
                    <CardDescription>
                        Basic information about the module
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Module Name -->
                    <div class="space-y-2">
                        <Label for="name">
                            Module Name *
                        </Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Enter module name"
                            :class="{ 'border-destructive': form.errors.name }"
                            required
                        />
                        <div v-if="form.errors.name" class="text-sm text-destructive">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <Label for="description">
                            Description
                        </Label>
                        <Textarea
                            id="description"
                            v-model="form.description"
                            placeholder="Describe what students will learn in this module"
                            :class="{ 'border-destructive': form.errors.description }"
                            rows="4"
                        />
                        <p class="text-xs text-muted-foreground">
                            Optional description to help students understand the module content
                        </p>
                        <div v-if="form.errors.description" class="text-sm text-destructive">
                            {{ form.errors.description }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Configuration -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Settings class="h-5 w-5" />
                        Module Configuration
                    </CardTitle>
                    <CardDescription>
                        Set up module ordering and duration
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Order Number -->
                        <div class="space-y-2">
                            <Label for="order_number" class="flex items-center gap-2">
                                <Hash class="h-4 w-4" />
                                Order Number *
                            </Label>
                            <Input
                                id="order_number"
                                v-model.number="form.order_number"
                                type="number"
                                min="1"
                                placeholder="e.g. 1"
                                :class="{ 'border-destructive': form.errors.order_number }"
                                required
                            />
                            <p class="text-xs text-muted-foreground">
                                Position of this module in the course sequence
                            </p>
                            <div v-if="form.errors.order_number" class="text-sm text-destructive">
                                {{ form.errors.order_number }}
                            </div>
                        </div>

                        <!-- Estimated Duration -->
                        <div class="space-y-2">
                            <Label for="estimated_duration" class="flex items-center gap-2">
                                <Clock class="h-4 w-4" />
                                Estimated Duration (minutes)
                            </Label>
                            <Input
                                id="estimated_duration"
                                v-model.number="form.estimated_duration"
                                type="number"
                                min="1"
                                max="1000"
                                placeholder="e.g. 30"
                                :class="{ 'border-destructive': form.errors.estimated_duration }"
                            />
                            <p class="text-xs text-muted-foreground">
                                How long students should spend on this module
                                <span v-if="estimatedHours" class="font-medium">
                                    (≈ {{ estimatedHours }}h)
                                </span>
                            </p>
                            <div v-if="form.errors.estimated_duration" class="text-sm text-destructive">
                                {{ form.errors.estimated_duration }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Module Settings -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Settings class="h-5 w-5" />
                        Module Settings
                    </CardTitle>
                    <CardDescription>
                        Configure module accessibility and requirements
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Required Module -->
                    <div class="flex items-start space-x-3">
                        <Checkbox
                            id="is_required"
                            :checked="form.is_required"
                            @update:checked="handleRequiredChange"
                        />
                        <div class="space-y-1">
                            <Label for="is_required" class="text-sm font-medium cursor-pointer">
                                Required Module
                            </Label>
                            <p class="text-sm text-muted-foreground">
                                Students must complete this module to progress to the next one
                            </p>
                        </div>
                        <Badge v-if="form.is_required" variant="destructive" class="ml-auto">
                            Required
                        </Badge>
                    </div>

                    <Separator />

                    <!-- Active Status -->
                    <div class="flex items-start space-x-3">
                        <Checkbox
                            id="is_active"
                            :checked="form.is_active"
                            @update:checked="handleActiveChange"
                        />
                        <div class="space-y-1">
                            <Label for="is_active" class="text-sm font-medium cursor-pointer">
                                Active Module
                            </Label>
                            <p class="text-sm text-muted-foreground">
                                Module is visible and accessible to students
                            </p>
                        </div>
                        <Badge
                            :variant="form.is_active ? 'default' : 'secondary'"
                            class="ml-auto"
                        >
                            {{ form.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                </CardContent>
            </Card>

            <!-- Course Context -->
            <Alert>
                <Info class="h-4 w-4" />
                <AlertDescription>
                    <div class="space-y-2">
                        <div class="font-medium">Course: {{ course.name }}</div>
                        <p class="text-sm">
                            This module will be added to the above course. After creating the module,
                            you can add content items (videos, PDFs, documents) to organize your learning materials.
                        </p>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- Error Summary -->
            <Alert v-if="hasErrors" variant="destructive">
                <AlertDescription>
                    <div class="font-medium mb-2">Please fix the following errors:</div>
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        <li v-for="(error, field) in form.errors" :key="field">
                            {{ error }}
                        </li>
                    </ul>
                </AlertDescription>
            </Alert>

            <!-- Actions -->
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center justify-end space-x-4">
                        <Button variant="outline" asChild>
                            <Link :href="route('admin.course-modules.index', course.id)">
                                Cancel
                            </Link>
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="min-w-[120px]"
                        >
                            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            <Save v-else class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Creating...' : 'Create Module' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    </AdminLayout>
</template>

<style scoped>
/* Custom styles for better form layout */
.space-y-8 > * + * {
    margin-top: 2rem;
}

.space-y-6 > * + * {
    margin-top: 1.5rem;
}

.space-y-2 > * + * {
    margin-top: 0.5rem;
}
</style>
