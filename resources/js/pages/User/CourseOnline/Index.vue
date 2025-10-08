<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'

// Icons
import {
    BookOpen,
    Clock,
    Play,
    CheckCircle,
    AlertCircle,
    Target,
    TrendingUp,
    Calendar,
    Award,
    Eye,
    BarChart3
} from 'lucide-vue-next'

interface Assignment {
    id: number
    course: {
        id: number
        name: string
        description: string
        image_path: string | null
        difficulty_level: string
        estimated_duration: number
        modules_count: number
    }
    status: 'assigned' | 'in_progress' | 'completed'
    progress_percentage: number
    assigned_at: string
    started_at: string | null
    completed_at: string | null
    current_module: {
        id: number
        name: string
        order_number: number
    } | null
    time_spent: string
    next_content: {
        id: number
        title: string
        content_type: string
        module_name: string
    } | null
}

interface Stats {
    total_assignments: number
    completed_courses: number
    in_progress_courses: number
    total_hours_spent: number
    average_completion_rate: number
    certificates_earned: number
}

// ✅ FIX: Add default values and make stats optional
const props = defineProps<{
    assignments?: Assignment[]
    stats?: Stats
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Courses', href: '#' }
]

// ✅ FIX: Safe computed with defaults
const assignments = computed(() => props.assignments || [])

const stats = computed(() => ({
    total_assignments: props.stats?.total_assignments || 0,
    completed_courses: props.stats?.completed_courses || 0,
    in_progress_courses: props.stats?.in_progress_courses || 0,
    total_hours_spent: props.stats?.total_hours_spent || 0,
    average_completion_rate: props.stats?.average_completion_rate || 0,
    certificates_earned: props.stats?.certificates_earned || 0,
}))

const activeAssignments = computed(() =>
    assignments.value.filter(a => a.status === 'in_progress')
)

const completedAssignments = computed(() =>
    assignments.value.filter(a => a.status === 'completed')
)

const notStartedAssignments = computed(() =>
    assignments.value.filter(a => a.status === 'assigned')
)

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

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    })
}

const getProgressColor = (percentage: number) => {
    if (percentage >= 80) return 'bg-green-500'
    if (percentage >= 50) return 'bg-blue-500'
    if (percentage >= 25) return 'bg-yellow-500'
    return 'bg-gray-300'
}

const getActionButton = (assignment: Assignment) => {
    switch (assignment.status) {
        case 'assigned':
            return { text: 'Start Course', variant: 'default', icon: Play }
        case 'in_progress':
            return { text: 'Continue Learning', variant: 'default', icon: Play }
        case 'completed':
            return { text: 'Review Course', variant: 'outline', icon: Eye }
        default:
            return { text: 'View Course', variant: 'outline', icon: Eye }
    }
}
</script>

