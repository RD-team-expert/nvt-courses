<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Progress } from '@/components/ui/progress'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'

// Icons
import {
    Plus,
    Search,
    Filter,
    Users,
    BookOpen,
    TrendingUp,
    Clock,
    CheckCircle,
    AlertCircle,
    MoreHorizontal,
    Eye,
    Mail,
    Trash2,
    ArrowLeft,
    RefreshCw,
    UserCheck,
    Award
} from 'lucide-vue-next'

interface User {
    id: number
    name: string
    email: string
}

interface Course {
    id: number
    name: string
    difficulty_level: string
    estimated_duration: number
}

interface AssignedBy {
    id: number
    name: string
}

interface Assignment {
    id: number
    course: Course
    user: User
    assigned_by: AssignedBy
    status: 'assigned' | 'in_progress' | 'completed'
    progress_percentage: number
    assigned_at: string
    started_at: string | null
    completed_at: string | null
    time_spent: string
    current_module: number | null
}

interface Stats {
    total_assignments: number
    active_assignments: number
    completed_assignments: number
    average_completion_rate: number
}

interface Filters {
    course_id?: string
    status?: string
    user_id?: string
    search?: string
}

const props = defineProps<{
    assignments: {
        data: Assignment[]
        links?: any[]
        meta?: {
            total: number
            per_page: number
            current_page: number
            last_page: number
            from: number
            to: number
        }
    }
    courses: Course[]
    users: User[]
    stats: Stats
    filters: Filters
}>()

// ✅ FIXED: Updated breadcrumbs to go to course-online
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Online Courses', href: '/admin/course-online' },
    { title: 'Course Assignments', href: '#' }
]

// Search and filters
const searchTerm = ref(props.filters.search || '')
const selectedCourse = ref(props.filters.course_id || 'all')
const selectedStatus = ref(props.filters.status || 'all')
const selectedUser = ref(props.filters.user_id || 'all')
const isRefreshing = ref(false)

const totalAssignments = computed(() => props.assignments.meta?.total ?? props.assignments.data.length)

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

const getProgressColor = (percentage: number) => {
    if (percentage >= 80) return 'bg-green-500'
    if (percentage >= 50) return 'bg-blue-500'
    if (percentage >= 25) return 'bg-yellow-500'
    return 'bg-gray-300'
}

const applyFilters = () => {
    const params = new URLSearchParams()

    if (searchTerm.value) params.append('search', searchTerm.value)
    if (selectedCourse.value && selectedCourse.value !== 'all') params.append('course_id', selectedCourse.value)
    if (selectedStatus.value && selectedStatus.value !== 'all') params.append('status', selectedStatus.value)
    if (selectedUser.value && selectedUser.value !== 'all') params.append('user_id', selectedUser.value)

    router.get(route('admin.course-assignments.index'), Object.fromEntries(params))
}

const clearFilters = () => {
    searchTerm.value = ''
    selectedCourse.value = 'all'
    selectedStatus.value = 'all'
    selectedUser.value = 'all'
    router.get(route('admin.course-assignments.index'))
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

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    })
}

const deleteAssignment = (assignmentId: number) => {
    if (confirm('Are you sure you want to delete this assignment? This action cannot be undone.')) {
        router.delete(route('admin.course-assignments.destroy', assignmentId), {
            onSuccess: () => {
                // Refresh the page data
                router.reload()
            }
        })
    }
}
</script>

