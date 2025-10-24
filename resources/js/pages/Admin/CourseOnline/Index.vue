<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Progress } from '@/components/ui/progress'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger, DropdownMenuSeparator } from '@/components/ui/dropdown-menu'
// ✅ NEW: Import Alert Dialog components
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog'

// Icons
import {
    Plus,
    BookOpen,
    Users,
    TrendingUp,
    Award,
    Search,
    Filter,
    MoreHorizontal,
    Eye,
    Edit,
    Settings,
    BarChart3,
    Clock,
    CheckCircle,
    AlertCircle,
    RefreshCw,
    UserCheck,
    Trash2, // ✅ NEW: Delete icon
    AlertTriangle, // ✅ NEW: Warning icon
    Timer,
    CalendarDays,
    AlarmClock,
    Calendar
} from 'lucide-vue-next'

interface Course {
    id: number
    name: string
    description: string
    image_path: string | null
    thumbnails: {
        small: string
        medium: string
        large: string
    } | null
    difficulty_level: 'beginner' | 'intermediate' | 'advanced'
    estimated_duration: number
    is_active: boolean
    creator: {
        id: number
        name: string
    } | null
    modules_count: number
    assignments_count: number
    completion_rate: number
    created_at: string
    upcoming_deadlines_count?: number
    overdue_assignments_count?: number
    default_deadline_days?: number | null
    next_deadline?: string | null
    has_active_deadlines?: boolean
}


interface CoursesData {
    data: Course[]
    links: any
    meta: {
        total: number
        per_page: number
        current_page: number
        last_page: number
        from: number
        to: number
    }
}

const props = defineProps<{
    courses: CoursesData
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Online Courses', href: '#' }
]

// ✅ ADD THESE DEADLINE COMPUTATIONS
const totalUpcomingDeadlines = computed(() =>
    props.courses.data.reduce((sum, course) => sum + (course.upcoming_deadlines_count || 0), 0)
)

const totalOverdueAssignments = computed(() =>
    props.courses.data.reduce((sum, course) => sum + (course.overdue_assignments_count || 0), 0)
)

const coursesWithDeadlines = computed(() =>
    props.courses.data.filter(course => course.has_active_deadlines).length
)

// State
const searchQuery = ref('')
const statusFilter = ref('all')
const levelFilter = ref('all')
const isRefreshing = ref(false)
// ✅ NEW: Delete state
const isDeleting = ref(false)
const courseToDelete = ref<Course | null>(null)

// Computed properties for stats
const activeCourses = computed(() =>
    props.courses.data.filter(course => course.is_active).length
)

const totalEnrollments = computed(() =>
    props.courses.data.reduce((sum, course) => sum + (course.assignments_count || 0), 0)
)

const averageCompletion = computed(() => {
    const completions = props.courses.data.map(course => course.completion_rate || 0)
    const total = completions.reduce((sum, rate) => sum + rate, 0)
    return completions.length ? Math.round(total / completions.length) : 0
})

const filteredCourses = computed(() => {
    let filtered = props.courses.data

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(course =>
            course.name.toLowerCase().includes(query) ||
            course.description?.toLowerCase().includes(query)
        )
    }

    // Status filter
    if (statusFilter.value !== 'all') {
        const isActive = statusFilter.value === 'active'
        filtered = filtered.filter(course => course.is_active === isActive)
    }

    // Level filter
    if (levelFilter.value !== 'all') {
        filtered = filtered.filter(course => course.difficulty_level === levelFilter.value)
    }

    return filtered
})

// Methods
const truncateText = (text: string | null, maxLength: number): string => {
    if (!text) return ''
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
}

// ✅ ADD THESE DEADLINE FUNCTIONS
const formatDeadline = (dateString: string): string => {
    try {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        })
    } catch {
        return 'Invalid date'
    }
}

const getDeadlineUrgency = (deadline: string): 'safe' | 'warning' | 'danger' => {
    const now = new Date().getTime()
    const deadlineTime = new Date(deadline).getTime()
    const hoursUntil = (deadlineTime - now) / (1000 * 60 * 60)

    if (hoursUntil <= 24) return 'danger'
    if (hoursUntil <= 72) return 'warning'
    return 'safe'
}

