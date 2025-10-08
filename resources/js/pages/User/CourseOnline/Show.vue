<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Separator } from '@/components/ui/separator'

// Icons
import {
    ArrowLeft,
    Play,
    CheckCircle,
    Lock,
    Clock,
    FileText,
    Video,
    BookOpen,
    ChevronDown,
    ChevronRight,
    Target,
    Award,
    TrendingUp,
    User,
    Calendar
} from 'lucide-vue-next'

interface Course {
    id: number
    name: string
    description: string
    image_path: string | null
    difficulty_level: string
    estimated_duration: number
    is_active: boolean
}

interface Assignment {
    id: number
    status: 'assigned' | 'in_progress' | 'completed'
    progress_percentage: number
    assigned_at: string
    started_at: string | null
    current_module_id: number | null
    time_spent: string
}

interface ContentProgress {
    is_completed: boolean
    completion_percentage: number
    time_spent: number
    current_position: number
    last_accessed: string | null
}

interface Content {
    id: number
    title: string
    content_type: 'video' | 'pdf'
    order_number: number
    is_required: boolean
    duration: number | null
    is_unlocked: boolean
    progress: ContentProgress
    video: {
        id: number
        name: string
        thumbnail_url: string | null
        duration: number
        formatted_duration: string
    } | null
    pdf_name: string | null
}

interface ModuleProgress {
    total_content: number
    completed_content: number
    completion_percentage: number
    is_completed: boolean
}

interface Module {
    id: number
    name: string
    description: string
    order_number: number
    estimated_duration: number | null
    is_required: boolean
    is_unlocked: boolean
    progress: ModuleProgress
    content: Content[]
}

const props = defineProps<{
    course: Course
    assignment: Assignment
    modules: Module[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Courses', href: '/courses-online' },
    { title: props.course.name, href: '#' }
]

// State
const openModules = ref<Set<number>>(new Set())
const currentContent = ref<Content | null>(null)
const isStarting = ref(false)

// Computed
const completedModules = computed(() =>
    props.modules.filter(module => module.progress.is_completed).length
)

const totalModules = computed(() => props.modules.length)

const nextContent = computed(() => {
    for (const module of props.modules) {
        if (!module.is_unlocked) continue

        for (const content of module.content) {
            if (content.is_unlocked && !content.progress.is_completed) {
                return { content, module }
            }
        }
    }
    return null
})

const canComplete = computed(() => {
    const requiredModules = props.modules.filter(m => m.is_required)
    const completedRequiredModules = requiredModules.filter(m => m.progress.is_completed)
    return completedRequiredModules.length >= requiredModules.length
})

const timeSpentDisplay = computed(() => {
    if (!props.assignment.time_spent) return '0 minutes'
    return props.assignment.time_spent
})

// Methods
const getStatusColor = (status: string) => {
    switch (status) {
        case 'assigned': return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
        case 'in_progress': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
        case 'completed': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    }
}

const getStatusText = (status: string) => {
    switch (status) {
        case 'assigned': return 'Not Started'
        case 'in_progress': return 'In Progress'
        case 'completed': return 'Completed'
        default: return status
    }
}

const getDifficultyColor = (level: string) => {
    switch (level) {
        case 'beginner': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
        case 'intermediate': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
        case 'advanced': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    }
}

const toggleModule = (moduleId: number) => {
    if (openModules.value.has(moduleId)) {
        openModules.value.delete(moduleId)
    } else {
        openModules.value.add(moduleId)
    }
}

const startCourse = async () => {
    if (isStarting.value) return

    isStarting.value = true

    try {
        await router.post(route('courses-online.start', props.course.id))

        // Reload page to get updated assignment status
        router.reload()
    } catch (error) {
        console.error('Failed to start course:', error)
    } finally {
        isStarting.value = false
    }
}

const completeCourse = async () => {
    if (!canComplete.value) return

    try {
        await router.post(route('courses-online.complete', props.course.id))

        // Reload page to get updated assignment status
        router.reload()
    } catch (error) {
        console.error('Failed to complete course:', error)
        alert('Failed to complete course. Please try again.')
    }
}

const viewContent = (content: Content, module: Module) => {
    if (!content.is_unlocked) {
        alert('This content is not yet unlocked. Complete previous content first.')
        return
    }

    // Navigate to content viewer
    router.visit(route('content.show', content.id))
}

const continueWhere = () => {
    if (nextContent.value) {
        viewContent(nextContent.value.content, nextContent.value.module)
    }
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    })
}

const formatDuration = (seconds: number | null) => {
    if (!seconds) return 'Unknown'

    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = seconds % 60

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
    }
    return `${minutes}:${secs.toString().padStart(2, '0')}`
}

const getContentIcon = (contentType: string) => {
    return contentType === 'video' ? Video : FileText
}