<template>
    <Head title="Course Assignments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- ✅ FIXED: Header with back to courses button -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button asChild variant="ghost" size="sm">
                        <Link href="/admin/courses-online">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Courses
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Course Assignments</h1>
                        <p class="text-muted-foreground">Manage and track course assignments for all users</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="refreshData" :disabled="isRefreshing">
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': isRefreshing }" />
                        Refresh
                    </Button>
                    <Button asChild>
                        <Link :href="route('admin.course-assignments.create')">
                            <Plus class="mr-2 h-4 w-4" />
                            Assign Courses
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Assignments</CardTitle>
                        <BookOpen class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_assignments.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground">
                            All course assignments
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Learners</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.active_assignments.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground">
                            Currently learning
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Completed</CardTitle>
                        <CheckCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.completed_assignments.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground">
                            Successfully finished
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Avg. Progress</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ Math.round(stats.average_completion_rate) }}%</div>
                        <Progress :value="stats.average_completion_rate" class="mt-2" />
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Filter class="h-5 w-5" />
                        Filters & Search
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="applyFilters" class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-5">
                            <!-- Search -->
                            <div class="relative">
                                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="searchTerm"
                                    placeholder="Search users or courses..."
                                    class="pl-10"
                                />
                            </div>

                            <!-- Course Filter -->
                            <Select v-model="selectedCourse">
                                <SelectTrigger>
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

                            <!-- Status Filter -->
                            <Select v-model="selectedStatus">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="assigned">Not Started</SelectItem>
                                    <SelectItem value="in_progress">In Progress</SelectItem>
                                    <SelectItem value="completed">Completed</SelectItem>
                                </SelectContent>
                            </Select>

                            <!-- User Filter -->
                            <Select v-model="selectedUser">
                                <SelectTrigger>
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

                            <!-- Action Buttons -->
                            <div class="flex gap-2">
                                <Button type="submit" class="flex-1">
                                    Apply
                                </Button>
                                <Button type="button" variant="outline" @click="clearFilters">
                                    Clear
                                </Button>
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Assignments Table -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Assignment List</CardTitle>
                            <CardDescription>
                                Showing {{ assignments.meta?.from || 1 }} to {{ assignments.meta?.to || assignments.data.length }} of {{ totalAssignments }} assignments
                            </CardDescription>
                        </div>
                        <!-- ✅ REMOVED: Export button as requested -->
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="assignments.data.length === 0" class="flex flex-col items-center justify-center p-12 border-2 border-dashed border-input rounded-lg">
                        <UserCheck class="h-16 w-16 text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium mb-2">No assignments found</h3>
                        <p class="text-muted-foreground mb-4">
                            {{ searchTerm || selectedCourse !== 'all' || selectedStatus !== 'all' || selectedUser !== 'all'
                            ? 'Try adjusting your filters'
                            : 'Get started by assigning courses to users' }}
                        </p>
                        <Button asChild v-if="!searchTerm && selectedCourse === 'all' && selectedStatus === 'all' && selectedUser === 'all'">
                            <Link :href="route('admin.course-assignments.create')">
                                <Plus class="mr-2 h-4 w-4" />
                                Assign Courses
                            </Link>
                        </Button>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Student</TableHead>
                                    <TableHead>Course</TableHead>
                                    <TableHead>Progress</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Assigned Date</TableHead>
                                    <TableHead>Time Spent</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="assignment in assignments.data" :key="assignment.id" class="hover:bg-muted/50">
                                    <!-- Student -->
                                    <TableCell>
                                        <div>
                                            <div class="font-medium">{{ assignment.user.name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ assignment.user.email }}</div>
                                        </div>
                                    </TableCell>

                                    <!-- Course -->
                                    <TableCell>
                                        <div>
                                            <div class="font-medium">{{ assignment.course.name }}</div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <Badge
                                                    :class="getDifficultyColor(assignment.course.difficulty_level)"
                                                    class="text-xs"
                                                >
                                                    {{ assignment.course.difficulty_level }}
                                                </Badge>
                                                <span class="text-xs text-muted-foreground">
                                                    {{ assignment.course.estimated_duration }}min
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Progress -->
                                    <TableCell>
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between text-sm">
                                                <span>{{ Math.round(assignment.progress_percentage) }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                                <div
                                                    :class="getProgressColor(assignment.progress_percentage)"
                                                    class="h-2 rounded-full transition-all duration-300"
                                                    :style="{ width: `${Math.min(assignment.progress_percentage, 100)}%` }"
                                                ></div>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <Badge :class="getStatusColor(assignment.status)">
                                            {{ getStatusText(assignment.status) }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Assigned Date -->
                                    <TableCell>
                                        <div class="text-sm">
                                            {{ assignment.assigned_at }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            by {{ assignment.assigned_by.name }}
                                        </div>
                                    </TableCell>

                                    <!-- ✅ FIXED: Time Spent showing formatted days, hours, minutes -->
                                    <TableCell>
                                        <div class="text-sm font-mono">{{ assignment.time_spent }}</div>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm">
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem asChild>
                                                    <Link :href="route('admin.course-assignments.show', assignment.id)">
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View Details
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="deleteAssignment(assignment.id)" class="text-destructive">
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    Delete Assignment
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="assignments.links && assignments.links.length > 3" class="flex items-center justify-center space-x-2 mt-6">
                        <Button
                            v-for="link in assignments.links"
                            :key="link.label"
                            :variant="link.active ? 'default' : 'outline'"
                            :disabled="!link.url"
                            size="sm"
                            @click="link.url && router.get(link.url)"
                            v-html="link.label"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom styles for better responsiveness */
.overflow-x-auto {
    min-width: 100%;
}

@media (max-width: 768px) {
    .grid.gap-4.md\:grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .grid.gap-4.md\:grid-cols-5 {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .grid.gap-4.md\:grid-cols-4 {
        grid-template-columns: 1fr;
    }
}

/* Progress bar animation */
.transition-all {
    transition: width 0.3s ease-in-out;
}

/* Table hover effects */
.hover\:bg-muted\/50:hover {
    background-color: rgba(var(--muted), 0.5);
}
</style>
