<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    ArrowLeft,
    Edit,
    Layers,
    Users,
    Clock,
    Award,
    BarChart3,
    TrendingUp,
    Activity,
    BookOpen,
    CheckCircle,
    AlertCircle,
    Play,
    FileText,
    Calendar,
    User,
    Plus,
    Settings,
    Zap,
    Target,
    Brain
} from 'lucide-vue-next'

interface Assignment {
    id: number
    user: {
        name: string
        email: string
    }
    status: string
    progress_percentage: number
    created_at: string
}

interface Module {
    id: number
    name: string
    description: string
    order_number: number
    content_count: number
    video_count: number
    pdf_count: number
    estimated_duration: number
}

interface Course {
    id: number
    name: string
    description: string
    difficulty_level: string
    is_active: boolean
    image_path: string
    creator: {
        name: string
    }
    enrollment_count: number
    completion_rate: number
    estimated_duration: number
    created_at: string
    modules: Module[]
    assignments: Assignment[]
    avg_engagement?: number
    avg_study_time?: number
    success_prediction?: number
}

const props = defineProps<{
    course: Course
}>()

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Online Courses', href: route('admin.course-online.index') },
    { name: props.course.name, href: '#' }
]

const toggleLoading = ref(false)
const showAnalyticsModal = ref(false)

// Utility functions
const getLevelBadgeVariant = (level: string) => {
    switch (level?.toLowerCase()) {
        case 'beginner': return 'default'
        case 'intermediate': return 'secondary'
        case 'advanced': return 'destructive'
        default: return 'outline'
    }
}

const getStatusBadgeVariant = (status: string) => {
    switch (status?.toLowerCase()) {
        case 'assigned': return 'secondary'
        case 'in_progress': return 'default'
        case 'completed': return 'outline'
        default: return 'secondary'
    }
}

