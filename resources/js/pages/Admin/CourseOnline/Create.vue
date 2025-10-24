<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Switch } from '@/components/ui/switch'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'

// Icons
import { ArrowLeft, Save, BookOpen, Plus, Trash2, ChevronUp, ChevronDown, Video, FileText, Upload, X, AlertTriangle, Clock, Users, Settings, Eye, Calendar, CalendarClock } from 'lucide-vue-next' // ✅ NEW: Added Calendar icons

// Interfaces
interface Video {
    id: number
    name: string
    formatted_duration: string
    thumbnail_url?: string
    google_drive_url: string
}

interface Module {
    name: string
    description: string
    order_number: number
    estimated_duration: number | null
    is_required: boolean
    is_active: boolean
    content: ContentItem[]
}

// ✅ ENHANCED: ContentItem with pdf_page_count
interface ContentItem {
    title: string
    content_type: 'video' | 'pdf'
    order_number: number
    is_required: boolean
    is_active: boolean
    // Video fields
    video_id: number | null
    // PDF fields
    pdf_source_type: 'upload' | 'google_drive'
    pdf_file: File | null
    google_drive_pdf_url: string
    pdf_name?: string
    pdf_page_count: number | null // ✅ NEW: PDF page count field
}

const props = defineProps<{
    availableVideos?: Video[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Course Online", href: "/admin/course-online" },
    { title: "Create Course", href: "/admin/course-online/create" },
]

// Reactive references
const imageInput = ref<HTMLInputElement | null>(null)
const imagePreview = ref<string | null>(null)

// ✅ NEW: Form with deadline fields
const form = useForm({
    // Course fields
    name: '',
    description: '',
    image: null as File | null,
    difficulty_level: '',
    estimated_duration: null as number | null,
    is_active: true,
    // ✅ NEW: Deadline fields
    deadline: null as string | null,
    deadline_type: 'flexible',
    // Modules with content array
    modules: [] as Module[]
})

// ✅ ENHANCED: Computed properties with PDF validation
const canSubmit = computed(() => {
    // Basic course info validation
    if (!form.name || !form.difficulty_level || form.modules.length === 0) {
        return false
    }

    // ✅ FIXED: Always check deadline (no conditional)
    if (!form.deadline) {
        return false
    }

    return form.modules.every(module => {
        if (!module.name.trim()) {
            return false
        }

        if (!module.content || module.content.length === 0) {
            // Allow modules without content (empty modules are valid)
            return true
        }

        return module.content.every(content => {
            const basicValid = content.title && content.content_type

            // PDF validation
            if (content.content_type === 'pdf') {
                return basicValid &&
                    content.pdf_page_count &&
                    content.pdf_page_count > 0 &&
                    content.pdf_page_count <= 1000 &&
                    ((content.pdf_source_type === 'upload' && content.pdf_file) ||
                        (content.pdf_source_type === 'google_drive' && content.google_drive_pdf_url.trim()))
            }

            return basicValid
        })
    })
})

const totalContentItems = computed(() => {
    return form.modules.reduce((total, module) => total + (module.content?.length || 0), 0)
})

// ✅ NEW: Deadline computed properties
const deadlineMinDate = computed(() => {
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    return tomorrow.toISOString().slice(0, 16)
})

const deadlinePreview = computed(() => {
    if (!form.deadline) return null
    const date = new Date(form.deadline)
    return {
        formatted: date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }),
        relative: Math.ceil((date.getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24))
    }
})

// Image handling
const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (file) {
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB')
            target.value = ''
            return
        }

        form.image = file

        // Create preview
        const reader = new FileReader()
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string
        }
        reader.readAsDataURL(file)
    }
}

const removeImage = () => {
    form.image = null
    imagePreview.value = null
    if (imageInput.value) {
        imageInput.value.value = ''
    }
}

const triggerImageInput = () => {
    imageInput.value?.click()
}

// Module management
const addModule = () => {
    form.modules.push({
        name: '',
        description: '',
        order_number: form.modules.length + 1,
        estimated_duration: null,
        is_required: true,
        is_active: true,
        content: []
    })
}

const removeModule = (index: number) => {
    if (confirm('Are you sure you want to remove this module and all its content?')) {
        form.modules.splice(index, 1)
        // Update order numbers
        form.modules.forEach((module, idx) => {
            module.order_number = idx + 1
        })
    }
}

