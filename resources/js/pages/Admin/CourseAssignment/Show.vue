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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Alert, AlertDescription } from '@/components/ui/alert'

// Icons
import {
    ArrowLeft,
    User,
    BookOpen,
    Clock,
    CheckCircle,
    AlertTriangle,
    TrendingUp,
    Play,
    FileText,
    Calendar,
    Target,
    Activity,
    Eye,
    Mail,
    BarChart3
} from 'lucide-vue-next'

interface Assignment {
    id: number
    course: {
        id: number
        name: string
        description: string
        difficulty_level: string
        estimated_duration: number
        modules_count: number
    }
    user: {
        id: number
        name: string
        email: string
    }
    assigned_by: {
        id: number
        name: string
    }
    status: 'assigned' | 'in_progress' | 'completed'
    progress_percentage: number
    assigned_at: string
    started_at: string | null
    completed_at: string | null
    time_spent: string
    current_module: {
        id: number
        name: string
        order_number: number
    } | null
}

interface ModuleProgress {
    id: number
    name: string
    order_number: number
    progress: {
        total_content: number
        completed_content: number
        completion_percentage: number
        is_unlocked: boolean
        is_completed: boolean
    }
}

interface UserProgress {
    id: number
    content: {
        id: number
        title: string
        content_type: string
        module_name: string
    }
    completion_percentage: number
    is_completed: boolean
    watch_time: number
    last_accessed: string | null
    completed_at: string | null
}

interface LearningSession {
    id: number
    content_title: string
    session_start: string
    session_end: string | null
    duration: string
    attention_score: number
    engagement_level: string
    is_suspicious: boolean
    cheating_risk: string
}

const props = defineProps<{
    assignment: Assignment
    moduleProgress: ModuleProgress[]
    userProgress: UserProgress[]
    learningSessions: LearningSession[]
}>()

// ✅ FIXED: Updated breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Online Courses', href: '/admin/course-online' },
    { title: 'Course Assignments', href: '/admin/course-assignments' },
    { title: 'Assignment Details', href: '#' }
]

// Button methods
const sendMessage = () => {
    alert(`Send message to ${props.assignment.user.name}`)
    // TODO: Implement actual messaging functionality
}

const viewAsStudent = () => {
    // Open course in new tab from student perspective
    window.open(`/courses-online/${props.assignment.course.id}`, '_blank')
}

// Computed properties
const completedModules = computed(() =>
    props.moduleProgress.filter(module => module.progress.is_completed).length
)

const totalModules = computed(() => props.moduleProgress.length)

const averageEngagement = computed(() => {
    if (props.learningSessions.length === 0) return 0
    const total = props.learningSessions.reduce((sum, session) => sum + session.attention_score, 0)
    return Math.round(total / props.learningSessions.length)
})

const suspiciousSessionsCount = computed(() =>
    props.learningSessions.filter(session => session.is_suspicious).length
)

// Utility methods
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

const getEngagementColor = (level: string) => {
    switch (level.toLowerCase()) {
        case 'high': return 'text-green-600'
        case 'medium': return 'text-yellow-600'
        case 'low': return 'text-orange-600'
        case 'very low': return 'text-red-600'
        default: return 'text-gray-600'
    }
}

const getRiskColor = (risk: string) => {
    switch (risk.toLowerCase()) {
        case 'none': return 'text-green-600'
        case 'low': return 'text-yellow-600'
        case 'medium': return 'text-orange-600'
        case 'high': return 'text-red-600'
        case 'very high': return 'text-red-700'
        default: return 'text-gray-600'
    }
}