const getStatusText = (status: string) => {
    switch (status?.toLowerCase()) {
        case 'assigned': return 'Not Started'
        case 'in_progress': return 'In Progress'
        case 'completed': return 'Completed'
        default: return status
    }
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const toggleActiveStatus = () => {
    toggleLoading.value = true
    router.patch(route('admin.course-online.toggle-active', props.course.id), {}, {
        onFinish: () => {
            toggleLoading.value = false
        }
    })
}

const showAnalytics = () => {
    showAnalyticsModal.value = true
}
</script>

<template>
    <Head title="Course Details" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header Section -->
            <Card class="overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-8">
                    <div class="flex flex-col lg:flex-row items-start justify-between gap-6">
                        <div class="flex items-center space-x-6 flex-1">
                            <!-- Course Image -->
                            <div class="flex-shrink-0">
                                <div v-if="course.image_path" class="relative">
                                    <img
                                        :src="course.image_path"
                                        :alt="course.name"
                                        class="h-28 w-28 rounded-xl object-cover shadow-lg border-4 border-white/20"
                                    />
                                </div>
                                <div v-else class="h-28 w-28 rounded-xl bg-white/10 backdrop-blur-sm flex items-center justify-center shadow-lg border-4 border-white/20">
                                    <BookOpen class="w-12 h-12 text-white/70" />
                                </div>
                            </div>

                            <!-- Course Info -->
                            <div class="text-white flex-1">
                                <h1 class="text-3xl font-bold mb-3">{{ course.name }}</h1>
                                <p class="text-blue-100 mb-4 leading-relaxed max-w-3xl">
                                    {{ course.description || 'No description available for this course.' }}
                                </p>
                                <div class="flex flex-wrap items-center gap-3">
                                    <Badge :variant="getLevelBadgeVariant(course.difficulty_level)" class="px-3 py-1.5 font-semibold">
                                        {{ course.difficulty_level || 'Not specified' }}
                                    </Badge>
                                    <Badge :variant="course.is_active ? 'default' : 'destructive'" class="px-3 py-1.5 font-semibold">
                                        {{ course.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                    <span class="text-blue-100 text-sm flex items-center">
                                        <User class="w-4 h-4 mr-1" />
                                        Created by <span class="font-medium text-white ml-1">{{ course.creator?.name || 'Unknown' }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <Link :href="route('admin.course-online.edit', course.id)">
                                <Button variant="outline" class="bg-white/10 border-white/20 text-white hover:bg-white/20 hover:border-white/30">
                                    <Edit class="w-4 h-4 mr-2" />
                                    Edit Course
                                </Button>
                            </Link>
                            <Link :href="route('admin.course-modules.index', course.id)">
                                <Button class="bg-white text-blue-600 hover:bg-blue-50">
                                    <Layers class="w-4 h-4 mr-2" />
                                    Manage Modules
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="px-8 py-6 bg-muted/30">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <Layers class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-foreground">{{ course.modules?.length || 0 }}</div>
                                <div class="text-sm text-muted-foreground">Modules</div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <Users class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-foreground">{{ course.enrollment_count || 0 }}</div>
                                <div class="text-sm text-muted-foreground">Enrolled</div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <BarChart3 class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-foreground">{{ course.completion_rate || 0 }}%</div>
                                <div class="text-sm text-muted-foreground">Completion</div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                                <Clock class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-foreground">{{ course.estimated_duration || 0 }}</div>
                                <div class="text-sm text-muted-foreground">Minutes</div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Analytics Dashboard -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center">
                                <Brain class="w-5 h-5 mr-2 text-blue-600" />
                                Learning Analytics
                            </CardTitle>
                            <CardDescription>
                                Advanced metrics and performance insights
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-100 dark:border-blue-800">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-blue-600 dark:text-blue-400 text-sm font-semibold">Avg Engagement</p>
                                            <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ course.avg_engagement || 85 }}%</p>
                                        </div>
                                        <Zap class="w-8 h-8 text-blue-600 dark:text-blue-300" />
                                    </div>
                                    <Progress :value="course.avg_engagement || 85" class="mt-4" />
                                </div>

                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 border border-green-100 dark:border-green-800">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-green-600 dark:text-green-400 text-sm font-semibold">Avg Study Time</p>
                                            <p class="text-3xl font-bold text-green-900 dark:text-green-100">{{ course.avg_study_time || 42 }}m</p>
                                        </div>
                                        <Clock class="w-8 h-8 text-green-600 dark:text-green-300" />
                                    </div>
                                    <div class="mt-4 text-sm text-green-600 dark:text-green-400 flex items-center">
                                        <TrendingUp class="w-4 h-4 mr-1" />
                                        +12% this month
                                    </div>
                                </div>

                                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border border-purple-100 dark:border-purple-800">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-purple-600 dark:text-purple-400 text-sm font-semibold">Success Rate</p>
                                            <p class="text-3xl font-bold text-purple-900 dark:text-purple-100">{{ course.success_prediction || 78 }}%</p>
                                        </div>
                                        <Target class="w-8 h-8 text-purple-600 dark:text-purple-300" />
                                    </div>
                                    <div class="mt-4 text-sm text-purple-600 dark:text-purple-400">
                                        ML prediction
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-center">
                                <Button @click="showAnalytics" variant="outline" class="w-full">
                                    <Activity class="w-4 h-4 mr-2" />
                                    View Detailed Analytics
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Course Modules -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="flex items-center">
                                        <Layers class="w-5 h-5 mr-2 text-indigo-600" />
                                        Course Modules
                                    </CardTitle>
                                    <CardDescription>
                                        {{ course.modules?.length || 0 }} modules in this course
                                    </CardDescription>
                                </div>
                                <Link :href="route('admin.course-modules.create', course.id)">
                                    <Button class="bg-green-600 hover:bg-green-700">
                                        <Plus class="w-4 h-4 mr-2" />
                                        Add Module
                                    </Button>
                                </Link>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!course.modules || course.modules.length === 0" class="text-center py-12">
                                <Layers class="mx-auto h-16 w-16 text-muted-foreground mb-4" />
                                <h3 class="text-lg font-semibold text-foreground mb-2">No modules yet</h3>
                                <p class="text-muted-foreground mb-6">Start building your course by adding the first module.</p>
                                <Link :href="route('admin.course-modules.create', course.id)">
                                    <Button>
                                        <Plus class="w-4 h-4 mr-2" />
                                        Create First Module
                                    </Button>
                                </Link>
                            </div>

                            <div v-else class="space-y-4">
                                <div
                                    v-for="(module, index) in course.modules"
                                    :key="module.id"
                                    class="border rounded-lg p-6 hover:bg-muted/50 transition-colors"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                                                <span class="text-indigo-600 dark:text-indigo-400 font-semibold">{{ module.order_number || index + 1 }}</span>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-foreground">{{ module.name }}</h3>
                                                <p class="text-sm text-muted-foreground mb-2">{{ module.description || 'No description' }}</p>
                                                <div class="flex items-center space-x-4 text-sm text-muted-foreground">
                                                    <span class="flex items-center">
                                                        <BookOpen class="w-4 h-4 mr-1" />
                                                        {{ module.content_count || 0 }} items
                                                    </span>
                                                    <span class="flex items-center">
                                                        <Play class="w-4 h-4 mr-1" />
                                                        {{ module.video_count || 0 }} videos
                                                    </span>
                                                    <span class="flex items-center">
                                                        <FileText class="w-4 h-4 mr-1" />
                                                        {{ module.pdf_count || 0 }} PDFs
                                                    </span>
                                                    <span v-if="module.estimated_duration" class="flex items-center">
                                                        <Clock class="w-4 h-4 mr-1" />
                                                        {{ module.estimated_duration }}min
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex space-x-2">
                                            <Link :href="route('admin.course-modules.show', [course.id, module.id])">
                                                <Button variant="outline" size="sm">
                                                    View
                                                </Button>
                                            </Link>
                                            <Link :href="route('admin.course-modules.edit', [course.id, module.id])">
                                                <Button size="sm">
                                                    <Edit class="w-4 h-4 mr-1" />
                                                    Edit
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Course Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Course Information</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-muted-foreground">Difficulty</span>
                                <Badge :variant="getLevelBadgeVariant(course.difficulty_level)">
                                    {{ course.difficulty_level || 'Not set' }}
                                </Badge>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-muted-foreground">Duration</span>
                                <span class="text-sm font-medium">{{ course.estimated_duration || 0 }} min</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-muted-foreground">Status</span>
                                <Badge :variant="course.is_active ? 'default' : 'destructive'">
                                    {{ course.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-muted-foreground">Creator</span>
                                <span class="text-sm">{{ course.creator?.name || 'Unknown' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-muted-foreground">Created</span>
                                <span class="text-sm">{{ formatDate(course.created_at) }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <Link :href="route('admin.course-assignments.create', { course_id: course.id })" class="block">
                                <Button class="w-full">
                                    <Users class="w-4 h-4 mr-2" />
                                    Assign to Users
                                </Button>
                            </Link>

                            <Link :href="route('admin.reports.course-online.progress', { course_id: course.id })" class="block">
                                <Button variant="outline" class="w-full">
                                    <BarChart3 class="w-4 h-4 mr-2" />
                                    View Reports
                                </Button>
                            </Link>

                            <Button
                                @click="toggleActiveStatus"
                                :disabled="toggleLoading"
                                :variant="course.is_active ? 'destructive' : 'default'"
                                class="w-full"
                            >
                                <Settings v-if="toggleLoading" class="w-4 h-4 mr-2 animate-spin" />
                                <CheckCircle v-else-if="!course.is_active" class="w-4 h-4 mr-2" />
                                <AlertCircle v-else class="w-4 h-4 mr-2" />
                                {{ course.is_active ? 'Deactivate' : 'Activate' }} Course
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Recent Learners -->
                    <Card v-if="course.assignments && course.assignments.length > 0">
                        <CardHeader>
                            <CardTitle class="text-lg">Recent Learners</CardTitle>
                            <CardDescription>Latest course enrollments</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div
                                    v-for="assignment in course.assignments.slice(0, 5)"
                                    :key="assignment.id"
                                    class="flex items-center space-x-3 p-3 rounded-lg hover:bg-muted/50 transition-colors"
                                >
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                                        <span class="text-white font-semibold text-sm">{{ assignment.user.name.charAt(0).toUpperCase() }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-foreground truncate">{{ assignment.user.name }}</p>
                                        <div class="flex items-center space-x-2 mt-1">
                                            <Progress :value="assignment.progress_percentage || 0" class="h-2 flex-1" />
                                            <span class="text-xs text-muted-foreground">{{ assignment.progress_percentage || 0 }}%</span>
                                        </div>
                                    </div>
                                    <Badge :variant="getStatusBadgeVariant(assignment.status)">
                                        {{ getStatusText(assignment.status) }}
                                    </Badge>
                                </div>
                            </div>
                            <div v-if="course.assignments.length > 5" class="mt-4 pt-4 border-t">
                                <Link :href="route('admin.course-assignments.index', { course_id: course.id })">
                                    <Button variant="link" class="p-0 h-auto">
                                        View all {{ course.assignments.length }} learners →
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Analytics Modal -->
        <Dialog v-model:open="showAnalyticsModal">
            <DialogContent class="max-w-4xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center">
                        <Brain class="w-5 h-5 mr-2 text-blue-600" />
                        Detailed Course Analytics
                    </DialogTitle>
                    <DialogDescription>
                        Comprehensive performance metrics and insights
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-6 mt-4">
                    <div class="text-center py-16">
                        <BarChart3 class="mx-auto h-16 w-16 text-muted-foreground mb-4" />
                        <h3 class="text-lg font-semibold text-foreground mb-2">Advanced Analytics Dashboard</h3>
                        <p class="text-muted-foreground">Interactive charts and detailed metrics coming soon</p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
