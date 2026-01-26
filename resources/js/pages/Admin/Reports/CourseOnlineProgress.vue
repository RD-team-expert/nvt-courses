<script setup lang="ts">
import { ref, watch, computed, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { debounce } from 'lodash'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'

// Icons
import {
    Filter,
    Download,
    BookOpen,
    Users,
    Clock,
    CheckCircle,
    AlertTriangle,
    BarChart3,
    Activity,
    RefreshCw,
    RotateCcw
} from 'lucide-vue-next'

// ✅ PROPER INTERFACES
interface Assignment {
    id: number
    user_name: string
    user_email: string
    department_name: string | null
    course_name: string
    difficulty_level: string
    status: string
    progress_percentage: number
    assigned_at: string
    started_at: string | null
    completed_at: string | null
    total_time_spent: number
    total_sessions: number
    avg_attention_score: number | null
    suspicious_sessions: number
}

interface Course {
    id: number
    name: string
}

interface User {
    id: number
    name: string
    email: string
}

interface Department {
    id: number
    name: string
}

interface Stats {
    total_assignments: number
    completed_assignments: number
    in_progress_assignments: number
    average_completion_rate: number
    total_learning_hours: number
}

interface Filters {
    course_id?: string
    status?: string
    user_id?: string
    department_id?: string
    date_from?: string
    date_to?: string
}

interface PaginationData {
    data: Assignment[]
    links: Array<{
        url: string | null
        label: string
        active: boolean
    }>
    meta: {
        current_page: number
        from: number
        last_page: number
        to: number
        total: number
    }
}

// ✅ PROPER PROPS DEFINITION
const props = withDefaults(defineProps<{
    assignments: PaginationData
    courses: Course[]
    users: User[]
    departments: Department[]
    filters: Filters
    stats: Stats
}>(), {
    assignments: () => ({ data: [], links: [], meta: { current_page: 1, from: 0, last_page: 1, to: 0, total: 0 } }),
    courses: () => [],
    users: () => [],
    departments: () => [],
    filters: () => ({}),
    stats: () => ({ total_assignments: 0, completed_assignments: 0, in_progress_assignments: 0, average_completion_rate: 0, total_learning_hours: 0 })
})

// ✅ PROPER BREADCRUMBS
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Reports & Analytics', href: route('admin.reports.index') },
    { name: 'Course Progress', href: '#' }
]

// ✅ REACTIVE STATE
const courseFilter = ref(props.filters?.course_id || '')
const statusFilter = ref(props.filters?.status || '')
const userFilter = ref(props.filters?.user_id || '')
const departmentFilter = ref(props.filters?.department_id || '')
const dateFromFilter = ref(props.filters?.date_from || '')
const dateToFilter = ref(props.filters?.date_to || '')
const isRefreshing = ref(false)

// ✅ COMPUTED VALUES
const totalAssignments = computed(() => props.assignments?.meta?.total || 0)
const lastPage = computed(() => props.assignments?.meta?.last_page || 1)

// ✅ DEBOUNCED APPLY FILTERS
const applyFilters = debounce(() => {
    const params: Record<string, string> = {}

    if (courseFilter.value && courseFilter.value !== 'all') params.course_id = courseFilter.value
    if (statusFilter.value && statusFilter.value !== 'all') params.status = statusFilter.value
    if (userFilter.value && userFilter.value !== 'all') params.user_id = userFilter.value
    if (departmentFilter.value && departmentFilter.value !== 'all') params.department_id = departmentFilter.value
    if (dateFromFilter.value) params.date_from = dateFromFilter.value
    if (dateToFilter.value) params.date_to = dateToFilter.value

    router.get(route('admin.reports.course-online.progress'), params, {
        preserveState: true,
        preserveScroll: true,
        only: ['assignments', 'stats']
    })
}, 300)

// ✅ CLEAR FILTERS
const clearFilters = () => {
    courseFilter.value = ''
    statusFilter.value = ''
    userFilter.value = ''
    departmentFilter.value = ''
    dateFromFilter.value = ''
    dateToFilter.value = ''

    router.get(route('admin.reports.course-online.progress'), {}, {
        preserveState: true,
        preserveScroll: true
    })
}