<template>
    <Head title="My Learning Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold tracking-tight">My Learning Dashboard</h1>
                <p class="text-muted-foreground">Track your progress and continue your learning journey</p>
            </div>

            <!-- ✅ FIX: Safe stats access -->
            <div class="grid gap-4 md:grid-cols-4 lg:grid-cols-5">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Courses</CardTitle>
                        <BookOpen class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_assignments }}</div>
                        <p class="text-xs text-muted-foreground">Assigned to you</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">In Progress</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.in_progress_courses }}</div>
                        <p class="text-xs text-muted-foreground">Currently learning</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Completed</CardTitle>
                        <CheckCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.completed_courses }}</div>
                        <p class="text-xs text-muted-foreground">Successfully finished</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Hours Spent</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ Math.round(stats.total_hours_spent) }}</div>
                        <p class="text-xs text-muted-foreground">Learning time</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Avg. Progress</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ Math.round(stats.average_completion_rate) }}%</div>
                        <p class="text-xs text-muted-foreground">Overall completion</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ✅ FIX: Show message if no assignments -->
            <div v-if="assignments.length === 0" class="text-center py-12 border-2 border-dashed border-input rounded-lg">
                <BookOpen class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                <h3 class="text-lg font-medium mb-2">No courses assigned yet</h3>
                <p class="text-muted-foreground">Contact your administrator to get access to courses</p>
            </div>

            <!-- Course Tabs -->
            <Tabs v-else default-value="in-progress" class="space-y-4">
                <TabsList class="grid w-full grid-cols-3">
                    <TabsTrigger value="in-progress">
                        In Progress ({{ activeAssignments.length }})
                    </TabsTrigger>
                    <TabsTrigger value="not-started">
                        Not Started ({{ notStartedAssignments.length }})
                    </TabsTrigger>
                    <TabsTrigger value="completed">
                        Completed ({{ completedAssignments.length }})
                    </TabsTrigger>
                </TabsList>

                <!-- In Progress Tab -->
                <TabsContent value="in-progress" class="space-y-4">
                    <div v-if="activeAssignments.length === 0" class="text-center py-12 border-2 border-dashed border-input rounded-lg">
                        <TrendingUp class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium mb-2">No courses in progress</h3>
                        <p class="text-muted-foreground mb-4">Start a new course to begin your learning journey</p>
                    </div>

                    <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <Card
                            v-for="assignment in activeAssignments"
                            :key="assignment.id"
                            class="hover:shadow-lg transition-shadow"
                        >
                            <CardHeader class="pb-3">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <CardTitle class="text-lg line-clamp-2">{{ assignment.course.name }}</CardTitle>
                                        <CardDescription class="line-clamp-2">{{ assignment.course.description }}</CardDescription>
                                    </div>
                                    <Badge :class="getStatusColor(assignment.status)" class="ml-2">
                                        {{ getStatusText(assignment.status) }}
                                    </Badge>
                                </div>
                            </CardHeader>

                            <CardContent class="space-y-4">
                                <!-- Course Image -->
                                <div v-if="assignment.course.image_path" class="aspect-video rounded-lg overflow-hidden bg-muted">
                                    <img
                                        :src="assignment.course.image_path"
                                        :alt="assignment.course.name"
                                        class="w-full h-full object-cover"
                                    />
                                </div>

                                <!-- Progress -->
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Progress</span>
                                        <span class="font-medium">{{ Math.round(assignment.progress_percentage) }}%</span>
                                    </div>
                                    <Progress :value="assignment.progress_percentage" />
                                </div>

                                <!-- Course Details -->
                                <div class="flex items-center justify-between text-sm text-muted-foreground">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center gap-1">
                                            <Clock class="h-3 w-3" />
                                            {{ assignment.course.estimated_duration }}min
                                        </div>
                                        <Badge :class="getDifficultyColor(assignment.course.difficulty_level)" class="text-xs">
                                            {{ assignment.course.difficulty_level }}
                                        </Badge>
                                    </div>
                                </div>

                                <!-- Next Content -->
                                <div v-if="assignment.next_content" class="text-sm">
                                    <div class="text-muted-foreground mb-1">Continue with:</div>
                                    <div class="font-medium">{{ assignment.next_content.title }}</div>
                                    <div class="text-xs text-muted-foreground">{{ assignment.next_content.module_name }}</div>
                                </div>

                                <!-- Action Button -->
                                <Button asChild class="w-full" :variant="getActionButton(assignment).variant">
                                    <Link :href="route('courses-online.show', assignment.course.id)">
                                        <component :is="getActionButton(assignment).icon" class="mr-2 h-4 w-4" />
                                        {{ getActionButton(assignment).text }}
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Not Started Tab -->
                <TabsContent value="not-started" class="space-y-4">
                    <div v-if="notStartedAssignments.length === 0" class="text-center py-12 border-2 border-dashed border-input rounded-lg">
                        <BookOpen class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium mb-2">All courses started!</h3>
                        <p class="text-muted-foreground">Great job! You've begun all your assigned courses.</p>
                    </div>

                    <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <Card
                            v-for="assignment in notStartedAssignments"
                            :key="assignment.id"
                            class="hover:shadow-lg transition-shadow"
                        >
                            <CardHeader class="pb-3">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <CardTitle class="text-lg line-clamp-2">{{ assignment.course.name }}</CardTitle>
                                        <CardDescription class="line-clamp-2">{{ assignment.course.description }}</CardDescription>
                                    </div>
                                    <Badge :class="getStatusColor(assignment.status)" class="ml-2">
                                        {{ getStatusText(assignment.status) }}
                                    </Badge>
                                </div>
                            </CardHeader>

                            <CardContent class="space-y-4">
                                <!-- Course Details -->
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center gap-1 text-muted-foreground">
                                            <Clock class="h-3 w-3" />
                                            {{ assignment.course.estimated_duration }}min
                                        </div>
                                        <div class="flex items-center gap-1 text-muted-foreground">
                                            <BookOpen class="h-3 w-3" />
                                            {{ assignment.course.modules_count }} modules
                                        </div>
                                    </div>
                                    <Badge :class="getDifficultyColor(assignment.course.difficulty_level)" class="text-xs">
                                        {{ assignment.course.difficulty_level }}
                                    </Badge>
                                </div>

                                <!-- Assigned Date -->
                                <div class="text-sm text-muted-foreground">
                                    <Calendar class="h-3 w-3 inline mr-1" />
                                    Assigned {{ formatDate(assignment.assigned_at) }}
                                </div>

                                <!-- Action Button -->
                                <Button asChild class="w-full">
                                    <Link :href="route('courses-online.show', assignment.course.id)">
                                        <Play class="mr-2 h-4 w-4" />
                                        Start Course
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Completed Tab -->
                <TabsContent value="completed" class="space-y-4">
                    <div v-if="completedAssignments.length === 0" class="text-center py-12 border-2 border-dashed border-input rounded-lg">
                        <CheckCircle class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium mb-2">No completed courses yet</h3>
                        <p class="text-muted-foreground">Complete your first course to earn your certificate!</p>
                    </div>

                    <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <Card
                            v-for="assignment in completedAssignments"
                            :key="assignment.id"
                            class="hover:shadow-lg transition-shadow border-green-200 dark:border-green-800"
                        >
                            <CardContent class="p-6 space-y-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-semibold">{{ assignment.course.name }}</h3>
                                        <p class="text-sm text-muted-foreground">{{ assignment.course.description }}</p>
                                    </div>
                                    <Badge :class="getStatusColor(assignment.status)">
                                        <CheckCircle class="h-3 w-3 mr-1" />
                                        Completed
                                    </Badge>
                                </div>

                                <!-- Completion Details -->
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Completed:</span>
                                        <span class="font-medium">{{ formatDate(assignment.completed_at!) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Time Spent:</span>
                                        <span class="font-medium">{{ assignment.time_spent }}</span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex gap-2">
                                    <Button asChild variant="outline" class="flex-1">
                                        <Link :href="route('courses-online.show', assignment.course.id)">
                                            <Eye class="mr-2 h-4 w-4" />
                                            Review
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
