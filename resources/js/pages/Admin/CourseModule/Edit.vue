<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
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
    Settings,
    AlertTriangle,
    Loader2,
    FileText
} from 'lucide-vue-next'

// Types
interface Module {
    id: number
    name: string
    description?: string
    order_number: number
    estimated_duration?: number
    is_required: boolean
    is_active: boolean
}

interface Course {
    id: number
    name: string
    description?: string
}

// Props
const props = defineProps<{
    course: Course
    module: Module
}>()

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Courses', href: '/admin/course-online' },
    { title: props.course.name, href: `/admin/course-online/${props.course.id}` },
    { title: 'Modules', href: `/admin/course-modules/${props.course.id}` },
    { title: 'Edit', href: '#' }
]

// Track original order number
const originalOrderNumber = ref(0)

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
const orderChanged = computed(() => {
    return form.order_number !== originalOrderNumber.value
})

const estimatedHours = computed(() => {
    if (!form.estimated_duration) return null
    return Math.round((form.estimated_duration / 60) * 10) / 10
})

const hasErrors = computed(() => {
    return Object.keys(form.errors).length > 0
})

// Initialize form
onMounted(() => {
    form.name = props.module.name || ''
    form.description = props.module.description || ''
    form.order_number = props.module.order_number || 1
    form.estimated_duration = props.module.estimated_duration || null
    form.is_required = props.module.is_required ?? true
    form.is_active = props.module.is_active ?? true

    // Store original order number
    originalOrderNumber.value = props.module.order_number || 1
})

// Submit form
const submit = () => {
    form.patch(route('admin.course-modules.update', [props.course.id, props.module.id]), {
        onSuccess: () => {
            // Update original order number after successful save
            originalOrderNumber.value = form.order_number
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
    <Head title="Edit Module" />

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
                <h1 class="text-3xl font-bold tracking-tight">Edit Module</h1>
                <p class="text-muted-foreground">
                    Update "<span class="font-medium">{{ module.name }}</span>" in {{ course.name }}
                </p>
            </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-8">
            <!-- Module Information -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <BookOpen class="h-5 w-5" />
                        Module Information
                    </CardTitle>
                    <CardDescription>
                        Update the basic information for this module
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

            <!-- Module Configuration -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Settings class="h-5 w-5" />
                        Module Configuration
                    </CardTitle>
                    <CardDescription>
                        Configure module ordering and duration settings
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
                        <div class="space-y-1 flex-1">
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
                        <div class="space-y-1 flex-1">
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
                            Editing module "{{ module.name }}". Changes will affect how students progress through this course
                            and may impact existing student progress.
                        </p>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- Order Change Warning -->
            <Alert v-if="orderChanged" variant="destructive">
                <AlertTriangle class="h-4 w-4" />
                <AlertDescription>
                    <div class="space-y-2">
                        <div class="font-medium">Order Number Changed</div>
                        <p class="text-sm">
                            Changing the order number from {{ originalOrderNumber }} to {{ form.order_number }}
                            will automatically reorder other modules in this course and may affect student progress.
                        </p>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- Error Summary -->
            <Alert v-if="hasErrors" variant="destructive">
                <AlertTriangle class="h-4 w-4" />
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
                            class="min-w-[140px]"
                        >
                            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            <Save v-else class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Updating...' : 'Update Module' }}
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