const moveModule = (index: number, direction: 'up' | 'down') => {
    const newIndex = direction === 'up' ? index - 1 : index + 1
    if (newIndex < 0 || newIndex >= form.modules.length) return

    // Swap modules
    const temp = form.modules[index]
    form.modules[index] = form.modules[newIndex]
    form.modules[newIndex] = temp

    // Update order numbers
    form.modules.forEach((module, idx) => {
        module.order_number = idx + 1
    })
}

// Content management
const addContentToModule = (moduleIndex: number) => {
    if (!form.modules[moduleIndex].content) {
        form.modules[moduleIndex].content = []
    }

    form.modules[moduleIndex].content.push({
        title: '',
        content_type: 'video' as any,
        order_number: form.modules[moduleIndex].content.length + 1,
        is_required: true,
        is_active: true,
        // Video fields
        video_id: null,
        // PDF fields
        pdf_source_type: 'upload',
        pdf_file: null,
        google_drive_pdf_url: '',
        pdf_name: '',
        pdf_page_count: null // ✅ NEW: Initialize page count
    })
}

const removeContentFromModule = (moduleIndex: number, contentIndex: number) => {
    if (confirm('Are you sure you want to remove this content item?')) {
        form.modules[moduleIndex].content.splice(contentIndex, 1)
        // Update order numbers
        form.modules[moduleIndex].content.forEach((content, idx) => {
            content.order_number = idx + 1
        })
    }
}

const resetContentFields = (moduleIndex: number, contentIndex: number) => {
    const content = form.modules[moduleIndex].content[contentIndex]
    content.video_id = null
    content.pdf_file = null
    content.google_drive_pdf_url = ''
    content.pdf_source_type = 'upload'
    content.pdf_name = ''
    content.pdf_page_count = null // ✅ NEW: Reset page count
}

const handlePdfUpload = (event: Event, moduleIndex: number, contentIndex: number) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]

    if (file && file.type === 'application/pdf') {
        form.modules[moduleIndex].content[contentIndex].pdf_file = file
        // Set default PDF name from file
        form.modules[moduleIndex].content[contentIndex].pdf_name = file.name
    }
}

const getSelectedVideo = (videoId: number): Video | undefined => {
    return props.availableVideos?.find(video => video.id === videoId)
}

const getDifficultyBadgeColor = (level: string) => {
    switch (level) {
        case 'beginner':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
        case 'intermediate':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
        case 'advanced':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    }
}

// ✅ NEW: PDF validation helper
const validatePdfContent = (content: ContentItem): string | null => {
    if (content.content_type === 'pdf') {
        // Check if page count exists and is a valid number
        if (!content.pdf_page_count || content.pdf_page_count < 1) {
            return 'PDF page count is required and must be at least 1'
        }
        if (content.pdf_page_count > 1000) {
            return 'Page count cannot exceed 1000'
        }

        // Check source requirements
        if (content.pdf_source_type === 'upload' && !content.pdf_file) {
            return 'Please upload a PDF file'
        }
        if (content.pdf_source_type === 'google_drive' && !content.google_drive_pdf_url.trim()) {
            return 'Please provide Google Drive URL'
        }
    }
    return null
}

// Form submission
// ✅ SIMPLIFIED: No conditional deadline logic needed
const submit = () => {
    // PDF validation (unchanged)
    for (let moduleIndex = 0; moduleIndex < form.modules.length; moduleIndex++) {
        const module = form.modules[moduleIndex]
        for (let contentIndex = 0; contentIndex < (module.content?.length || 0); contentIndex++) {
            const content = module.content[contentIndex]
            const error = validatePdfContent(content)
            if (error) {
                alert(`Module ${moduleIndex + 1}, Content ${contentIndex + 1}: ${error}`)
                return
            }
        }
    }

    // ✅ ALWAYS SET: has_deadline is always true now
    form.post('/admin/course-online', {
        onSuccess: () => {
            form.reset()
            imagePreview.value = null
        }
    })
}
</script>

