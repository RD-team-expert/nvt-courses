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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

// Icons
import {
    ArrowLeft,
    Users,
    BookOpen,
    TrendingUp,
    Clock,
    CheckCircle,
    AlertTriangle,
    Activity,
    Target,
    Eye,
    Download,
    BarChart3,
    Zap,
    Award,
    PlayCircle,
    FileText,
    UserCheck,
    UserX
} from 'lucide-vue-next'

interface Course {
    id: number
    name: string
    description: string
    difficulty_level: string
    modules_count: number
}

interface Analytics {
    total_enrollments: number
    active_learners: number
    completed_learners: number
    completion_rate: number
    dropout_rate: number
    average_session_duration: number
    total_learning_hours: number
    average_video_completion_rate: number
    cheating_incidents_count: number
    engagement_score: number
    last_calculated: string | null
}

interface ModuleAnalytic {
    id: number
    name: string
    order_number: number
    completion_count: number
    average_progress: number
    average_time_spent: number
    dropout_point: number
}

interface LearnerProgress {
    user_name: string
    status: string
    progress_percentage: number
    assigned_date: string
    last_activity: string | null
}

interface EngagementMetric {
    average_attention_score: number
    high_engagement_sessions: number
    low_engagement_sessions: number
    total_interactions: number
}

interface ContentPerformance {
    title: string
    type: string
    view_count: number
    average_completion: number
    skip_rate: number
    engagement_score: number
}

// ✅ FIXED: Make all props optional to handle course selection page
const props = defineProps<{
    course?: Course
    analytics?: Analytics
    moduleAnalytics?: ModuleAnalytic[]
    learnerProgress?: LearnerProgress[]
    engagementMetrics?: EngagementMetric
    contentPerformance?: ContentPerformance[]
    courses?: Course[] // For course selection
}>()

// ✅ FIXED: Dynamic breadcrumbs based on whether course is selected
const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Online Courses', href: '/admin/courses-online' },
    { title: 'Analytics', href: '/admin/analytics' },
    { title: props.course?.name || 'Course Analytics', href: '#' }
])

// ✅ FIXED: Only compute these when course and analytics exist
const completionRateColor = computed(() => {
    if (!props.analytics) return 'text-gray-600'
    const rate = props.analytics.completion_rate
    if (rate >= 80) return 'text-green-600'
    if (rate >= 60) return 'text-blue-600'
    if (rate >= 40) return 'text-yellow-600'
    return 'text-red-600'
})

const engagementLevel = computed(() => {
    if (!props.analytics) return { text: 'N/A', color: 'text-gray-600 bg-gray-100' }
    const score = props.analytics.engagement_score
    if (score >= 80) return { text: 'Excellent', color: 'text-green-600 bg-green-100' }
    if (score >= 60) return { text: 'Good', color: 'text-blue-600 bg-blue-100' }
    if (score >= 40) return { text: 'Average', color: 'text-yellow-600 bg-yellow-100' }
    return { text: 'Poor', color: 'text-red-600 bg-red-100' }
})

const riskLevel = computed(() => {
    if (!props.analytics) return { text: 'N/A', color: 'text-gray-600 bg-gray-100' }
    const incidents = props.analytics.cheating_incidents_count
    if (incidents === 0) return { text: 'No Risk', color: 'text-green-600 bg-green-100' }
    if (incidents <= 2) return { text: 'Low Risk', color: 'text-yellow-600 bg-yellow-100' }
    if (incidents <= 5) return { text: 'Medium Risk', color: 'text-orange-600 bg-orange-100' }
    return { text: 'High Risk', color: 'text-red-600 bg-red-100' }
})

const topPerformingModules = computed(() => {
    if (!props.moduleAnalytics) return []
    return [...props.moduleAnalytics]
        .sort((a, b) => b.average_progress - a.average_progress)
        .slice(0, 5)
})

const strugglingModules = computed(() => {
    if (!props.moduleAnalytics) return []
    return [...props.moduleAnalytics]
        .sort((a, b) => b.dropout_point - a.dropout_point)
        .slice(0, 5)
})

const activeLearners = computed(() => {
    if (!props.learnerProgress) return []
    return props.learnerProgress.filter(learner => learner.status === 'in_progress')
})

const completedLearners = computed(() => {
    if (!props.learnerProgress) return []
    return props.learnerProgress.filter(learner => learner.status === 'completed')
})

const strugglingLearners = computed(() => {
    if (!props.learnerProgress) return []
    return props.learnerProgress
        .filter(learner => learner.status === 'in_progress' && learner.progress_percentage < 25)
        .sort((a, b) => a.progress_percentage - b.progress_percentage)
})