const formatDate = (dateString: string) => {
    if (!dateString) return 'Not available'
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getProgressColor = (percentage: number) => {
    if (percentage >= 80) return 'bg-green-500'
    if (percentage >= 50) return 'bg-blue-500'
    if (percentage >= 25) return 'bg-yellow-500'
    return 'bg-gray-300'
}

const getContentIcon = (contentType: string) => {
    switch (contentType) {
        case 'video': return Play
        case 'pdf': return FileText
        default: return FileText
    }
}

const formatTimeSpent = (minutes: number) => {
    if (!minutes || minutes <= 0) return '0 min'
    if (minutes < 60) return `${minutes}m`
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`
}

// ✅ FIXED: Duration formatter to handle negative values
const formatDuration = (duration: string) => {
    if (!duration || duration === 'null' || duration === 'undefined') {
        return '0 min'
    }

    // If duration starts with "-", make it positive
    if (duration.startsWith('-')) {
        return duration.substring(1).trim() || '0 min'
    }

    return duration
}
</script>

<template>
    <Head title="Assignment Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button asChild variant="ghost">
                        <Link href="/admin/course-assignments">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Assignments
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Assignment Details</h1>
                        <p class="text-muted-foreground">{{ assignment.user.name }} • {{ assignment.course.name }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" @click="sendMessage">
                        <Mail class="mr-2 h-4 w-4" />
                        Send Message
                    </Button>
                    <Button variant="outline" size="sm" @click="viewAsStudent">
                        <Eye class="mr-2 h-4 w-4" />
                        View as Student
                    </Button>
                </div>
            </div>

            <!-- Assignment Overview -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Student Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <User class="h-5 w-5 text-primary" />
                            Student Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Name</span>
                            <span class="font-medium">{{ assignment.user.name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Email</span>
                            <span class="font-medium">{{ assignment.user.email }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Status</span>
                            <Badge :class="getStatusColor(assignment.status)">
                                {{ getStatusText(assignment.status) }}
                            </Badge>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Assigned by</span>
                            <span class="font-medium">{{ assignment.assigned_by.name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Assigned Date</span>
                            <span class="font-medium">{{ formatDate(assignment.assigned_at) }}</span>
                        </div>
                        <div v-if="assignment.started_at" class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Started Date</span>
                            <span class="font-medium">{{ formatDate(assignment.started_at) }}</span>
                        </div>
                        <div v-if="assignment.completed_at" class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Completed Date</span>
                            <span class="font-medium">{{ formatDate(assignment.completed_at) }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Course Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BookOpen class="h-5 w-5 text-primary" />
                            Course Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <div class="font-medium">{{ assignment.course.name }}</div>
                            <div class="text-sm text-muted-foreground mt-1">{{ assignment.course.description }}</div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Difficulty</span>
                            <Badge :class="getDifficultyColor(assignment.course.difficulty_level)">
                                {{ assignment.course.difficulty_level }}
                            </Badge>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Duration</span>
                            <span class="font-medium">{{ assignment.course.estimated_duration }} minutes</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Modules</span>
                            <span class="font-medium">{{ assignment.course.modules_count }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Current Module</span>
                            <span class="font-medium">
                                {{ assignment.current_module
                                ? `Module ${assignment.current_module.order_number}: ${assignment.current_module.name}`
                                : 'Not started' }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Progress Overview -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Overall Progress</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ Math.round(assignment.progress_percentage) }}%</div>
                        <Progress :value="assignment.progress_percentage" class="mt-2" />
                        <p class="text-xs text-muted-foreground mt-2">
                            {{ completedModules }} of {{ totalModules }} modules completed
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Time Spent</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ assignment.time_spent || '0 min' }}</div>
                        <p class="text-xs text-muted-foreground">
                            Est. {{ assignment.course.estimated_duration }} min total
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Engagement</CardTitle>
                        <Activity class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ averageEngagement }}%</div>
                        <p class="text-xs text-muted-foreground">Average attention score</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Sessions</CardTitle>
                        <BarChart3 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ learningSessions.length }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ suspiciousSessionsCount }} suspicious
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Detailed Tabs -->
            <Tabs default-value="modules" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="modules">Module Progress</TabsTrigger>
                    <TabsTrigger value="sessions">Learning Sessions</TabsTrigger>
                    <TabsTrigger value="content">Content Progress</TabsTrigger>
                </TabsList>

                <!-- Module Progress Tab -->
                <TabsContent value="modules" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Module Progress</CardTitle>
                            <CardDescription>
                                Detailed progress through each course module
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="moduleProgress.length === 0" class="text-center py-8 text-muted-foreground">
                                <BookOpen class="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <p>No module progress data available</p>
                            </div>
                            <div v-else class="space-y-4">
                                <div
                                    v-for="module in moduleProgress"
                                    :key="module.id"
                                    class="border rounded-lg p-4"
                                    :class="module.progress.is_completed
                                        ? 'bg-green-50 border-green-200 dark:bg-green-950'
                                        : 'bg-gray-50 dark:bg-gray-900'"
                                >
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold"
                                                 :class="module.progress.is_completed
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                    : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
                                            >
                                                {{ module.order_number }}
                                            </div>
                                            <div>
                                                <h4 class="font-medium">{{ module.name }}</h4>
                                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                                    <span>{{ module.progress.completed_content }}/{{ module.progress.total_content }} content items</span>
                                                    <span v-if="!module.progress.is_unlocked" class="text-orange-600">🔒 Locked</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-semibold">{{ Math.round(module.progress.completion_percentage) }}%</div>
                                            <Badge v-if="module.progress.is_completed" class="bg-green-100 text-green-800">
                                                <CheckCircle class="w-3 h-3 mr-1" />
                                                Completed
                                            </Badge>
                                        </div>
                                    </div>
                                    <Progress :value="module.progress.completion_percentage" class="h-2" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Learning Sessions Tab -->
                <TabsContent value="sessions" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Learning Sessions</CardTitle>
                            <CardDescription>
                                Detailed activity log of student learning sessions
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <!-- Suspicious Activity Alert -->
                            <Alert v-if="suspiciousSessionsCount > 0" class="mb-4">
                                <AlertTriangle class="h-4 w-4" />
                                <AlertDescription>
                                    <strong>{{ suspiciousSessionsCount }}</strong> suspicious learning sessions detected.
                                    Review for potential academic dishonesty.
                                </AlertDescription>
                            </Alert>

                            <div v-if="learningSessions.length === 0" class="text-center py-8 text-muted-foreground">
                                <Activity class="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <p>No learning sessions recorded yet</p>
                            </div>

                            <Table v-else>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Content</TableHead>
                                        <TableHead>Session Time</TableHead>
                                        <TableHead>Duration</TableHead>
                                        <TableHead>Engagement</TableHead>
                                        <TableHead>Risk Level</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow
                                        v-for="session in learningSessions"
                                        :key="session.id"
                                        :class="session.is_suspicious ? 'bg-red-50 dark:bg-red-950' : ''"
                                    >
                                        <TableCell>
                                            <div class="font-medium">{{ session.content_title }}</div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="text-sm">{{ formatDate(session.session_start) }}</div>
                                            <div v-if="session.session_end" class="text-xs text-muted-foreground">
                                                Ended {{ session.session_end }}
                                            </div>
                                        </TableCell>
                                        <!-- ✅ FIXED: Duration column -->
                                        <TableCell>{{ formatDuration(session.duration) }}</TableCell>
                                        <TableCell>
                                            <div :class="getEngagementColor(session.engagement_level)" class="font-medium">
                                                {{ session.engagement_level }}
                                            </div>
                                            <div class="text-xs text-muted-foreground">{{ session.attention_score }}% attention</div>
                                        </TableCell>
                                        <TableCell>
                                            <div :class="getRiskColor(session.cheating_risk)" class="font-medium">
                                                {{ session.cheating_risk }}
                                            </div>
                                            <div v-if="session.is_suspicious" class="text-xs text-red-600">
                                                ⚠️ Flagged
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Content Progress Tab -->
                <TabsContent value="content" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Content Progress</CardTitle>
                            <CardDescription>
                                Individual content item completion status
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="userProgress.length === 0" class="text-center py-8 text-muted-foreground">
                                <FileText class="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <p>No content progress data available</p>
                            </div>
                            <Table v-else>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Content</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Progress</TableHead>
                                        <TableHead>Time Spent</TableHead>
                                        <TableHead>Last Accessed</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(progress, index) in userProgress" :key="progress.id || index">
                                        <TableCell>
                                            <div class="font-medium">{{ progress.content.title }}</div>
                                            <div class="text-xs text-muted-foreground">{{ progress.content.module_name }}</div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <component :is="getContentIcon(progress.content.content_type)" class="w-4 h-4 text-blue-600" />
                                                <span class="capitalize">{{ progress.content.content_type }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between text-sm">
                                                    <span>{{ Math.round(progress.completion_percentage) }}%</span>
                                                    <Badge v-if="progress.is_completed" class="bg-green-100 text-green-800">
                                                        <CheckCircle class="w-3 h-3" />
                                                    </Badge>
                                                </div>
                                                <Progress :value="progress.completion_percentage" class="h-2" />
                                            </div>
                                        </TableCell>
                                        <TableCell>{{ formatTimeSpent(progress.watch_time) }}</TableCell>
                                        <TableCell>
                                            <div v-if="progress.last_accessed" class="text-sm">
                                                {{ formatDate(progress.last_accessed) }}
                                            </div>
                                            <div v-else class="text-sm text-muted-foreground">Never</div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge v-if="progress.is_completed" class="bg-green-100 text-green-800">
                                                Completed
                                            </Badge>
                                            <Badge v-else-if="progress.completion_percentage > 0" class="bg-blue-100 text-blue-800">
                                                In Progress
                                            </Badge>
                                            <Badge v-else class="bg-gray-100 text-gray-800">
                                                Not Started
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom progress bar colors */
.progress-green {
    @apply bg-green-500;
}

.progress-blue {
    @apply bg-blue-500;
}

.progress-yellow {
    @apply bg-yellow-500;
}

.progress-gray {
    @apply bg-gray-300;
}

/* Animation for progress bars */
:deep(.progress-bar) {
    transition: width 0.3s ease-in-out;
}

/* Table hover effects */
:deep(.table-row:hover) {
    @apply bg-muted/50;
}

/* Badge consistency */
:deep(.badge) {
    @apply text-xs font-semibold;
}

/* Alert styling */
:deep(.alert-warning) {
    @apply border-yellow-200 bg-yellow-50;
}
</style>