<template>
    <Head title="Create Complete Course" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button as-child variant="ghost">
                    <Link href="/admin/course-online">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Courses
                    </Link>
                </Button>

                <div class="flex-1">
                    <h1 class="text-3xl font-bold">Create Complete Course</h1>
                    <p class="text-muted-foreground">Build course, modules, and content all in one place</p>
                </div>

                <div class="hidden sm:flex items-center gap-4 text-sm text-muted-foreground">
                    <div class="flex items-center gap-2">
                        <BookOpen class="h-4 w-4" />
                        {{ form.modules.length }} modules
                    </div>
                    <div class="flex items-center gap-2">
                        <FileText class="h-4 w-4" />
                        {{ totalContentItems }} content items
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Course Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BookOpen class="h-5 w-5 text-primary" />
                            Course Information
                        </CardTitle>
                        <CardDescription>
                            Enter the basic details for your online course
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Course Name -->
                        <div class="space-y-2">
                            <Label for="name">Course Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter course name"
                                :class="{ 'border-destructive': form.errors.name }"
                                required
                            />
                            <div v-if="form.errors.name" class="text-sm text-destructive">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Course Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describe what students will learn in this course..."
                                :rows="4"
                                :class="{ 'border-destructive': form.errors.description }"
                            />
                            <div v-if="form.errors.description" class="text-sm text-destructive">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <!-- Course Settings Row -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Difficulty Level -->
                            <div class="space-y-2">
                                <Label for="difficulty_level">Difficulty Level</Label>
                                <select
                                    id="difficulty_level"
                                    v-model="form.difficulty_level"
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="{ 'border-destructive': form.errors.difficulty_level }"
                                >
                                    <option value="">Select difficulty</option>
                                    <option value="beginner">Beginner</option>
                                    <option value="intermediate">Intermediate</option>
                                    <option value="advanced">Advanced</option>
                                </select>
                                <div v-if="form.errors.difficulty_level" class="text-sm text-destructive">
                                    {{ form.errors.difficulty_level }}
                                </div>
                            </div>

                            <!-- Estimated Duration -->
                            <div class="space-y-2">
                                <Label for="estimated_duration">Duration (minutes)</Label>
                                <Input
                                    id="estimated_duration"
                                    v-model.number="form.estimated_duration"
                                    type="number"
                                    min="1"
                                    placeholder="e.g. 120"
                                    :class="{ 'border-destructive': form.errors.estimated_duration }"
                                />
                                <div v-if="form.errors.estimated_duration" class="text-sm text-destructive">
                                    {{ form.errors.estimated_duration }}
                                </div>
                            </div>

                            <!-- Active Status -->
                            <div class="space-y-2">
                                <Label>Course Status</Label>
                                <div class="flex items-center space-x-2 pt-2">
                                    <Switch
                                        :checked="form.is_active"
                                        @update:checked="form.is_active = $event"
                                    />
                                    <span class="text-sm text-muted-foreground">
                                        {{ form.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- ✅ NEW: Course Deadline Section -->
                <!-- ✅ FIXED: Course Deadline Section - Always visible -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5 text-primary" />
                            Course Deadline
                            <Badge variant="destructive" class="ml-2">Required</Badge>
                        </CardTitle>
                        <CardDescription>
                            Set a completion deadline for this course - all courses must have a deadline
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- ✅ ALWAYS VISIBLE: Deadline Date & Time -->
                        <div class="space-y-2">
                            <Label for="deadline">📅 Deadline Date & Time *</Label>
                            <Input
                                id="deadline"
                                v-model="form.deadline"
                                type="datetime-local"
                                :min="deadlineMinDate"
                                :class="{ 'border-destructive': form.errors.deadline || !form.deadline }"
                                required
                                class="w-full"
                            />
                            <div v-if="form.errors.deadline" class="text-sm text-destructive">
                                {{ form.errors.deadline }}
                            </div>
                            <div v-if="!form.deadline" class="text-sm text-destructive">
                                Course deadline is required for all courses
                            </div>
                        </div>

                        <!-- ✅ ALWAYS VISIBLE: Deadline Type -->
                        <div class="space-y-2">
                            <Label for="deadline_type">⚙️ Deadline Type</Label>
                            <select
                                id="deadline_type"
                                v-model="form.deadline_type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="flexible">Flexible - Allow completion after deadline</option>
                                <option value="strict">Strict - Block access after deadline</option>
                            </select>
                        </div>

                        <!-- ✅ ALWAYS VISIBLE: Deadline Preview (when deadline is set) -->
                        <div v-if="form.deadline && deadlinePreview" class="bg-blue-50 dark:bg-blue-950 p-4 rounded-lg border">
                            <div class="flex items-start gap-3">
                                <CalendarClock class="h-5 w-5 text-blue-600 mt-0.5" />
                                <div class="flex-1">
                                    <div class="font-medium text-blue-900 dark:text-blue-100">
                                        📅 {{ deadlinePreview.formatted }}
                                    </div>
                                    <div class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                        ⏰ {{ deadlinePreview.relative }} days from now
                                    </div>
                                    <div class="text-xs text-muted-foreground mt-2">
                                        Type: {{ form.deadline_type === 'flexible' ? 'Flexible (late completion allowed)' : 'Strict (access blocked after deadline)' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ✅ MANDATORY NOTICE -->
                        <Alert>
                            <AlertTriangle class="h-4 w-4" />
                            <AlertDescription>
                                <strong>Required:</strong> All courses must have a deadline. This ensures proper progress tracking and evaluation reporting.
                                Students and managers will be notified about the deadline automatically.
                            </AlertDescription>
                        </Alert>
                    </CardContent>
                </Card>

                <!-- Course Image -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Upload class="h-5 w-5 text-primary" />
                            Course Image
                        </CardTitle>
                        <CardDescription>
                            Upload a course image to make it more attractive
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Hidden file input -->
                        <input
                            ref="imageInput"
                            type="file"
                            accept="image/*"
                            @change="handleImageUpload"
                            class="hidden"
                        />

                        <!-- Upload Area -->
                        <div
                            v-if="!imagePreview"
                            @click="triggerImageInput"
                            class="border-2 border-dashed border-input rounded-lg p-8 text-center cursor-pointer hover:border-primary transition-colors"
                            :class="{ 'border-destructive': form.errors.image }"
                        >
                            <Upload class="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                            <div class="text-sm font-medium mb-2">Click to upload course image</div>
                            <div class="text-xs text-muted-foreground">PNG, JPG, GIF up to 2MB</div>
                        </div>

                        <!-- Image Preview -->
                        <div v-if="imagePreview" class="space-y-4">
                            <div class="relative w-48 h-32 rounded-lg overflow-hidden border mx-auto">
                                <img
                                    :src="imagePreview"
                                    alt="Course preview"
                                    class="w-full h-full object-cover"
                                />
                                <Button
                                    @click="removeImage"
                                    variant="destructive"
                                    size="sm"
                                    class="absolute top-2 right-2 h-8 w-8 p-0 rounded-full"
                                    type="button"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                            <div class="text-center">
                                <Button
                                    @click="triggerImageInput"
                                    variant="outline"
                                    size="sm"
                                    type="button"
                                >
                                    <Upload class="h-4 w-4 mr-2" />
                                    Change Image
                                </Button>
                            </div>
                        </div>

                        <div v-if="form.errors.image" class="text-sm text-destructive">
                            {{ form.errors.image }}
                        </div>
                    </CardContent>
                </Card>

                <!-- Course Modules -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Settings class="h-5 w-5 text-primary" />
                                    Course Modules
                                    <Badge variant="secondary">{{ form.modules.length }}</Badge>
                                </CardTitle>
                                <CardDescription>
                                    Build your course structure with modules and content
                                </CardDescription>
                            </div>
                            <Button
                                type="button"
                                @click="addModule"
                                class="shrink-0"
                            >
                                <Plus class="h-4 w-4 mr-2" />
                                Add Module
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- Empty State -->
                        <div v-if="form.modules.length === 0" class="text-center py-12 border-2 border-dashed border-input rounded-lg">
                            <BookOpen class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                            <h3 class="text-lg font-medium mb-2">No modules added yet</h3>
                            <p class="text-muted-foreground mb-4">Add your first module to start building your course</p>
                            <Button type="button" @click="addModule">
                                <Plus class="h-4 w-4 mr-2" />
                                Add First Module
                            </Button>
                        </div>

                        <!-- Modules List -->
                        <div v-else class="space-y-6">
                            <Card
                                v-for="(module, moduleIndex) in form.modules"
                                :key="`module-${moduleIndex}`"
                                class="border-2"
                            >
                                <CardHeader class="pb-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-primary/10 text-primary rounded-full flex items-center justify-center text-sm font-semibold">
                                                {{ moduleIndex + 1 }}
                                            </div>
                                            <div>
                                                <CardTitle class="text-lg">Module {{ moduleIndex + 1 }}</CardTitle>
                                                <CardDescription v-if="module.content?.length">
                                                    {{ module.content.length }} content items
                                                </CardDescription>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <Button
                                                v-if="moduleIndex > 0"
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="moveModule(moduleIndex, 'up')"
                                            >
                                                <ChevronUp class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                v-if="moduleIndex < form.modules.length - 1"
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="moveModule(moduleIndex, 'down')"
                                            >
                                                <ChevronDown class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="removeModule(moduleIndex)"
                                                class="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="space-y-6">
                                    <!-- Module Basic Info -->
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Module Name -->
                                            <div class="space-y-2">
                                                <Label :for="`module-name-${moduleIndex}`">Module Name</Label>
                                                <Input
                                                    :id="`module-name-${moduleIndex}`"
                                                    v-model="module.name"
                                                    type="text"
                                                    required
                                                    :placeholder="`Module ${moduleIndex + 1} name`"
                                                    :class="{ 'border-destructive': form.errors[`modules.${moduleIndex}.name`] }"
                                                />
                                                <div v-if="form.errors[`modules.${moduleIndex}.name`]" class="text-sm text-destructive">
                                                    {{ form.errors[`modules.${moduleIndex}.name`] }}
                                                </div>
                                            </div>

                                            <!-- Duration -->
                                            <div class="space-y-2">
                                                <Label :for="`module-duration-${moduleIndex}`">Duration (minutes)</Label>
                                                <Input
                                                    :id="`module-duration-${moduleIndex}`"
                                                    v-model.number="module.estimated_duration"
                                                    type="number"
                                                    min="1"
                                                    placeholder="e.g. 30"
                                                    :class="{ 'border-destructive': form.errors[`modules.${moduleIndex}.estimated_duration`] }"
                                                />
                                                <div v-if="form.errors[`modules.${moduleIndex}.estimated_duration`]" class="text-sm text-destructive">
                                                    {{ form.errors[`modules.${moduleIndex}.estimated_duration`] }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Module Description -->
                                        <div class="space-y-2">
                                            <Label :for="`module-description-${moduleIndex}`">Module Description</Label>
                                            <Textarea
                                                :id="`module-description-${moduleIndex}`"
                                                v-model="module.description"
                                                :rows="3"
                                                :placeholder="`Describe what students will learn in module ${moduleIndex + 1}`"
                                                :class="{ 'border-destructive': form.errors[`modules.${moduleIndex}.description`] }"
                                            />
                                            <div v-if="form.errors[`modules.${moduleIndex}.description`]" class="text-sm text-destructive">
                                                {{ form.errors[`modules.${moduleIndex}.description`] }}
                                            </div>
                                        </div>

                                        <!-- Module Settings -->
                                        <div class="flex items-center gap-6">
                                            <div class="flex items-center space-x-2">
                                                <Switch
                                                    :checked="module.is_required"
                                                    @update:checked="module.is_required = $event"
                                                />
                                                <Label class="text-sm">Required module</Label>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <Switch
                                                    :checked="module.is_active"
                                                    @update:checked="module.is_active = $event"
                                                />
                                                <Label class="text-sm">Active module</Label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Module Content Section -->
                                    <div class="border rounded-lg p-4 bg-muted/20">
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-center gap-2">
                                                <Video class="h-5 w-5 text-primary" />
                                                <h4 class="font-medium">Module Content</h4>
                                                <Badge variant="outline">{{ module.content?.length || 0 }} items</Badge>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click="addContentToModule(moduleIndex)"
                                            >
                                                <Plus class="h-4 w-4 mr-2" />
                                                Add Content
                                            </Button>
                                        </div>

                                        <!-- Content Items -->
                                        <div v-if="!module.content || module.content.length === 0" class="text-center py-8 border-2 border-dashed border-input rounded-lg">
                                            <FileText class="h-12 w-12 mx-auto text-muted-foreground mb-3" />
                                            <p class="text-muted-foreground text-sm mb-4">No content added yet</p>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click="addContentToModule(moduleIndex)"
                                            >
                                                Add first content item
                                            </Button>
                                        </div>
                                        <div v-else class="space-y-4">
                                            <Card
                                                v-for="(content, contentIndex) in module.content"
                                                :key="`content-${moduleIndex}-${contentIndex}`"
                                                class="border"
                                            >
                                                <CardHeader class="pb-3">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-6 h-6 bg-primary/10 text-primary rounded-full flex items-center justify-center text-xs font-medium">
                                                                {{ contentIndex + 1 }}
                                                            </div>
                                                            <CardTitle class="text-base">Content Item {{ contentIndex + 1 }}</CardTitle>
                                                            <Badge
                                                                v-if="content.content_type"
                                                                :class="content.content_type === 'video'
                                                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                                                                    : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'"
                                                            >
                                                                <component
                                                                    :is="content.content_type === 'video' ? Video : FileText"
                                                                    class="h-3 w-3 mr-1"
                                                                />
                                                                {{ content.content_type }}
                                                            </Badge>
                                                        </div>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            @click="removeContentFromModule(moduleIndex, contentIndex)"
                                                            class="text-destructive hover:text-destructive"
                                                        >
                                                            <Trash2 class="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </CardHeader>
                                                <CardContent class="space-y-4">
                                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                                        <!-- Content Type -->
                                                        <div class="space-y-2">
                                                            <Label>Content Type</Label>
                                                            <select
                                                                v-model="content.content_type"
                                                                required
                                                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                                @change="resetContentFields(moduleIndex, contentIndex)"
                                                            >
                                                                <option value="">Select type</option>
                                                                <option value="video">Video</option>
                                                                <option value="pdf">PDF Document</option>
                                                            </select>
                                                        </div>

                                                        <!-- Content Title -->
                                                        <div class="space-y-2">
                                                            <Label>Content Title</Label>
                                                            <Input
                                                                v-model="content.title"
                                                                type="text"
                                                                required
                                                                placeholder="Enter content title"
                                                            />
                                                        </div>
                                                    </div>

                                                    <!-- Video Content Fields -->
                                                    <div v-if="content.content_type === 'video'" class="space-y-4">
                                                        <div class="space-y-2">
                                                            <Label>Select Video</Label>
                                                            <select
                                                                v-model="content.video_id"
                                                                required
                                                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                                            >
                                                                <option value="">Choose a video</option>
                                                                <option
                                                                    v-for="video in availableVideos"
                                                                    :key="video.id"
                                                                    :value="video.id"
                                                                >
                                                                    {{ video.name }} ({{ video.formatted_duration }})
                                                                </option>
                                                            </select>
                                                        </div>

                                                        <!-- Video Preview -->
                                                        <div v-if="content.video_id && getSelectedVideo(content.video_id)" class="p-3 bg-blue-50 dark:bg-blue-950 rounded-lg">
                                                            <div class="flex items-center gap-3">
                                                                <img
                                                                    v-if="getSelectedVideo(content.video_id)?.thumbnail_url"
                                                                    :src="getSelectedVideo(content.video_id)!.thumbnail_url"
                                                                    :alt="getSelectedVideo(content.video_id)!.name"
                                                                    class="w-16 h-12 object-cover rounded"
                                                                />
                                                                <div class="flex-1">
                                                                    <p class="font-medium text-sm">{{ getSelectedVideo(content.video_id)!.name }}</p>
                                                                </div>
                                                                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                                                    <Clock class="h-3 w-3" />
                                                                    {{ getSelectedVideo(content.video_id)!.formatted_duration }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- ✅ ENHANCED: PDF Content Fields with Page Count -->
                                                    <div v-if="content.content_type === 'pdf'" class="space-y-4">
                                                        <div class="space-y-3">
                                                            <Label>PDF Source</Label>
                                                            <div class="flex gap-6">
                                                                <label class="flex items-center space-x-2">
                                                                    <input
                                                                        v-model="content.pdf_source_type"
                                                                        type="radio"
                                                                        value="upload"
                                                                        class="text-primary"
                                                                    />
                                                                    <span class="text-sm">Upload File</span>
                                                                </label>
                                                                <label class="flex items-center space-x-2">
                                                                    <input
                                                                        v-model="content.pdf_source_type"
                                                                        type="radio"
                                                                        value="google_drive"
                                                                        class="text-primary"
                                                                    />
                                                                    <span class="text-sm">Google Drive URL</span>
                                                                </label>
                                                            </div>

                                                            <!-- File Upload -->
                                                            <div v-if="content.pdf_source_type === 'upload'">
                                                                <input
                                                                    type="file"
                                                                    accept=".pdf"
                                                                    @change="handlePdfUpload($event, moduleIndex, contentIndex)"
                                                                    class="block w-full text-sm text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20"
                                                                />
                                                            </div>

                                                            <!-- Google Drive URL -->
                                                            <div v-if="content.pdf_source_type === 'google_drive'" class="space-y-2">
                                                                <Input
                                                                    v-model="content.google_drive_pdf_url"
                                                                    type="url"
                                                                    placeholder="https://drive.google.com/..."
                                                                />
                                                                <div class="space-y-2">
                                                                    <Label>PDF Name</Label>
                                                                    <Input
                                                                        v-model="content.pdf_name"
                                                                        type="text"
                                                                        placeholder="Enter PDF name"
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- ✅ NEW: PDF Page Count Field -->
                                                        <div class="space-y-2">
                                                            <Label class="text-red-600">PDF Page Count *</Label>
                                                            <Input
                                                                v-model.number="content.pdf_page_count"
                                                                type="number"
                                                                min="1"
                                                                max="1000"
                                                                placeholder="Enter number of pages"
                                                                required
                                                                class="w-full"
                                                                :class="{ 'border-red-500': !content.pdf_page_count }"

                                                            />
                                                            <p class="text-xs text-red-600">
                                                                <strong>Required:</strong> Please count the pages manually and enter the exact number.
                                                                This helps provide accurate reading time estimates.
                                                            </p>
                                                        </div>

                                                        <div v-if="validatePdfContent(content)" class="text-sm text-destructive">
                                                            {{ validatePdfContent(content) }}
                                                        </div>

                                                        <!-- PDF Preview -->
                                                        <div v-if="content.pdf_file || content.google_drive_pdf_url" class="p-3 bg-purple-50 dark:bg-purple-950 rounded-lg">
                                                            <div class="flex items-center gap-3">
                                                                <FileText class="h-8 w-8 text-purple-600" />
                                                                <div class="flex-1">
                                                                    <p class="font-medium text-sm">
                                                                        {{ content.pdf_file ? content.pdf_file.name : (content.pdf_name || 'Google Drive PDF') }}
                                                                    </p>
                                                                    <p class="text-xs text-muted-foreground">
                                                                        PDF Document
                                                                        <span v-if="content.pdf_page_count"> • {{ content.pdf_page_count }} pages</span>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Content Settings -->
                                                    <div class="flex items-center gap-6 pt-2 border-t">
                                                        <div class="flex items-center space-x-2">
                                                            <Switch
                                                                :checked="content.is_required"
                                                                @update:checked="content.is_required = $event"
                                                            />
                                                            <Label class="text-sm">Required content</Label>
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            <Switch
                                                                :checked="content.is_active"
                                                                @update:checked="content.is_active = $event"
                                                            />
                                                            <Label class="text-sm">Active content</Label>
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </CardContent>
                </Card>

                <!-- Guidelines -->
                <Alert>
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                        <strong>Important:</strong> Make sure your videos are accessible and PDFs are properly formatted.
                        <strong>PDF page count is required</strong> for accurate progress tracking and reading time estimates.
                        Students will access content sequentially based on module order.
                    </AlertDescription>
                </Alert>

                <!-- Submit Actions -->
                <div class="flex justify-between items-center py-6">
                    <Button as-child variant="outline">
                        <Link href="/admin/course-online">
                            Cancel
                        </Link>
                    </Button>

                    <div class="flex items-center gap-4">
                        <div v-if="form.modules.length > 0" class="text-sm text-muted-foreground hidden sm:block">
                            {{ form.modules.length }} modules • {{ totalContentItems }} content items
                        </div>
                        <Button
                            type="submit"
                            :disabled="form.processing || !canSubmit"
                            class="min-w-48"
                        >
                            <Save class="h-4 w-4 mr-2" />
                            {{ form.processing ? 'Creating Course...' : 'Create Complete Course' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