// Methods
const getDifficultyColor = (level: string): string => {
    switch (level) {
        case 'beginner': return 'bg-green-100 text-green-800'
        case 'intermediate': return 'bg-yellow-100 text-yellow-800'
        case 'advanced': return 'bg-red-100 text-red-800'
        default: return 'bg-gray-100 text-gray-800'
    }
}

const getStatusColor = (status: string): string => {
    switch (status) {
        case 'assigned': return 'bg-gray-100 text-gray-800'
        case 'in_progress': return 'bg-blue-100 text-blue-800'
        case 'completed': return 'bg-green-100 text-green-800'
        default: return 'bg-gray-100 text-gray-800'
    }
}

const getStatusText = (status: string): string => {
    switch (status) {
        case 'assigned': return 'Not Started'
        case 'in_progress': return 'In Progress'
        case 'completed': return 'Completed'
        default: return status
    }
}

const formatHours = (hours: number): string => {
    if (hours < 1) return `${Math.round(hours * 60)}m`
    return `${hours.toFixed(1)}h`
}

const formatMinutes = (minutes: number): string => {
    if (minutes < 60) return `${Math.round(minutes)}m`
    const hours = Math.floor(minutes / 60)
    const mins = Math.round(minutes % 60)
    return `${hours}h ${mins}m`
}

const getContentIcon = (type: string) => {
    return type === 'video' ? PlayCircle : FileText
}

const getProgressColor = (percentage: number): string => {
    if (percentage >= 80) return 'bg-green-500'
    if (percentage >= 60) return 'bg-blue-500'
    if (percentage >= 40) return 'bg-yellow-500'
    return 'bg-red-500'
}

const formatNumber = (num: number): string => {
    return num.toLocaleString()
}

// ✅ FIXED: Method to navigate to specific course
const viewCourseAnalytics = (courseId: number) => {
    window.location.href = `/admin/analytics/course-analytics/${courseId}`
}
</script>