// ✅ EXPORT REPORT
const exportReport = () => {
    const params = new URLSearchParams()

    if (courseFilter.value && courseFilter.value !== 'all') params.append('course_id', courseFilter.value)
    if (statusFilter.value && statusFilter.value !== 'all') params.append('status', statusFilter.value)
    if (userFilter.value && userFilter.value !== 'all') params.append('user_id', userFilter.value)
    if (departmentFilter.value && departmentFilter.value !== 'all') params.append('department_id', departmentFilter.value)
    if (dateFromFilter.value) params.append('date_from', dateFromFilter.value)
    if (dateToFilter.value) params.append('date_to', dateToFilter.value)

    const url = route('admin.reports.course-online.export.progress') + '?' + params.toString()
    window.open(url, '_blank')
}

// ✅ REFRESH DATA
const refreshData = () => {
    isRefreshing.value = true

    const params: Record<string, string> = {}
    if (courseFilter.value && courseFilter.value !== 'all') params.course_id = courseFilter.value
    if (statusFilter.value && statusFilter.value !== 'all') params.status = statusFilter.value
    if (userFilter.value && userFilter.value !== 'all') params.user_id = userFilter.value
    if (departmentFilter.value && departmentFilter.value !== 'all') params.department_id = departmentFilter.value
    if (dateFromFilter.value) params.date_from = dateFromFilter.value
    if (dateToFilter.value) params.date_to = dateToFilter.value

    router.get(route('admin.reports.course-online.progress'), params, {
        preserveState: false,
        preserveScroll: true,
        onFinish: () => {
            setTimeout(() => {
                isRefreshing.value = false
            }, 500)
        }
    })
}

// ✅ UTILITY FUNCTIONS
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
        default: return status || 'Unknown'
    }
}

const getDifficultyBadgeVariant = (level: string) => {
    switch (level?.toLowerCase()) {
        case 'beginner': return 'default'
        case 'intermediate': return 'secondary'
        case 'advanced': return 'destructive'
        default: return 'outline'
    }
}