const getUrgencyColor = (urgency: string): string => {
    switch (urgency) {
        case 'danger': return 'text-red-600 bg-red-50'
        case 'warning': return 'text-orange-600 bg-orange-50'
        case 'safe': return 'text-green-600 bg-green-50'
        default: return 'text-gray-600 bg-gray-50'
    }
}

const navigateToDeadlines = () => {
    router.get('/admin/course-assignments/deadlines')
}

const getDifficultyColor = (level: string): string => {
    switch (level) {
        case 'beginner': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
        case 'intermediate': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
        case 'advanced': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
    }
}

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    })
}

const formatDuration = (minutes: number): string => {
    if (minutes < 60) return `${minutes}m`
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`
}

const refreshData = async () => {
    isRefreshing.value = true
    try {
        router.reload()
    } finally {
        setTimeout(() => {
            isRefreshing.value = false
        }, 1000)
    }
}

const toggleCourseStatus = async (courseId: number) => {
    router.patch(route('admin.course-online.toggle-active', courseId))
}

// ✅ NEW: Delete course functionality
const confirmDelete = (course: Course) => {
    courseToDelete.value = course
}

const deleteCourse = async () => {
    if (!courseToDelete.value) return

    isDeleting.value = true

    try {
        router.delete(route('admin.course-online.destroy', courseToDelete.value.id), {
            onSuccess: () => {
                // Success feedback will be handled by the backend flash message
                courseToDelete.value = null
            },
            onError: (errors) => {
                console.error('Delete failed:', errors)
                // Error feedback will be handled by the backend flash message
            },
            onFinish: () => {
                isDeleting.value = false
            }
        })
    } catch (error) {
        console.error('Delete error:', error)
        isDeleting.value = false
    }
}

// ✅ NEW: Check if course can be deleted
const canDeleteCourse = (course: Course): boolean => {
    // Don't allow deletion if there are active enrollments
    return course.assignments_count === 0
}

// ✅ NEW: Get delete warning message
const getDeleteWarning = (course: Course): string => {
    if (course.assignments_count > 0) {
        return `This course has ${course.assignments_count} enrolled students. You cannot delete it while students are enrolled.`
    }
    return `Are you sure you want to delete "${course.name}"? This action cannot be undone and will permanently remove all course content, modules, and related data.`
}

const clearFilters = () => {
    searchQuery.value = ''
    statusFilter.value = 'all'
    levelFilter.value = 'all'
}
</script>

<template>
    <Head title="Online Courses Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Online Courses</h1>
                    <p class="text-muted-foreground">Manage your online courses and track student progress</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="refreshData" :disabled="isRefreshing">
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': isRefreshing }" />
                        Refresh
                    </Button>
                    <!-- ✅ NEW: Deadline Management Button -->

                    <Button asChild variant="outline">
                        <Link href="/admin/course-assignments">
                            <UserCheck class="mr-3 h-4 w-4" />
                            Course Assignments
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link :href="route('admin.course-online.create')">
                            <Plus class="mr-2 h-4 w-4" />
                            Create Course
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- ✅ NEW: Deadline Alert Banner -->
            <div v-if="totalOverdueAssignments > 0" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <AlarmClock class="h-5 w-5 text-red-500 mr-3" />
                        <div>
                            <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                Overdue Assignments Alert
                            </h3>
                            <p class="text-sm text-red-600 dark:text-red-300">
                                {{ totalOverdueAssignments }} assignment{{ totalOverdueAssignments === 1 ? '' : 's' }} are past deadline
                            </p>
                        </div>
                    </div>
                    <Button @click="navigateToDeadlines" variant="outline" size="sm" class="border-red-200 text-red-700 hover:bg-red-100">
                        <CalendarDays class="h-4 w-4 mr-2" />
                        Manage Deadlines
                    </Button>
                </div>
            </div>


            <!-- Stats Cards -->
            <!-- Stats Cards -->
<!--            <div class="grid gap-4 md:grid-cols-5">-->
<!--                <Card>-->
<!--                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">-->
<!--                        <CardTitle class="text-sm font-medium">Total Courses</CardTitle>-->
<!--                        <BookOpen class="h-4 w-4 text-muted-foreground" />-->
<!--                    </CardHeader>-->
<!--                    <CardContent>-->
<!--                        <div class="text-2xl font-bold">{{ courses.meta?.total || courses.data.length }}</div>-->
<!--                        <p class="text-xs text-muted-foreground">-->
<!--                            +{{ courses.data.filter(c => new Date(c.created_at) > new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)).length }} this month-->
<!--                        </p>-->
<!--                    </CardContent>-->
<!--                </Card>-->

<!--                <Card>-->
<!--                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">-->
<!--                        <CardTitle class="text-sm font-medium">Active Courses</CardTitle>-->
<!--                        <CheckCircle class="h-4 w-4 text-muted-foreground" />-->
<!--                    </CardHeader>-->
<!--                    <CardContent>-->
<!--                        <div class="text-2xl font-bold">{{ activeCourses }}</div>-->
<!--                        <p class="text-xs text-muted-foreground">-->
<!--                            {{ Math.round((activeCourses / courses.data.length) * 100) || 0 }}% of total-->
<!--                        </p>-->
<!--                    </CardContent>-->
<!--                </Card>-->

<!--                <Card>-->
<!--                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">-->
<!--                        <CardTitle class="text-sm font-medium">Total Enrollments</CardTitle>-->
<!--                        <Users class="h-4 w-4 text-muted-foreground" />-->
<!--                    </CardHeader>-->
<!--                    <CardContent>-->
<!--                        <div class="text-2xl font-bold">{{ totalEnrollments }}</div>-->
<!--                        <p class="text-xs text-muted-foreground">-->
<!--                            Avg {{ Math.round(totalEnrollments / (courses.data.length || 1)) }} per course-->
<!--                        </p>-->
<!--                    </CardContent>-->
<!--                </Card>-->

<!--                &lt;!&ndash; ✅ NEW: Upcoming Deadlines Card &ndash;&gt;-->
<!--                <Card>-->
<!--                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">-->
<!--                        <CardTitle class="text-sm font-medium">Due Soon</CardTitle>-->
<!--                        <Timer class="h-4 w-4 text-orange-500" />-->
<!--                    </CardHeader>-->
<!--                    <CardContent>-->
<!--                        <div class="text-2xl font-bold text-orange-600">{{ totalUpcomingDeadlines }}</div>-->
<!--                        <p class="text-xs text-muted-foreground">-->
<!--                            {{ coursesWithDeadlines }} courses with deadlines-->
<!--                        </p>-->
<!--                    </CardContent>-->
<!--                </Card>-->

<!--                &lt;!&ndash; ✅ NEW: Overdue Assignments Card &ndash;&gt;-->
<!--                <Card>-->
<!--                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">-->
<!--                        <CardTitle class="text-sm font-medium">Overdue</CardTitle>-->
<!--                        <AlarmClock class="h-4 w-4 text-red-500" />-->
<!--                    </CardHeader>-->
<!--                    <CardContent>-->
<!--                        <div class="text-2xl font-bold text-red-600">{{ totalOverdueAssignments }}</div>-->
<!--                        <p class="text-xs text-muted-foreground">-->
<!--                            Need immediate attention-->
<!--                        </p>-->
<!--                    </CardContent>-->
<!--                </Card>-->
<!--            </div>-->

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Filter class="h-5 w-5" />
                        Filters
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-4">
                        <!-- Search -->
                        <div class="flex-1 relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                placeholder="Search courses..."
                                class="pl-10"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div class="w-40">
                            <Select v-model="statusFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Level Filter -->
                        <div class="w-40">
                            <Select v-model="levelFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Level" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Levels</SelectItem>
                                    <SelectItem value="beginner">Beginner</SelectItem>
                                    <SelectItem value="intermediate">Intermediate</SelectItem>
                                    <SelectItem value="advanced">Advanced</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Clear Filters -->
                        <Button variant="outline" @click="clearFilters">
                            Clear
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Courses Content -->
            <Tabs default-value="grid" class="space-y-4">
                <div class="flex items-center justify-between">
                    <TabsList>
                        <TabsTrigger value="grid">Grid View</TabsTrigger>
                        <TabsTrigger value="table">Table View</TabsTrigger>
                    </TabsList>
                </div>

                <!-- Grid View -->
                <TabsContent value="grid" class="space-y-4">
                    <div v-if="filteredCourses.length === 0" class="flex flex-col items-center justify-center p-12 border-2 border-dashed border-input rounded-lg">
                        <BookOpen class="h-16 w-16 text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium mb-2">No courses found</h3>
                        <p class="text-muted-foreground mb-4">
                            {{ searchQuery || statusFilter !== 'all' || levelFilter !== 'all'
                            ? 'Try adjusting your filters'
                            : 'Get started by creating your first online course' }}
                        </p>
                        <Button as-child v-if="!searchQuery && statusFilter === 'all' && levelFilter === 'all'">
                            <Link :href="route('admin.course-online.create')">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Course
                            </Link>
                        </Button>
                    </div>

                    <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <Card
                            v-for="course in filteredCourses"
                            :key="course.id"
                            class="group hover:shadow-lg transition-shadow duration-200"
                        >
                            <CardHeader class="pb-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <Avatar class="h-12 w-12">
                                            <AvatarImage
                                                v-if="course.image_path"
                                                :src="course.thumbnails?.small || course.image_path"
                                                :alt="course.name"
                                            />
                                            <AvatarFallback>
                                                <BookOpen class="h-6 w-6" />
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="flex-1">
                                            <CardTitle class="text-lg line-clamp-1">{{ course.name }}</CardTitle>
                                            <CardDescription class="line-clamp-2">
                                                {{ truncateText(course.description, 80) }}
                                            </CardDescription>
                                        </div>
                                    </div>

                                    <!-- Actions Dropdown -->
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="sm">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem as-child>
                                                <Link :href="route('admin.course-online.show', course.id)">
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    View Details
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem as-child>
                                                <Link :href="route('admin.course-modules.index', course.id)">
                                                    <Settings class="mr-2 h-4 w-4" />
                                                    Manage Modules
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem as-child>
                                                <Link :href="route('admin.course-online.edit', course.id)">
                                                    <Edit class="mr-2 h-4 w-4" />
                                                    Edit Course
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                @click="confirmDelete(course)"
                                                :disabled="!canDeleteCourse(course)"
                                                class="text-red-600 focus:text-red-600 focus:bg-red-50 dark:text-red-400 dark:focus:text-red-300 dark:focus:bg-red-950/20"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Delete Course
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </CardHeader>

                            <CardContent class="space-y-4">
                                <!-- Course Badges -->
                                <div class="flex items-center gap-2 flex-wrap">
                                    <Badge :class="getDifficultyColor(course.difficulty_level)">
                                        {{ course.difficulty_level }}
                                    </Badge>
                                    <Badge :variant="course.is_active ? 'default' : 'secondary'">
                                        {{ course.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                    <Badge v-if="course.assignments_count > 0" variant="outline" class="text-orange-600 border-orange-600/50 dark:text-orange-400 dark:border-orange-400/50">
                                        {{ course.assignments_count }} enrolled
                                    </Badge>
                                    <!-- ✅ FIXED: Deadline Badge with dark mode -->
                                    <Badge v-if="course.has_deadline" variant="outline" class="text-blue-600 border-blue-600/50 dark:text-blue-400 dark:border-blue-400/50 bg-blue-50/50 dark:bg-blue-950/20">
                                        <Calendar class="mr-1 h-3 w-3" />
                                        Deadline Set
                                    </Badge>
                                </div>

                                <!-- Course Stats -->
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div class="text-lg font-bold">{{ course.modules_count }}</div>
                                        <div class="text-xs text-muted-foreground">Modules</div>
                                    </div>
                                    <div>
                                        <div class="text-lg font-bold">{{ course.assignments_count }}</div>
                                        <div class="text-xs text-muted-foreground">Enrolled</div>
                                    </div>
                                    <div>
                                        <div class="text-lg font-bold">{{ Math.round(course.completion_rate) }}%</div>
                                        <div class="text-xs text-muted-foreground">Completion</div>
                                    </div>
                                </div>

                                <!-- ✅ FIXED: Course Deadline Information with proper dark mode -->
                                <div v-if="course.has_deadline" class="mt-4 p-3 rounded-lg border"
                                     :class="{
                             'bg-red-50 border-red-200 dark:bg-red-950/30 dark:border-red-800': course.overdue_assignments_count > 0,
                             'bg-orange-50 border-orange-200 dark:bg-orange-950/30 dark:border-orange-800': course.upcoming_deadlines_count > 0 && !course.overdue_assignments_count,
                             'bg-blue-50 border-blue-200 dark:bg-blue-950/30 dark:border-blue-800': !course.overdue_assignments_count && !course.upcoming_deadlines_count
                         }">
                                    <div class="flex items-center justify-between text-sm mb-2">
                                        <div class="flex items-center gap-2">
                                            <Calendar class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                            <span class="font-medium text-foreground">Course Deadline:</span>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded-full border font-medium" :class="{
                                'text-red-700 bg-red-100 border-red-300 dark:text-red-300 dark:bg-red-900/50 dark:border-red-700': course.overdue_assignments_count > 0,
                                'text-orange-700 bg-orange-100 border-orange-300 dark:text-orange-300 dark:bg-orange-900/50 dark:border-orange-700': course.upcoming_deadlines_count > 0 && !course.overdue_assignments_count,
                                'text-blue-700 bg-blue-100 border-blue-300 dark:text-blue-300 dark:bg-blue-900/50 dark:border-blue-700': !course.overdue_assignments_count && !course.upcoming_deadlines_count
                            }">
                                {{ course.deadline_type === 'strict' ? 'Strict' : 'Flexible' }}
                            </span>
                                    </div>

                                    <!-- Course Default Deadline -->
                                    <div v-if="course.deadline" class="text-sm mb-2 text-muted-foreground">
                                        📅 {{ formatDeadline(course.deadline) }}
                                    </div>

                                    <!-- Assignment Status Summary -->
                                    <div v-if="course.assignments_count > 0" class="space-y-1">
                                        <div v-if="course.overdue_assignments_count > 0" class="flex items-center gap-2 p-2 rounded-md bg-red-100/70 border border-red-200 dark:bg-red-900/30 dark:border-red-700">
                                            <AlarmClock class="h-3 w-3 text-red-600 dark:text-red-400" />
                                            <span class="text-xs text-red-700 dark:text-red-300 font-medium">
                                    {{ course.overdue_assignments_count }} overdue assignment{{ course.overdue_assignments_count !== 1 ? 's' : '' }}
                                </span>
                                        </div>
                                        <div v-if="course.upcoming_deadlines_count > 0" class="flex items-center gap-2 p-2 rounded-md bg-orange-100/70 border border-orange-200 dark:bg-orange-900/30 dark:border-orange-700">
                                            <Timer class="h-3 w-3 text-orange-600 dark:text-orange-400" />
                                            <span class="text-xs text-orange-700 dark:text-orange-300 font-medium">
                                    {{ course.upcoming_deadlines_count }} due soon
                                </span>
                                        </div>
                                        <div v-if="!course.overdue_assignments_count && !course.upcoming_deadlines_count" class="flex items-center gap-2 p-2 rounded-md bg-green-100/70 border border-green-200 dark:bg-green-900/30 dark:border-green-700">
                                            <CheckCircle class="h-3 w-3 text-green-600 dark:text-green-400" />
                                            <span class="text-xs text-green-700 dark:text-green-300">✅ All assignments on track</span>
                                        </div>
                                    </div>

                                    <!-- No Assignments Message -->
                                    <div v-else class="text-xs text-muted-foreground p-2 rounded-md bg-muted/30 border border-muted-foreground/20">
                                        No active assignments
                                    </div>
                                </div>

                                <!-- ✅ FIXED: No Deadline Information with dark mode -->
                                <div v-else class="mt-4 p-3 rounded-lg bg-gray-50 border border-gray-200 dark:bg-muted/20 dark:border-muted-foreground/20">
                                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-muted-foreground">
                                        <CalendarDays class="h-4 w-4" />
                                        <span>No deadline set for this course</span>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span>Completion Rate</span>
                                        <span>{{ Math.round(course.completion_rate) }}%</span>
                                    </div>
                                    <Progress :value="course.completion_rate" />
                                </div>

                                <!-- Course Info -->
                                <div class="flex items-center justify-between text-sm text-muted-foreground">
                                    <div class="flex items-center gap-1">
                                        <Clock class="h-3 w-3" />
                                        {{ formatDuration(course.estimated_duration) }}
                                    </div>
                                    <div>Created {{ formatDate(course.created_at) }}</div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex gap-2 pt-2">
                                    <Button as-child size="sm" class="flex-1">
                                        <Link :href="route('admin.course-online.show', course.id)">
                                            <Eye class="mr-2 h-4 w-4" />
                                            View
                                        </Link>
                                    </Button>
                                    <Button as-child variant="outline" size="sm" class="flex-1">
                                        <Link :href="route('admin.course-modules.index', course.id)">
                                            <Settings class="mr-2 h-4 w-4" />
                                            Modules
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Table View -->
                <TabsContent value="table">
                    <Card>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Course</TableHead>
                                    <TableHead>Level</TableHead>
                                    <TableHead>Modules</TableHead>
                                    <TableHead>Enrolled</TableHead>
                                    <TableHead>Completion</TableHead>
                                    <!-- ✅ Deadline Column -->
                                    <TableHead>Deadline Status</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="course in filteredCourses" :key="course.id">
                                    <!-- Course Name & Image -->
                                    <TableCell>
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-8 w-8">
                                                <AvatarImage
                                                    v-if="course.image_path"
                                                    :src="course.thumbnails?.small || course.image_path"
                                                    :alt="course.name"
                                                />
                                                <AvatarFallback>
                                                    <BookOpen class="h-4 w-4" />
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium">{{ course.name }}</div>
                                                <div class="text-sm text-muted-foreground">
                                                    {{ truncateText(course.description, 50) }}
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Difficulty Level -->
                                    <TableCell>
                                        <Badge :class="getDifficultyColor(course.difficulty_level)">
                                            {{ course.difficulty_level }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Modules Count -->
                                    <TableCell>{{ course.modules_count }}</TableCell>

                                    <!-- Enrolled Count -->
                                    <TableCell>{{ course.assignments_count }}</TableCell>

                                    <!-- Completion Progress -->
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Progress :value="course.completion_rate" class="w-16" />
                                            <span class="text-sm">{{ Math.round(course.completion_rate) }}%</span>
                                        </div>
                                    </TableCell>

                                    <!-- ✅ FIXED: Deadline Status Cell with proper dark mode -->
                                    <TableCell>
                                        <div class="space-y-1">
                                            <!-- Course has deadline -->
                                            <div v-if="course.has_deadline">
                                                <!-- Course deadline info -->
                                                <div class="flex items-center gap-1 text-xs">
                                                    <Calendar class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                                    <span class="text-blue-700 dark:text-blue-300 font-medium">
                                            {{ course.deadline ? formatDeadline(course.deadline) : 'Set' }}
                                        </span>
                                                    <span class="text-xs px-1.5 py-0.5 rounded-full text-muted-foreground bg-muted/40 border border-muted-foreground/20">
                                            {{ course.deadline_type === 'strict' ? 'Strict' : 'Flexible' }}
                                        </span>
                                                </div>

                                                <!-- Assignment status if any exist -->
                                                <div v-if="course.assignments_count > 0" class="space-y-1">
                                                    <!-- Overdue assignments -->
                                                    <div v-if="course.overdue_assignments_count > 0" class="flex items-center gap-1">
                                                        <AlarmClock class="h-3 w-3 text-red-600 dark:text-red-400" />
                                                        <span class="text-xs text-red-700 dark:text-red-300 font-medium">
                                                {{ course.overdue_assignments_count }} overdue
                                            </span>
                                                    </div>

                                                    <!-- Due soon assignments -->
                                                    <div v-if="course.upcoming_deadlines_count > 0" class="flex items-center gap-1">
                                                        <Timer class="h-3 w-3 text-orange-600 dark:text-orange-400" />
                                                        <span class="text-xs text-orange-700 dark:text-orange-300 font-medium">
                                                {{ course.upcoming_deadlines_count }} due soon
                                            </span>
                                                    </div>

                                                    <!-- All on track -->
                                                    <div v-if="!course.overdue_assignments_count && !course.upcoming_deadlines_count"
                                                         class="flex items-center gap-1">
                                                        <CheckCircle class="h-3 w-3 text-green-600 dark:text-green-400" />
                                                        <span class="text-xs text-green-700 dark:text-green-300">On track</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- No deadline set -->
                                            <div v-else class="flex items-center gap-1">
                                                <CalendarDays class="h-3 w-3 text-gray-500 dark:text-muted-foreground" />
                                                <span class="text-xs text-gray-600 dark:text-muted-foreground">No deadline</span>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Course Active Status -->
                                    <TableCell>
                                        <Badge :variant="course.is_active ? 'default' : 'secondary'">
                                            {{ course.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="sm">
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem as-child>
                                                    <Link :href="route('admin.course-online.show', course.id)">
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View Details
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem as-child>
                                                    <Link :href="route('admin.course-modules.index', course.id)">
                                                        <Settings class="mr-2 h-4 w-4" />
                                                        Manage Modules
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem as-child>
                                                    <Link :href="route('admin.course-online.edit', course.id)">
                                                        <Edit class="mr-2 h-4 w-4" />
                                                        Edit Course
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    @click="confirmDelete(course)"
                                                    :disabled="!canDeleteCourse(course)"
                                                    class="text-red-600 focus:text-red-600 focus:bg-red-50 dark:text-red-400 dark:focus:text-red-300 dark:focus:bg-red-950/20"
                                                >
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    Delete Course
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </Card>
                </TabsContent>
            </Tabs>

            <!-- Pagination -->
            <div v-if="courses.meta && courses.meta.last_page > 1" class="flex items-center justify-center space-x-2">
                <Button
                    v-for="link in courses.links"
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    size="sm"
                    @click="link.url && router.get(link.url)"
                    v-html="link.label"
                />
            </div>
        </div>

        <!-- ✅ NEW: Delete Confirmation Dialog -->
        <AlertDialog :open="!!courseToDelete">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle class="flex items-center gap-2">
                        <AlertTriangle class="h-5 w-5 text-red-600" />
                        Delete Course
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        <div v-if="courseToDelete">
                            {{ getDeleteWarning(courseToDelete) }}

                            <div v-if="canDeleteCourse(courseToDelete)" class="mt-4 p-3 bg-red-50 rounded-lg border border-red-200">
                                <h4 class="font-medium text-red-800 mb-2">This will permanently delete:</h4>
                                <ul class="text-sm text-red-700 space-y-1">
                                    <li>• Course: {{ courseToDelete.name }}</li>
                                    <li>• {{ courseToDelete.modules_count }} modules and all their content</li>
                                    <li>• All associated PDFs, videos, and learning materials</li>
                                    <li>• All progress tracking data</li>
                                </ul>
                            </div>

                            <div v-else class="mt-4 p-3 bg-orange-50 rounded-lg border border-orange-200">
                                <h4 class="font-medium text-orange-800 mb-2">Cannot Delete</h4>
                                <p class="text-sm text-orange-700">
                                    This course has active student enrollments. Please remove all student assignments before deleting the course.
                                </p>
                            </div>
                        </div>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="courseToDelete = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        v-if="courseToDelete && canDeleteCourse(courseToDelete)"
                        @click="deleteCourse"
                        :disabled="isDeleting"
                        class="bg-red-600 hover:bg-red-700 focus:ring-red-600"
                    >
                        <Trash2 v-if="!isDeleting" class="mr-2 h-4 w-4" />
                        <RefreshCw v-else class="mr-2 h-4 w-4 animate-spin" />
                        {{ isDeleting ? 'Deleting...' : 'Delete Course' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