const getProgressColor = (percentage: number) => {
    if (percentage >= 90) return 'bg-green-500'
    if (percentage >= 60) return 'bg-blue-500'
    if (percentage >= 30) return 'bg-yellow-500'
    return 'bg-gray-300'
}

// Auto-open current module on mount
onMounted(() => {
    // Open the current module or first incomplete module
    const currentModuleId = props.assignment.current_module_id
    if (currentModuleId) {
        openModules.value.add(currentModuleId)
    } else {
        // Open first incomplete module
        const firstIncomplete = props.modules.find(m => !m.progress.is_completed && m.is_unlocked)
        if (firstIncomplete) {
            openModules.value.add(firstIncomplete.id)
        }
    }
})
</script>

<template>
    <Head :title="course.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Course Header -->
            <div class="flex items-start gap-6">
                <!-- Course Image -->
                <div v-if="course.image_path" class="flex-shrink-0">
                    <img
                        :src="course.image_path"
                        :alt="course.name"
                        class="w-32 h-24 rounded-lg object-cover border"
                    />
                </div>

                <!-- Course Info -->
                <div class="flex-1">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h1 class="text-3xl font-bold mb-2">{{ course.name }}</h1>
                            <p class="text-lg text-muted-foreground mb-4">{{ course.description }}</p>

                            <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                <div class="flex items-center gap-1">
                                    <Clock class="h-4 w-4" />
                                    {{ course.estimated_duration }} minutes
                                </div>
                                <Badge :class="getDifficultyColor(course.difficulty_level)">
                                    {{ course.difficulty_level }}
                                </Badge>
                                <div class="flex items-center gap-1">
                                    <BookOpen class="h-4 w-4" />
                                    {{ totalModules }} modules
                                </div>
                            </div>
                        </div>

                        <!-- Back Button -->
                        <Button asChild variant="outline">
                            <Link href="/courses-online">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Courses
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Progress Overview -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Status</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <Badge :class="getStatusColor(assignment.status)" class="mb-2">
                            {{ getStatusText(assignment.status) }}
                        </Badge>
                        <p class="text-xs text-muted-foreground">
                            {{ assignment.started_at ? `Started ${formatDate(assignment.started_at)}` : 'Not started yet' }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Progress</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold mb-2">{{ Math.round(assignment.progress_percentage) }}%</div>
                        <Progress :value="assignment.progress_percentage" class="mb-2" />
                        <p class="text-xs text-muted-foreground">
                            {{ completedModules }}/{{ totalModules }} modules completed
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Time Spent</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold mb-2">{{ timeSpentDisplay }}</div>
                        <p class="text-xs text-muted-foreground">Learning time</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Actions</CardTitle>
                        <User class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <!-- Start Course -->
                        <Button
                            v-if="assignment.status === 'assigned'"
                            @click="startCourse"
                            :disabled="isStarting"
                            class="w-full mb-2"
                        >
                            <Play class="h-4 w-4 mr-2" />
                            {{ isStarting ? 'Starting...' : 'Start Course' }}
                        </Button>

                        <!-- Continue Learning -->
                        <Button
                            v-else-if="assignment.status === 'in_progress' && nextContent"
                            @click="continueWhere"
                            class="w-full mb-2"
                        >
                            <Play class="h-4 w-4 mr-2" />
                            Continue Learning
                        </Button>

                        <!-- Complete Course -->
                        <Button
                            v-if="assignment.status === 'in_progress' && canComplete"
                            @click="completeCourse"
                            variant="default"
                            class="w-full"
                        >
                            <Award class="h-4 w-4 mr-2" />
                            Complete Course
                        </Button>

                        <!-- Already Completed -->
                        <div v-if="assignment.status === 'completed'" class="text-center">
                            <CheckCircle class="h-8 w-8 mx-auto text-green-600 mb-2" />
                            <p class="text-sm font-medium text-green-600">Course Completed!</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Next Content Alert -->
            <Alert v-if="nextContent && assignment.status === 'in_progress'" class="border-blue-200 bg-blue-50 dark:bg-blue-950">
                <Play class="h-4 w-4" />
                <AlertDescription>
                    <div class="flex items-center justify-between">
                        <div>
                            <strong>Continue where you left off:</strong>
                            <span class="ml-2">{{ nextContent.content.title }}</span>
                            <span class="text-muted-foreground ml-2">in {{ nextContent.module.name }}</span>
                        </div>
                        <Button @click="continueWhere" size="sm">
                            Continue
                        </Button>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- Course Modules -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <BookOpen class="h-5 w-5 text-primary" />
                        Course Modules
                    </CardTitle>
                    <CardDescription>
                        Complete modules in order to progress through the course
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        v-for="(module, index) in modules"
                        :key="module.id"
                        class="border rounded-lg"
                        :class="{
                            'border-green-200 bg-green-50 dark:bg-green-950': module.progress.is_completed,
                            'border-blue-200 bg-blue-50 dark:bg-blue-950': !module.progress.is_completed && module.is_unlocked,
                            'border-gray-200 bg-gray-50 dark:bg-gray-900': !module.is_unlocked
                        }"
                    >
                        <Collapsible>
                            <CollapsibleTrigger
                                @click="toggleModule(module.id)"
                                class="w-full p-4 text-left hover:bg-accent/50 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <!-- Module Number -->
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold"
                                             :class="{
                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': module.progress.is_completed,
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': !module.progress.is_completed && module.is_unlocked,
                                                'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400': !module.is_unlocked
                                            }"
                                        >
                                            <CheckCircle v-if="module.progress.is_completed" class="w-4 h-4" />
                                            <Lock v-else-if="!module.is_unlocked" class="w-4 h-4" />
                                            <span v-else>{{ module.order_number }}</span>
                                        </div>

                                        <!-- Module Info -->
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-lg">{{ module.name }}</h3>
                                            <p v-if="module.description" class="text-sm text-muted-foreground">{{ module.description }}</p>
                                            <div class="flex items-center gap-4 mt-2 text-sm text-muted-foreground">
                                                <span>{{ module.content.length }} content items</span>
                                                <span v-if="module.estimated_duration">~{{ module.estimated_duration }} minutes</span>
                                                <Badge v-if="module.is_required" variant="outline" class="text-xs">Required</Badge>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <!-- Progress -->
                                        <div class="text-right min-w-[100px]">
                                            <div class="text-sm font-medium">
                                                {{ Math.round(module.progress.completion_percentage) }}%
                                            </div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ module.progress.completed_content }}/{{ module.progress.total_content }}
                                            </div>
                                        </div>

                                        <!-- Expand Icon -->
                                        <ChevronDown
                                            class="h-4 w-4 transition-transform"
                                            :class="{ 'rotate-180': openModules.has(module.id) }"
                                        />
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mt-3">
                                    <Progress :value="module.progress.completion_percentage" class="h-2" />
                                </div>
                            </CollapsibleTrigger>

                            <CollapsibleContent v-if="openModules.has(module.id)">
                                <Separator />
                                <div class="p-4 space-y-2">
                                    <div
                                        v-for="content in module.content"
                                        :key="content.id"
                                        @click="viewContent(content, module)"
                                        class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                                        :class="{
                                            'bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:hover:bg-green-800': content.progress.is_completed,
                                            'bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800': !content.progress.is_completed && content.is_unlocked,
                                            'bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 cursor-not-allowed': !content.is_unlocked
                                        }"
                                    >
                                        <!-- Content Icon -->
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                             :class="{
                                                'bg-green-200 text-green-700 dark:bg-green-800 dark:text-green-300': content.progress.is_completed,
                                                'bg-blue-200 text-blue-700 dark:bg-blue-800 dark:text-blue-300': !content.progress.is_completed && content.is_unlocked,
                                                'bg-gray-300 text-gray-500 dark:bg-gray-600 dark:text-gray-400': !content.is_unlocked
                                            }"
                                        >
                                            <CheckCircle v-if="content.progress.is_completed" class="w-4 h-4" />
                                            <Lock v-else-if="!content.is_unlocked" class="w-4 h-4" />
                                            <component v-else :is="getContentIcon(content.content_type)" class="w-4 h-4" />
                                        </div>

                                        <!-- Content Info -->
                                        <div class="flex-1">
                                            <div class="font-medium">{{ content.title }}</div>
                                            <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                                <span class="capitalize">{{ content.content_type }}</span>
                                                <span v-if="content.video">{{ content.video.formatted_duration }}</span>
                                                <Badge v-if="content.is_required" variant="outline" class="text-xs">Required</Badge>
                                            </div>
                                        </div>

                                        <!-- Progress Indicator -->
                                        <div class="text-right">
                                            <div v-if="content.progress.is_completed" class="text-green-600 font-medium text-sm">
                                                Complete
                                            </div>
                                            <div v-else-if="content.progress.completion_percentage > 0" class="text-blue-600 font-medium text-sm">
                                                {{ Math.round(content.progress.completion_percentage) }}%
                                            </div>
                                            <div v-else-if="content.is_unlocked" class="text-gray-500 text-sm">
                                                Not started
                                            </div>
                                            <div v-else class="text-gray-400 text-sm">
                                                🔒 Locked
                                            </div>
                                        </div>

                                        <!-- Action Arrow -->
                                        <ChevronRight v-if="content.is_unlocked" class="h-4 w-4 text-muted-foreground" />
                                    </div>
                                </div>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </CardContent>
            </Card>

            <!-- Course Completion -->
        </div>
    </AppLayout>
</template>