const formatTimeSpent = (minutes: number | null | undefined) => {
    if (!minutes || minutes <= 0) return '0 min'
    if (minutes < 60) return `${Math.round(minutes)}m`

    const hours = Math.floor(minutes / 60)
    const mins = Math.round(minutes % 60)
    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`
}

const formatDate = (dateString: string | null | undefined) => {
    if (!dateString) return 'N/A'

    try {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        })
    } catch {
        return 'Invalid Date'
    }
}

const getProgressVariant = (percentage: number) => {
    if (percentage >= 80) return 'default'
    if (percentage >= 60) return 'secondary'
    if (percentage >= 40) return 'secondary'
    return 'destructive'
}

const getPerformanceRating = (attentionScore: number | null, suspiciousCount: number) => {
    if (!attentionScore && attentionScore !== 0) return 'No Data'

    let rating = attentionScore
    if (suspiciousCount > 0) {
        rating -= (suspiciousCount * 10)
    }
    rating = Math.max(0, rating)

    if (rating >= 85) return 'Excellent'
    if (rating >= 70) return 'Good'
    if (rating >= 50) return 'Average'
    return 'Poor'
}

const getPerformanceColor = (attentionScore: number | null, suspiciousCount: number) => {
    const rating = getPerformanceRating(attentionScore, suspiciousCount)

    switch (rating) {
        case 'Excellent': return 'text-green-600 dark:text-green-400'
        case 'Good': return 'text-blue-600 dark:text-blue-400'
        case 'Average': return 'text-yellow-600 dark:text-yellow-400'
        case 'Poor': return 'text-red-600 dark:text-red-400'
        default: return 'text-muted-foreground'
    }
}

// ✅ WATCHERS
const stopWatching = watch(
    [courseFilter, statusFilter, userFilter, departmentFilter, dateFromFilter, dateToFilter],
    applyFilters
)

// ✅ CLEANUP
onBeforeUnmount(() => {
    stopWatching()
})
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Course Online Progress Report</h1>
                    <p class="text-sm text-muted-foreground mt-1">Track online course assignment progress and performance</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" @click="refreshData" :disabled="isRefreshing">
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': isRefreshing }" />
                        Refresh
                    </Button>
                    <Button @click="exportReport" size="sm">
                        <Download class="mr-2 h-4 w-4" />
                        Export CSV
                    </Button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Assignments</CardTitle>
                        <BookOpen class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_assignments.toLocaleString() }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Completed</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600 dark:text-green-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.completed_assignments.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.total_assignments > 0 ? Math.round((stats.completed_assignments / stats.total_assignments) * 100) : 0 }}% completion rate
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">In Progress</CardTitle>
                        <Activity class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.in_progress_assignments.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground">Currently learning</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Avg Progress</CardTitle>
                        <BarChart3 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ Math.round(stats.average_completion_rate) }}%</div>
                        <Progress :value="stats.average_completion_rate" class="mt-2" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Learning Time</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_learning_hours.toFixed(1) }}h</div>
                        <p class="text-xs text-muted-foreground">Hours invested</p>
                    </CardContent>
                </Card>
            </div>

            <!-- ✅ FIXED: Filters -->
            <Card>
                <CardHeader>
                    <div class="flex items-center">
                        <Filter class="mr-2 h-5 w-5 text-primary" />
                        <div>
                            <CardTitle>Filter Assignments</CardTitle>
                            <CardDescription>Use the filters below to narrow down the assignment records</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7 gap-4">
                        <!-- Course Filter -->
                        <div class="space-y-2">
                            <Label for="course_filter">Course</Label>
                            <Select
                                :model-value="courseFilter || 'all'"
                                @update:model-value="(value) => courseFilter = value === 'all' ? '' : value"
                            >
                                <SelectTrigger id="course_filter">
                                    <SelectValue placeholder="All Courses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Courses</SelectItem>
                                    <SelectItem
                                        v-for="course in courses"
                                        :key="course.id"
                                        :value="course.id.toString()"
                                    >
                                        {{ course.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Status Filter -->
                        <div class="space-y-2">
                            <Label for="status_filter">Status</Label>
                            <Select
                                :model-value="statusFilter || 'all'"
                                @update:model-value="(value) => statusFilter = value === 'all' ? '' : value"
                            >
                                <SelectTrigger id="status_filter">
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="assigned">Not Started</SelectItem>
                                    <SelectItem value="in_progress">In Progress</SelectItem>
                                    <SelectItem value="completed">Completed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- User Filter -->
                        <div class="space-y-2">
                            <Label for="user_filter">User</Label>
                            <Select
                                :model-value="userFilter || 'all'"
                                @update:model-value="(value) => userFilter = value === 'all' ? '' : value"
                            >
                                <SelectTrigger id="user_filter">
                                    <SelectValue placeholder="All Users" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Users</SelectItem>
                                    <SelectItem
                                        v-for="user in users"
                                        :key="user.id"
                                        :value="user.id.toString()"
                                    >
                                        {{ user.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Department Filter -->
                        <div class="space-y-2">
                            <Label for="department_filter">Department</Label>
                            <Select
                                :model-value="departmentFilter || 'all'"
                                @update:model-value="(value) => departmentFilter = value === 'all' ? '' : value"
                            >
                                <SelectTrigger id="department_filter">
                                    <SelectValue placeholder="All Departments" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Departments</SelectItem>
                                    <SelectItem
                                        v-for="department in departments"
                                        :key="department.id"
                                        :value="department.id.toString()"
                                    >
                                        {{ department.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Date From -->
                        <div class="space-y-2">
                            <Label for="date_from">From Date</Label>
                            <Input
                                id="date_from"
                                type="date"
                                v-model="dateFromFilter"
                                placeholder="Start date"
                            />
                        </div>

                        <!-- Date To -->
                        <div class="space-y-2">
                            <Label for="date_to">To Date</Label>
                            <Input
                                id="date_to"
                                type="date"
                                v-model="dateToFilter"
                                placeholder="End date"
                            />
                        </div>

                        <!-- Clear Button -->
                        <div class="flex items-end">
                            <Button variant="outline" @click="clearFilters" class="w-full">
                                <RotateCcw class="mr-2 h-4 w-4" />
                                Reset Filters
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Results -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Assignment Progress</CardTitle>
                            <CardDescription>
                                Showing {{ assignments.meta?.from || 1 }} to {{ assignments.meta?.to || assignments.data.length }}
                                of {{ totalAssignments }} assignments
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="assignments.data.length === 0" class="text-center py-8">
                        <BookOpen class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-2 text-sm font-semibold text-foreground">No assignments found</h3>
                        <p class="mt-1 text-sm text-muted-foreground">Try adjusting your filters to see results.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>
                                        <div class="flex items-center">
                                            <Users class="mr-2 h-4 w-4" />
                                            User
                                        </div>
                                    </TableHead>
                                    <TableHead>
                                        <div class="flex items-center">
                                            <BookOpen class="mr-2 h-4 w-4" />
                                            Course
                                        </div>
                                    </TableHead>
                                    <TableHead>
                                        <div class="flex items-center">
                                            <BarChart3 class="mr-2 h-4 w-4" />
                                            Progress
                                        </div>
                                    </TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>
                                        <div class="flex items-center">
                                            <Clock class="mr-2 h-4 w-4" />
                                            Time Spent
                                        </div>
                                    </TableHead>
                                    <TableHead>Performance</TableHead>
                                    <TableHead>
                                        <div class="flex items-center">
                                            <Calendar class="mr-2 h-4 w-4" />
                                            Assigned Date
                                        </div>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="assignment in assignments.data"
                                    :key="assignment.id"
                                    class="hover:bg-muted/50"
                                >
                                    <!-- User -->
                                    <TableCell>
                                        <div class="space-y-1">
                                            <div class="font-medium text-foreground">{{ assignment.user_name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ assignment.user_email }}</div>
                                            <div v-if="assignment.department_name" class="text-xs text-muted-foreground">
                                                {{ assignment.department_name }}
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Course -->
                                    <TableCell>
                                        <div class="space-y-2">
                                            <div class="font-medium text-foreground">{{ assignment.course_name }}</div>
                                            <Badge :variant="getDifficultyBadgeVariant(assignment.difficulty_level)">
                                                {{ assignment.difficulty_level || 'Unknown' }}
                                            </Badge>
                                        </div>
                                    </TableCell>

                                    <!-- Progress -->
                                    <TableCell>
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="font-medium">{{ Math.round(assignment.progress_percentage) }}%</span>
                                            </div>
                                            <Progress :value="assignment.progress_percentage" class="h-2" />
                                        </div>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :variant="getStatusBadgeVariant(assignment.status)">
                                            {{ getStatusText(assignment.status) }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Time Spent -->
                                    <TableCell>
                                        <div class="space-y-1">
                                            <div class="text-sm font-medium text-foreground">
                                                {{ formatTimeSpent(assignment.total_time_spent) }}
                                            </div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ assignment.total_sessions }} sessions
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Performance -->
                                    <TableCell>
                                        <div class="space-y-1">
                                            <div class="text-sm">
                                                <span
                                                    v-if="assignment.avg_attention_score !== null"
                                                    class="font-medium"
                                                    :class="getPerformanceColor(assignment.avg_attention_score, assignment.suspicious_sessions)"
                                                >
                                                    {{ getPerformanceRating(assignment.avg_attention_score, assignment.suspicious_sessions) }}
                                                </span>
                                                <span v-else class="text-muted-foreground">No data</span>
                                            </div>
                                            <div v-if="assignment.avg_attention_score !== null" class="text-xs text-muted-foreground">
                                                {{ Math.round(assignment.avg_attention_score) }}% attention
                                            </div>
                                            <div v-if="assignment.suspicious_sessions > 0" class="text-xs text-red-600 dark:text-red-400 flex items-center">
                                                <AlertTriangle class="h-3 w-3 mr-1" />
                                                {{ assignment.suspicious_sessions }} suspicious
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Assigned Date -->
                                    <TableCell>
                                        <div class="space-y-1">
                                            <div class="text-sm text-foreground">{{ formatDate(assignment.assigned_at) }}</div>
                                            <div v-if="assignment.started_at" class="text-xs text-muted-foreground">
                                                Started: {{ formatDate(assignment.started_at) }}
                                            </div>
                                            <div v-if="assignment.completed_at" class="text-xs text-green-600 dark:text-green-400">
                                                Completed: {{ formatDate(assignment.completed_at) }}
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="assignments?.data?.length > 0 && (lastPage > 1 || (assignments?.links && assignments.links.length > 0))" class="px-4 sm:px-6 py-3 border-t border-gray-200 mt-6">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-muted-foreground">
                                Showing {{ assignments?.meta?.from || 1 }} to {{ assignments?.meta?.to || assignments.data.length }} of {{ assignments?.meta?.total || assignments.data.length }} results
                            </div>
                            <div class="flex space-x-2">
                                <a v-for="(link, index) in assignments?.links || []"
                                   :key="link.url || link.label || index"
                                   :href="link.url"
                                   :class="{
                                       'bg-primary text-primary-foreground': link.active,
                                       'hover:bg-muted': !link.active && link.url,
                                       'text-muted-foreground cursor-not-allowed': !link.url
                                   }"
                                   class="px-3 py-2 text-sm font-medium border rounded-md transition-colors"
                                   v-html="link.label">
                                </a>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