<template>
    <Head :title="course?.name ? `${course.name} - Analytics` : 'Course Analytics'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- ✅ FIXED: Course Selection Page -->
        <div v-if="!course && courses" class="space-y-6">
            <div class="text-center">
                <h1 class="text-3xl font-bold tracking-tight mb-2">Course Analytics</h1>
                <p class="text-muted-foreground">Select a course to view detailed analytics</p>
            </div>

            <Card class="max-w-md mx-auto">
                <CardHeader>
                    <CardTitle>Select Course</CardTitle>
                    <CardDescription>Choose a course to analyze</CardDescription>
                </CardHeader>
                <CardContent>
                    <Select @update:model-value="viewCourseAnalytics">
                        <SelectTrigger>
                            <SelectValue placeholder="Choose a course..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="courseOption in courses"
                                :key="courseOption.id"
                                :value="courseOption.id.toString()"
                            >
                                {{ courseOption.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </CardContent>
            </Card>

            <!-- Course Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 max-w-6xl mx-auto">
                <Card
                    v-for="courseOption in courses"
                    :key="courseOption.id"
                    class="cursor-pointer hover:shadow-md transition-shadow"
                    @click="viewCourseAnalytics(courseOption.id)"
                >
                    <CardHeader>
                        <CardTitle class="text-lg">{{ courseOption.name }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-muted-foreground">View Analytics</span>
                            <Eye class="h-4 w-4" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- ✅ FIXED: Course Analytics Dashboard (only show when course is selected) -->
        <div v-else-if="course && analytics" class="space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-2">
                        <Button asChild variant="ghost" size="sm">
                            <Link href="/admin/analytics/course-analytics">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back to Course Selection
                            </Link>
                        </Button>
                        <Badge :class="getDifficultyColor(course.difficulty_level)">
                            {{ course.difficulty_level }}
                        </Badge>
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ course.name }}</h1>
                    <p class="text-muted-foreground mt-1">{{ course.description }}</p>
                    <div class="flex items-center gap-4 mt-3 text-sm text-muted-foreground">
                        <div class="flex items-center gap-1">
                            <BookOpen class="h-4 w-4" />
                            {{ course.modules_count }} modules
                        </div>
                        <div class="flex items-center gap-1">
                            <Users class="h-4 w-4" />
                            {{ analytics.total_enrollments }} enrolled
                        </div>
                        <div v-if="analytics.last_calculated" class="flex items-center gap-1">
                            <Clock class="h-4 w-4" />
                            Updated {{ analytics.last_calculated }}
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm">
                        <Download class="mr-2 h-4 w-4" />
                        Export Report
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link :href="route('admin.courses-online.show', course.id)">
                            <Eye class="mr-2 h-4 w-4" />
                            View Course
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Completion Rate</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="completionRateColor">
                            {{ Math.round(analytics.completion_rate) }}%
                        </div>
                        <Progress :value="analytics.completion_rate" class="mt-2" />
                        <p class="text-xs text-muted-foreground mt-2">
                            {{ analytics.completed_learners }}/{{ analytics.total_enrollments }} completed
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Engagement Score</CardTitle>
                        <Activity class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ Math.round(analytics.engagement_score) }}</div>
                        <Badge :class="engagementLevel.color" class="mt-2">
                            {{ engagementLevel.text }}
                        </Badge>
                        <p class="text-xs text-muted-foreground mt-2">
                            Average user engagement
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Learning Time</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatHours(analytics.total_learning_hours) }}</div>
                        <p class="text-xs text-muted-foreground">
                            Avg {{ formatMinutes(analytics.average_session_duration) }} per session
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Security Status</CardTitle>
                        <AlertTriangle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ analytics.cheating_incidents_count }}</div>
                        <Badge :class="riskLevel.color" class="mt-2">
                            {{ riskLevel.text }}
                        </Badge>
                        <p class="text-xs text-muted-foreground mt-2">
                            Suspicious incidents
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Security Alert -->
            <Alert v-if="analytics.cheating_incidents_count > 0" class="border-red-200 bg-red-50 dark:bg-red-950">
                <AlertTriangle class="h-4 w-4" />
                <AlertDescription>
                    <strong>{{ analytics.cheating_incidents_count }}</strong> suspicious activities detected in this course.
                    <Link :href="route('admin.analytics.cheating-detection', { course_id: course.id })" class="ml-2 font-medium underline">
                        Investigate now →
                    </Link>
                </AlertDescription>
            </Alert>

            <!-- Analytics Tabs -->
            <Tabs default-value="modules" class="space-y-4">
                <TabsList>
                    <TabsTrigger value="modules">Module Performance</TabsTrigger>
                    <TabsTrigger value="learners">Learner Progress</TabsTrigger>
                    <TabsTrigger value="content">Content Analytics</TabsTrigger>
                    <TabsTrigger value="engagement">Engagement Details</TabsTrigger>
                </TabsList>

                <!-- Module Performance Tab -->
                <TabsContent value="modules" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <!-- Top Performing Modules -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <TrendingUp class="h-5 w-5 text-green-600" />
                                    Top Performing Modules
                                </CardTitle>
                                <CardDescription>Modules with highest completion rates</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="topPerformingModules.length === 0" class="text-center py-6 text-muted-foreground">
                                    <BookOpen class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                    <p>No module data available</p>
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="module in topPerformingModules"
                                        :key="module.id"
                                        class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg"
                                    >
                                        <div>
                                            <div class="font-medium">Module {{ module.order_number }}: {{ module.name }}</div>
                                            <div class="text-sm text-muted-foreground">
                                                {{ module.completion_count }} completions
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-green-600">
                                                {{ Math.round(module.average_progress) }}%
                                            </div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ formatMinutes(module.average_time_spent) }} avg
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Struggling Modules -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <UserX class="h-5 w-5 text-red-600" />
                                    Modules Needing Attention
                                </CardTitle>
                                <CardDescription>Modules with high dropout rates</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="strugglingModules.every(m => m.dropout_point === 0)" class="text-center py-6 text-muted-foreground">
                                    <CheckCircle class="h-12 w-12 mx-auto mb-2 text-green-600" />
                                    <p>All modules performing well</p>
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="module in strugglingModules.filter(m => m.dropout_point > 0)"
                                        :key="module.id"
                                        class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg"
                                    >
                                        <div>
                                            <div class="font-medium">Module {{ module.order_number }}: {{ module.name }}</div>
                                            <div class="text-sm text-muted-foreground">
                                                {{ module.completion_count }} completed
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-red-600">
                                                {{ module.dropout_point }} dropouts
                                            </div>
                                            <div class="text-xs text-red-600">
                                                {{ Math.round(module.average_progress) }}% avg progress
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- All Modules Table -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Complete Module Analysis</CardTitle>
                            <CardDescription>Detailed performance metrics for all modules</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!moduleAnalytics || moduleAnalytics.length === 0" class="text-center py-8 text-muted-foreground">
                                <BookOpen class="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <p>No module analytics available</p>
                            </div>
                            <Table v-else>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Module</TableHead>
                                        <TableHead>Completions</TableHead>
                                        <TableHead>Avg Progress</TableHead>
                                        <TableHead>Avg Time</TableHead>
                                        <TableHead>Dropouts</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="module in moduleAnalytics" :key="module.id">
                                        <TableCell>
                                            <div class="font-medium">{{ module.order_number }}. {{ module.name }}</div>
                                        </TableCell>
                                        <TableCell>{{ module.completion_count }}</TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <Progress :value="module.average_progress" class="w-16" />
                                                <span class="text-sm">{{ Math.round(module.average_progress) }}%</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>{{ formatMinutes(module.average_time_spent) }}</TableCell>
                                        <TableCell>
                                            <span :class="module.dropout_point > 0 ? 'text-red-600' : 'text-green-600'">
                                                {{ module.dropout_point }}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                :class="module.average_progress >= 80 ? 'bg-green-100 text-green-800' :
                                                       module.average_progress >= 60 ? 'bg-blue-100 text-blue-800' :
                                                       module.dropout_point > 0 ? 'bg-red-100 text-red-800' :
                                                       'bg-yellow-100 text-yellow-800'"
                                            >
                                                {{
                                                    module.average_progress >= 80 ? 'Excellent' :
                                                        module.average_progress >= 60 ? 'Good' :
                                                            module.dropout_point > 0 ? 'Needs Review' :
                                                                'Average'
                                                }}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Learner Progress Tab -->
                <TabsContent value="learners" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <!-- Active Learners -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Users class="h-5 w-5 text-blue-600" />
                                    Active Learners
                                </CardTitle>
                                <CardDescription>{{ activeLearners.length }} currently learning</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="activeLearners.length === 0" class="text-center py-6 text-muted-foreground">
                                    <Users class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                    <p class="text-sm">No active learners</p>
                                </div>
                                <div v-else class="space-y-2 max-h-64 overflow-y-auto">
                                    <div
                                        v-for="learner in activeLearners"
                                        :key="learner.user_name"
                                        class="flex items-center justify-between p-2 bg-blue-50 border border-blue-200 rounded"
                                    >
                                        <div>
                                            <div class="font-medium text-sm">{{ learner.user_name }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                Started {{ learner.assigned_date }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-bold">{{ Math.round(learner.progress_percentage) }}%</div>
                                            <Progress :value="learner.progress_percentage" class="w-16 h-1" />
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Completed Learners -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <UserCheck class="h-5 w-5 text-green-600" />
                                    Completed
                                </CardTitle>
                                <CardDescription>{{ completedLearners.length }} successfully finished</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="completedLearners.length === 0" class="text-center py-6 text-muted-foreground">
                                    <UserCheck class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                    <p class="text-sm">No completed learners</p>
                                </div>
                                <div v-else class="space-y-2 max-h-64 overflow-y-auto">
                                    <div
                                        v-for="learner in completedLearners"
                                        :key="learner.user_name"
                                        class="flex items-center justify-between p-2 bg-green-50 border border-green-200 rounded"
                                    >
                                        <div>
                                            <div class="font-medium text-sm">{{ learner.user_name }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                Started {{ learner.assigned_date }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <CheckCircle class="h-4 w-4 text-green-600" />
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Struggling Learners -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <UserX class="h-5 w-5 text-red-600" />
                                    Need Support
                                </CardTitle>
                                <CardDescription>{{ strugglingLearners.length }} learners < 25% progress</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="strugglingLearners.length === 0" class="text-center py-6 text-muted-foreground">
                                    <CheckCircle class="h-8 w-8 mx-auto mb-2 text-green-600" />
                                    <p class="text-sm">All learners progressing well</p>
                                </div>
                                <div v-else class="space-y-2 max-h-64 overflow-y-auto">
                                    <div
                                        v-for="learner in strugglingLearners"
                                        :key="learner.user_name"
                                        class="flex items-center justify-between p-2 bg-red-50 border border-red-200 rounded"
                                    >
                                        <div>
                                            <div class="font-medium text-sm">{{ learner.user_name }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                Last active: {{ learner.last_activity || 'Never' }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-bold text-red-600">{{ Math.round(learner.progress_percentage) }}%</div>
                                            <Progress :value="learner.progress_percentage" class="w-16 h-1" />
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- All Learners Table -->
                    <Card>
                        <CardHeader>
                            <CardTitle>All Learners Progress</CardTitle>
                            <CardDescription>Complete overview of learner performance</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!learnerProgress || learnerProgress.length === 0" class="text-center py-8 text-muted-foreground">
                                <Users class="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <p>No learner progress data available</p>
                            </div>
                            <Table v-else>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Learner</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Progress</TableHead>
                                        <TableHead>Assigned Date</TableHead>
                                        <TableHead>Last Activity</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="learner in learnerProgress" :key="learner.user_name">
                                        <TableCell class="font-medium">{{ learner.user_name }}</TableCell>
                                        <TableCell>
                                            <Badge :class="getStatusColor(learner.status)">
                                                {{ getStatusText(learner.status) }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <Progress :value="learner.progress_percentage" class="w-20" />
                                                <span class="text-sm">{{ Math.round(learner.progress_percentage) }}%</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>{{ learner.assigned_date }}</TableCell>
                                        <TableCell>
                                            <span :class="!learner.last_activity ? 'text-red-600' : 'text-muted-foreground'">
                                                {{ learner.last_activity || 'Never accessed' }}
                                            </span>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Content Analytics Tab -->
                <TabsContent value="content" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <BarChart3 class="h-5 w-5 text-purple-600" />
                                Content Performance
                            </CardTitle>
                            <CardDescription>How individual content items are performing</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!contentPerformance || contentPerformance.length === 0" class="text-center py-8 text-muted-foreground">
                                <FileText class="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <p>No content performance data available</p>
                            </div>
                            <Table v-else>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Content</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Views</TableHead>
                                        <TableHead>Completion Rate</TableHead>
                                        <TableHead>Skip Rate</TableHead>
                                        <TableHead>Engagement</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="content in contentPerformance" :key="content.title">
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <component :is="getContentIcon(content.type)" class="h-4 w-4" />
                                                <span class="font-medium">{{ content.title }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline" class="capitalize">{{ content.type }}</Badge>
                                        </TableCell>
                                        <TableCell>{{ content.view_count }}</TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <Progress :value="content.average_completion" class="w-16" />
                                                <span class="text-sm">{{ Math.round(content.average_completion) }}%</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <span :class="content.skip_rate > 2 ? 'text-red-600' : 'text-green-600'">
                                                {{ content.skip_rate.toFixed(1) }} skips
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <div class="w-2 h-2 rounded-full" :class="getProgressColor(content.engagement_score)"></div>
                                                <span class="text-sm">{{ Math.round(content.engagement_score) }}%</span>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Engagement Details Tab -->
                <TabsContent value="engagement" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <!-- Engagement Metrics -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Zap class="h-5 w-5 text-yellow-600" />
                                    Engagement Metrics
                                </CardTitle>
                                <CardDescription>User interaction and attention data</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div v-if="!engagementMetrics" class="text-center py-6 text-muted-foreground">
                                    <Activity class="h-8 w-8 mx-auto mb-2 opacity-50" />
                                    <p>No engagement data available</p>
                                </div>
                                <div v-else>
                                    <div class="flex items-center justify-between">
                                        <span>Average Attention Score</span>
                                        <div class="text-right">
                                            <div class="font-bold">{{ Math.round(engagementMetrics.average_attention_score) }}%</div>
                                            <Progress :value="engagementMetrics.average_attention_score" class="w-24 mt-1" />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>High Engagement Sessions</span>
                                        <div class="font-bold text-green-600">{{ engagementMetrics.high_engagement_sessions }}</div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Low Engagement Sessions</span>
                                        <div class="font-bold text-red-600">{{ engagementMetrics.low_engagement_sessions }}</div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Total Interactions</span>
                                        <div class="font-bold">{{ formatNumber(engagementMetrics.total_interactions) }}</div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Video Completion -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <PlayCircle class="h-5 w-5 text-blue-600" />
                                    Video Performance
                                </CardTitle>
                                <CardDescription>Video-specific analytics</CardDescription>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="text-center">
                                    <div class="text-3xl font-bold">{{ Math.round(analytics.average_video_completion_rate) }}%</div>
                                    <div class="text-muted-foreground">Average Video Completion</div>
                                    <Progress :value="analytics.average_video_completion_rate" class="mt-2" />
                                </div>
                                <div class="text-center pt-4">
                                    <div class="text-lg">{{ formatMinutes(analytics.average_session_duration) }}</div>
                                    <div class="text-muted-foreground">Average Session Duration</div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>
            </Tabs>
        </div>

        <!-- ✅ FIXED: Loading or Error State -->
        <div v-else class="flex items-center justify-center py-12">
            <div class="text-center">
                <BookOpen class="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
                <p class="text-muted-foreground">Loading course analytics...</p>
            </div>
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

/* Card hover effects */
.cursor-pointer:hover {
    transform: translateY(-2px);
    transition: all 0.2s ease-in-out;
}
</style>
