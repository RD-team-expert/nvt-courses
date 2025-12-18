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
    Calendar,
    AlarmClock,
    CalendarDays,
    Timer,
    AlertTriangle
} from 'lucide-vue-next'

interface Course {
    id: number
    name: string
    description: string
    image_path: string | null
    difficulty_level: string
    estimated_duration: number
    is_active: boolean
    has_deadline: boolean
    deadline: string | null
    deadline_type: 'flexible' | 'strict'
}

interface Assignment {
    id: number
    status: 'assigned' | 'in_progress' | 'completed'
    progress_percentage: number
    assigned_at: string
    started_at: string | null
    current_module_id: number | null
    time_spent: string
    time_spent_minutes: number
    deadline: string | null
    is_overdue: boolean
    deadline_info: {
        status: 'no_deadline' | 'overdue' | 'due_today' | 'due_tomorrow' | 'due_soon' | 'due_this_week' | 'upcoming'
        message: string
        days_remaining: number | null
        urgency_level: 'none' | 'low' | 'medium' | 'high' | 'critical'
        formatted_deadline: string | null
        is_overdue: boolean
        time_remaining: string
    }
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

interface QuizStatus {
    has_quiz: boolean
    quiz_required: boolean
    quiz_id?: number
    quiz_title?: string
    passed: boolean
    status: string
    attempts_used: number
    max_attempts: number
    can_attempt: boolean
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
    quiz_status?: QuizStatus
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

// Deadline computed properties
const hasDeadline = computed(() => props.course.has_deadline && props.assignment.deadline)

const deadlineStatus = computed(() => props.assignment.deadline_info.status)

const isUrgentDeadline = computed(() =>
    ['overdue', 'due_today', 'due_tomorrow'].includes(deadlineStatus.value)
)

const deadlineColor = computed(() => {
    switch (deadlineStatus.value) {
        case 'overdue': return 'border-red-200 bg-red-50 dark:bg-red-950/20 dark:border-red-800'
        case 'due_today': return 'border-red-200 bg-red-50 dark:bg-red-950/20 dark:border-red-800'
        case 'due_tomorrow': return 'border-orange-200 bg-orange-50 dark:bg-orange-950/20 dark:border-orange-800'
        case 'due_soon': return 'border-orange-200 bg-orange-50 dark:bg-orange-950/20 dark:border-orange-800'
        case 'due_this_week': return 'border-yellow-200 bg-yellow-50 dark:bg-yellow-950/20 dark:border-yellow-800'
        case 'upcoming': return 'border-blue-200 bg-blue-50 dark:bg-blue-950/20 dark:border-blue-800'
        default: return 'border-gray-200 bg-gray-50 dark:bg-gray-950/20 dark:border-gray-800'
    }
})

const deadlineIcon = computed(() => {
    switch (deadlineStatus.value) {
        case 'overdue': return AlertTriangle
        case 'due_today':
        case 'due_tomorrow': return AlarmClock
        case 'due_soon':
        case 'due_this_week': return Timer
        case 'upcoming': return CalendarDays
        default: return Calendar
    }
})

const deadlineTextColor = computed(() => {
    switch (deadlineStatus.value) {
        case 'overdue': return 'text-red-700 dark:text-red-300'
        case 'due_today':
        case 'due_tomorrow': return 'text-red-700 dark:text-red-300'
        case 'due_soon': return 'text-orange-700 dark:text-orange-300'
        case 'due_this_week': return 'text-yellow-700 dark:text-yellow-300'
        case 'upcoming': return 'text-blue-700 dark:text-blue-300'
        default: return 'text-gray-700 dark:text-gray-300'
    }
})

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
        router.reload()
    } catch (error) {
        console.error('Failed to start course:', error)
    } finally {
        isStarting.value = false
    }
}

const completeCourse = async () => {
    if (!canComplete.value) {
        alert('⚠️ You must complete all required modules before finishing the course.')
        return
    }

    if (!confirm('🎓 Are you ready to complete this course?')) {
        return
    }

    try {
        await router.post(route('courses-online.complete', props.course.id))
        router.reload()
    } catch (error) {
        alert('Failed to complete course. Please try again.')
    }
}

const viewContent = (content: Content, module: Module) => {
    if (!content.is_unlocked) {
        alert('This content is not yet unlocked. Complete previous content first.')
        return
    }
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
    const currentModuleId = props.assignment.current_module_id
    if (currentModuleId) {
        openModules.value.add(currentModuleId)
    } else {
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
        <!-- ✅ FIXED: Added proper padding and max-width constraints for mobile -->
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
            <!-- ✅ FIXED: Urgent Deadline Alert - Better mobile layout -->
            <Alert v-if="isUrgentDeadline" :class="deadlineColor">
                <component :is="deadlineIcon" class="h-4 w-4 flex-shrink-0" />
                <AlertDescription>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex-1 min-w-0">
                            <strong class="text-red-800 dark:text-red-300 block sm:inline">
                                {{ deadlineStatus === 'overdue' ? '🚨 OVERDUE:' : '⚠️ URGENT:' }}
                            </strong>
                            <span class="ml-0 sm:ml-2 text-red-700 dark:text-red-300 block sm:inline break-words">
                                {{ assignment.deadline_info.message }}
                            </span>
                            <span v-if="assignment.deadline_info.formatted_deadline" class="block text-sm text-red-600 dark:text-red-400 mt-1">
                                Deadline was {{ assignment.deadline_info.formatted_deadline }}
                            </span>
                        </div>
                        <Button
                            v-if="assignment.status === 'in_progress' && nextContent"
                            @click="continueWhere"
                            size="sm"
                            variant="destructive"
                            class="w-full sm:w-auto flex-shrink-0"
                        >
                            Continue Now
                        </Button>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- ✅ FIXED: Course Header - Better mobile stacking -->
            <div class="flex flex-col gap-4">
                <!-- Course Image -->
                <div v-if="course.image_path" class="w-full sm:hidden">
                    <img
                        :src="course.image_path"
                        :alt="course.name"
                        class="w-full h-48 rounded-lg object-cover border"
                    />
                </div>

                <div class="flex flex-col sm:flex-row items-start gap-4 sm:gap-6">
                    <!-- Desktop Course Image -->
                    <div v-if="course.image_path" class="hidden sm:block flex-shrink-0">
                        <img
                            :src="course.image_path"
                            :alt="course.name"
                            class="w-32 h-24 rounded-lg object-cover border"
                        />
                    </div>

                    <!-- Course Info -->
                    <div class="flex-1 min-w-0 w-full">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-4">
                            <div class="flex-1 min-w-0">
                                <h1 class="text-2xl sm:text-3xl font-bold mb-2 break-words">{{ course.name }}</h1>
                                <p class="text-base sm:text-lg text-muted-foreground mb-3 sm:mb-4 break-words">{{ course.description }}</p>

                                <!-- ✅ FIXED: Better mobile wrapping for badges and info -->
                                <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                    <div class="flex items-center gap-1 whitespace-nowrap">
                                        <Clock class="h-4 w-4 flex-shrink-0" />
                                        <span>{{ course.estimated_duration }} min</span>
                                    </div>
                                    <Badge :class="getDifficultyColor(course.difficulty_level)" class="whitespace-nowrap">
                                        {{ course.difficulty_level }}
                                    </Badge>
                                    <div class="flex items-center gap-1 whitespace-nowrap">
                                        <BookOpen class="h-4 w-4 flex-shrink-0" />
                                        <span>{{ totalModules }} modules</span>
                                    </div>
                                    <Badge v-if="course.has_deadline" variant="outline" :class="course.deadline_type === 'strict' ? 'border-red-300 text-red-600 dark:border-red-700 dark:text-red-400' : 'border-blue-300 text-blue-600 dark:border-blue-700 dark:text-blue-400'" class="whitespace-nowrap">
                                        {{ course.deadline_type === 'strict' ? 'Strict' : 'Flexible' }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Back Button -->
                            <Button asChild variant="outline" size="sm" class="w-full sm:w-auto flex-shrink-0">
                                <Link href="/courses-online" class="flex items-center justify-center">
                                    <ArrowLeft class="h-4 w-4 mr-2" />
                                    Back
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ FIXED: Progress Overview - Responsive grid with proper mobile stacking -->
            <div class="grid gap-3 sm:gap-4" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
                <Card class="overflow-hidden">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Status</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                    </CardHeader>
                    <CardContent>
                        <Badge :class="getStatusColor(assignment.status)" class="mb-2">
                            {{ getStatusText(assignment.status) }}
                        </Badge>
                        <p class="text-xs text-muted-foreground break-words">
                            {{ assignment.started_at ? `Started ${formatDate(assignment.started_at)}` : 'Not started yet' }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="overflow-hidden">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Progress</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold mb-2">{{ Math.round(assignment.progress_percentage) }}%</div>
                        <Progress :value="assignment.progress_percentage" class="mb-2" />
                        <p class="text-xs text-muted-foreground">
                            {{ completedModules }}/{{ totalModules }} modules
                        </p>
                    </CardContent>
                </Card>



                <!-- Deadline Card -->
                <Card v-if="hasDeadline" :class="deadlineColor" class="overflow-hidden">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Deadline</CardTitle>
                        <component :is="deadlineIcon" class="h-4 w-4 flex-shrink-0" :class="deadlineTextColor" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-base sm:text-lg font-bold mb-2 break-words" :class="deadlineTextColor">
                            {{ assignment.deadline_info.time_remaining }}
                        </div>
                        <p class="text-xs break-words" :class="deadlineTextColor">
                            {{ assignment.deadline_info.formatted_deadline }}
                        </p>
                        <p class="text-xs text-muted-foreground mt-1 break-words">
                            {{ course.deadline_type === 'strict' ? 'Blocked after' : 'Late tracked' }}
                        </p>
                    </CardContent>
                </Card>

                <Card class="overflow-hidden">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Actions</CardTitle>
                        <User class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                    </CardHeader>
                    <CardContent>
                        <!-- Start Course -->
                        <Button
                            v-if="assignment.status === 'assigned'"
                            @click="startCourse"
                            :disabled="isStarting"
                            class="w-full mb-2"
                        >
                            <Play class="h-4 w-4 mr-2 flex-shrink-0" />
                            {{ isStarting ? 'Starting...' : 'Start' }}
                        </Button>

                        <!-- Continue Learning -->
                        <Button
                            v-else-if="assignment.status === 'in_progress' && nextContent"
                            @click="continueWhere"
                            class="w-full mb-2"
                            :variant="isUrgentDeadline ? 'destructive' : 'default'"
                        >
                            <Play class="h-4 w-4 mr-2 flex-shrink-0" />
                            Continue
                        </Button>

                        <!-- Complete Course -->
                        <Button
                            v-if="assignment.status === 'in_progress' && canComplete"
                            @click="completeCourse"
                            variant="default"
                            class="w-full"
                        >
                            <Award class="h-4 w-4 mr-2 flex-shrink-0" />
                            Complete
                        </Button>

                        <!-- Already Completed -->
                        <div v-if="assignment.status === 'completed'" class="text-center">
                            <CheckCircle class="h-8 w-8 mx-auto text-green-600 mb-2" />
                            <p class="text-sm font-medium text-green-600">Completed!</p>
                            <p v-if="hasDeadline" class="text-xs text-muted-foreground mt-1 break-words">
                                {{ assignment.deadline_info.is_overdue ? 'After deadline' : 'On time' }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ✅ FIXED: Deadline Information Card - Better mobile text wrapping -->
            <Card v-if="hasDeadline && !isUrgentDeadline" :class="deadlineColor" class="overflow-hidden">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base sm:text-lg break-words" :class="deadlineTextColor">
                        <component :is="deadlineIcon" class="h-5 w-5 flex-shrink-0" />
                        Deadline Info
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-muted-foreground mb-1">Status</div>
                            <div class="font-medium break-words" :class="deadlineTextColor">
                                {{ assignment.deadline_info.message }}
                            </div>
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-muted-foreground mb-1">Due Date</div>
                            <div class="font-medium break-words">
                                {{ assignment.deadline_info.formatted_deadline }}
                            </div>
                        </div>
                        <div class="min-w-0 sm:col-span-2 lg:col-span-1">
                            <div class="text-sm font-medium text-muted-foreground mb-1">Type</div>
                            <div class="font-medium break-words">
                                {{ course.deadline_type === 'strict' ? 'Strict (Blocked after)' : 'Flexible (Late allowed)' }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- ✅ FIXED: Next Content Alert - Better mobile layout -->
            <Alert v-if="nextContent && assignment.status === 'in_progress'" class="border-blue-200 bg-blue-50 dark:bg-blue-950 overflow-hidden">
                <Play class="h-4 w-4 flex-shrink-0" />
                <AlertDescription>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex-1 min-w-0">
                            <strong class="block sm:inline">Continue:</strong>
                            <span class="ml-0 sm:ml-2 block sm:inline break-words">{{ nextContent.content.title }}</span>
                            <span class="text-muted-foreground block sm:inline sm:ml-2">in {{ nextContent.module.name }}</span>
                        </div>
                        <Button @click="continueWhere" size="sm" :variant="isUrgentDeadline ? 'destructive' : 'default'" class="w-full sm:w-auto flex-shrink-0">
                            Continue
                        </Button>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- ✅ FIXED: Course Modules - Better mobile collapsible layout -->
            <Card class="overflow-hidden">
                <CardHeader>
                    <CardTitle class="flex flex-wrap items-center gap-2 text-base sm:text-lg">
                        <BookOpen class="h-5 w-5 text-primary flex-shrink-0" />
                        <span>Modules</span>
                        <Badge v-if="isUrgentDeadline" variant="destructive" class="whitespace-nowrap">
                            {{ deadlineStatus === 'overdue' ? 'Overdue!' : 'Due Soon!' }}
                        </Badge>
                    </CardTitle>
                    <CardDescription class="break-words">
                        Complete modules in order
                        <span v-if="hasDeadline" :class="deadlineTextColor">
                            · {{ assignment.deadline_info.time_remaining }}
                        </span>
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3 sm:space-y-4">
                    <div
                        v-for="(module, index) in modules"
                        :key="module.id"
                        class="border rounded-lg overflow-hidden"
                        :class="{
                            'border-green-200 bg-green-50 dark:bg-green-950': module.progress.is_completed,
                            'border-blue-200 bg-blue-50 dark:bg-blue-950': !module.progress.is_completed && module.is_unlocked,
                            'border-gray-200 bg-gray-50 dark:bg-gray-900': !module.is_unlocked
                        }"
                    >
                        <Collapsible :open="openModules.has(module.id)">
                            <CollapsibleTrigger
                                @click="toggleModule(module.id)"
                                class="w-full p-3 sm:p-4 text-left hover:bg-accent/50 transition-colors"
                            >
                                <div class="flex items-start gap-3">
                                    <!-- Module Number -->
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold flex-shrink-0"
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
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-base sm:text-lg break-words">{{ module.name }}</h3>
                                        <p v-if="module.description" class="text-sm text-muted-foreground mt-1 break-words">{{ module.description }}</p>
                                        <div class="flex flex-wrap items-center gap-2 sm:gap-4 mt-2 text-xs sm:text-sm text-muted-foreground">
                                            <span class="whitespace-nowrap">{{ module.content.length }} items</span>
                                            <span v-if="module.estimated_duration" class="whitespace-nowrap">~{{ module.estimated_duration }} min</span>
                                            <Badge v-if="module.is_required" variant="outline" class="text-xs">Required</Badge>
                                        </div>
                                    </div>

                                    <!-- Progress & Expand -->
                                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                        <div class="text-sm font-medium whitespace-nowrap">
                                            {{ Math.round(module.progress.completion_percentage) }}%
                                        </div>
                                        <div class="text-xs text-muted-foreground whitespace-nowrap">
                                            {{ module.progress.completed_content }}/{{ module.progress.total_content }}
                                        </div>
                                        <ChevronDown
                                            class="h-4 w-4 transition-transform mt-1"
                                            :class="{ 'rotate-180': openModules.has(module.id) }"
                                        />
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mt-3 ml-11">
                                    <Progress :value="module.progress.completion_percentage" class="h-2" />
                                </div>
                            </CollapsibleTrigger>

                            <CollapsibleContent v-if="openModules.has(module.id)">
                                <Separator />
                                <div class="p-3 sm:p-4 space-y-2">
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
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
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
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-sm sm:text-base break-words">{{ content.title }}</div>
                                            <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-xs sm:text-sm text-muted-foreground mt-1">
                                                <span class="capitalize whitespace-nowrap">{{ content.content_type }}</span>
                                                <span v-if="content.video" class="whitespace-nowrap">{{ content.video.formatted_duration }}</span>
                                                <Badge v-if="content.is_required" variant="outline" class="text-xs">Required</Badge>
                                            </div>
                                        </div>

                                        <!-- Progress Indicator -->
                                        <div class="text-right flex-shrink-0">
                                            <div v-if="content.progress.is_completed" class="text-green-600 font-medium text-xs sm:text-sm whitespace-nowrap">
                                                Complete
                                            </div>
                                            <div v-else-if="content.progress.completion_percentage > 0" class="text-blue-600 font-medium text-xs sm:text-sm whitespace-nowrap">
                                                {{ Math.round(content.progress.completion_percentage) }}%
                                            </div>
                                            <div v-else-if="content.is_unlocked" class="text-gray-500 text-xs sm:text-sm whitespace-nowrap">
                                                Start
                                            </div>
                                            <div v-else class="text-gray-400 text-xs sm:text-sm whitespace-nowrap">
                                                🔒 Locked
                                            </div>
                                        </div>

                                        <!-- Action Arrow -->
                                        <ChevronRight v-if="content.is_unlocked" class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                    </div>
                                    
                                    <!-- Module Quiz Section -->

                                    <div v-if="module.quiz_status && module.quiz_status.has_quiz" class="mt-4 pt-4 border-t">
                                        <!-- Clickable Quiz Link (when content is completed) -->
                                        <Link 
                                            v-if="module.progress.is_completed"
                                            :href="route('courses-online.modules.quiz.show', { courseOnline: course.id, courseModule: module.id })"
                                            class="flex items-center gap-3 p-3 rounded-lg transition-colors"
                                            :class="{
                                                'bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:hover:bg-green-800': module.quiz_status.passed,
                                                'bg-orange-100 hover:bg-orange-200 dark:bg-orange-900 dark:hover:bg-orange-800': !module.quiz_status.passed && module.quiz_status.can_attempt,
                                                'bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700': !module.quiz_status.can_attempt
                                            }"
                                        >
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                                 :class="{
                                                    'bg-green-200 text-green-700 dark:bg-green-800 dark:text-green-300': module.quiz_status.passed,
                                                    'bg-orange-200 text-orange-700 dark:bg-orange-800 dark:text-orange-300': !module.quiz_status.passed && module.quiz_status.can_attempt,
                                                    'bg-gray-300 text-gray-500 dark:bg-gray-600 dark:text-gray-400': !module.quiz_status.can_attempt
                                                }"
                                            >
                                                <CheckCircle v-if="module.quiz_status.passed" class="w-4 h-4" />
                                                <Target v-else class="w-4 h-4" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium text-sm sm:text-base">Module Quiz</div>
                                                <div class="text-xs sm:text-sm text-muted-foreground">
                                                    <span v-if="module.quiz_status.passed">Passed</span>
                                                    <span v-else-if="module.quiz_status.attempts_used > 0">
                                                        {{ module.quiz_status.attempts_used }}/{{ module.quiz_status.max_attempts }} attempts used
                                                    </span>
                                                    <span v-else>{{ module.quiz_status.quiz_required ? 'Required to proceed' : 'Optional' }}</span>
                                                </div>
                                            </div>
                                            <Badge v-if="module.quiz_status.quiz_required && !module.quiz_status.passed" variant="destructive" class="text-xs">
                                                Required
                                            </Badge>
                                            <ChevronRight class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                        </Link>

                                        <!-- Disabled Quiz (when content is not completed) -->
                                        <div 
                                            v-else
                                            class="flex items-center gap-3 p-3 rounded-lg bg-gray-100 dark:bg-gray-800 cursor-not-allowed opacity-60"
                                        >
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 bg-gray-300 text-gray-500 dark:bg-gray-600 dark:text-gray-400">
                                                <Lock class="w-4 h-4" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium text-sm sm:text-base text-gray-600 dark:text-gray-400">Module Quiz</div>
                                                <div class="text-xs sm:text-sm text-muted-foreground">
                                                    Complete all content first ({{ module.progress.completed_content }}/{{ module.progress.total_content }})
                                                </div>
                                            </div>
                                            <Badge variant="outline" class="text-xs border-gray-400 text-gray-600 dark:text-gray-400">
                                                Locked
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
