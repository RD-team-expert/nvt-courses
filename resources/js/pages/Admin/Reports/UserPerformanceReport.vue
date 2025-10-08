<!--
  User Performance Report Page
  Comprehensive reporting interface for tracking user performance and learning analytics
-->
<script setup lang="ts">
import { ref, watch } from 'vue'
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    Download,
    Filter,
    RotateCcw,
    User,
    Users,
    Calendar,
    Clock,
    Target,
    TrendingUp,
    CheckCircle,
    AlertTriangle,
    BookOpen,
    Brain,
    Award,
    BarChart3
} from 'lucide-vue-next'

const props = defineProps({
    users: Object,
    courses: Array,
    allUsers: Array,
    filters: Object,
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Reports & Analytics', href: route('admin.reports.index') },
    { name: 'User Performance', href: route('admin.reports.course-online.user-performance') }
]

// Filter state
const filters = ref({
    user_id: props.filters?.user_id || '',
    course_id: props.filters?.course_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
})

// Modal state for user details
const showModal = ref(false)
const modalTitle = ref('')
const selectedUser = ref(null)

// ✅ PERFORMANCE RATING HELPER FUNCTIONS
const getPerformanceRatingVariant = (rating) => {
    const normalizedRating = rating ? rating.toLowerCase() : 'unknown'

    switch (normalizedRating) {
        case 'excellent':
            return 'default'
        case 'good':
            return 'secondary'
        case 'average':
            return 'outline'
        case 'needs improvement':
            return 'destructive'
        default:
            return 'secondary'
    }
}

const getPerformanceRatingIcon = (rating) => {
    const normalizedRating = rating ? rating.toLowerCase() : 'unknown'

    switch (normalizedRating) {
        case 'excellent':
            return Award
        case 'good':
            return TrendingUp
        case 'average':
            return Target
        case 'needs improvement':
            return AlertTriangle
        default:
            return Target
    }
}

const getCompletionRateColor = (rate) => {
    if (rate >= 80) return 'text-green-600'
    if (rate >= 60) return 'text-blue-600'
    if (rate >= 40) return 'text-yellow-600'
    if (rate >= 20) return 'text-orange-600'
    return 'text-red-600'
}

// Handle select changes
const handleUserChange = (value: string) => {
    filters.value.user_id = value === 'all' ? '' : value
}

const handleCourseChange = (value: string) => {
    filters.value.course_id = value === 'all' ? '' : value
}

// Apply filters with debounce
const applyFilters = debounce(() => {
    const filterParams = { ...filters.value }

    if (filterParams.date_from) {
        filterParams.date_from = filterParams.date_from.trim()
    }
    if (filterParams.date_to) {
        filterParams.date_to = filterParams.date_to.trim()
    }

    router.get(route('admin.reports.course-online.user-performance'), filterParams, {
        preserveState: true,
        replace: true
    })
}, 500)

// Watch for filter changes
watch(filters, () => {
    applyFilters()
}, { deep: true })

// Reset filters
const resetFilters = () => {
    filters.value = {
        user_id: '',
        course_id: '',
        date_from: '',
        date_to: '',
    }
    applyFilters()
}

// Export to CSV
const exportToCsv = () => {
    const queryParams = new URLSearchParams(filters.value).toString()
    window.location.href = route('admin.reports.course-online.export.user-performance') + '?' + queryParams
}

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return '—'
    const date = new Date(dateString)
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Modal functions
const showUserDetails = (user) => {
    selectedUser.value = user
    modalTitle.value = `Performance Details - ${user.name}`
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    selectedUser.value = null
    modalTitle.value = ''
}

// Close modal on escape key
const handleKeydown = (event) => {
    if (event.key === 'Escape' && showModal.value) {
        closeModal()
    }
}

// Add event listener for escape key
if (typeof window !== 'undefined') {
    window.addEventListener('keydown', handleKeydown)
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">User Performance Report</h1>
                    <p class="text-sm text-muted-foreground mt-1">Comprehensive reporting interface for tracking user performance and learning analytics</p>
                </div>
                <Button @click="exportToCsv" class="w-full sm:w-auto">
                    <Download class="mr-2 h-4 w-4" />
                    Export to CSV
                </Button>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Users class="h-8 w-8 text-blue-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Users</p>
                                <p class="text-2xl font-bold text-foreground">{{ users?.total || 0 }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Award class="h-8 w-8 text-green-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">High Performers</p>
                                <p class="text-2xl font-bold text-foreground">{{ users?.data?.filter(u => ['Excellent', 'Good'].includes(u.performance_rating)).length || 0 }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <CheckCircle class="h-8 w-8 text-purple-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Avg Completion</p>
                                <p class="text-2xl font-bold text-foreground">{{ Math.round((users?.data?.reduce((sum, u) => sum + u.completion_rate, 0) / (users?.data?.length || 1)) || 0) }}%</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Clock class="h-8 w-8 text-orange-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Avg Learning Hours</p>
                                <p class="text-2xl font-bold text-foreground">{{ Math.round((users?.data?.reduce((sum, u) => sum + u.total_learning_hours, 0) / (users?.data?.length || 1)) || 0) }}h</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <div class="flex items-center">
                        <Filter class="mr-2 h-5 w-5 text-primary" />
                        <div>
                            <CardTitle>Filter Users</CardTitle>
                            <CardDescription>Use the filters below to narrow down the user performance records</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="space-y-2">
                            <Label for="user_filter">User</Label>
                            <Select
                                :model-value="filters.user_id || 'all'"
                                @update:model-value="handleUserChange"
                            >
                                <SelectTrigger id="user_filter">
                                    <SelectValue placeholder="All Users" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Users</SelectItem>
                                    <SelectItem v-for="user in allUsers" :key="user.id" :value="user.id.toString()">
                                        {{ user.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="course_filter">Course</Label>
                            <Select
                                :model-value="filters.course_id || 'all'"
                                @update:model-value="handleCourseChange"
                            >
                                <SelectTrigger id="course_filter">
                                    <SelectValue placeholder="All Courses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Courses</SelectItem>
                                    <SelectItem v-for="course in courses" :key="course.id" :value="course.id.toString()">
                                        {{ course.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="date_from">Assignment From</Label>
                            <Input
                                id="date_from"
                                type="date"
                                v-model="filters.date_from"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="date_to">Assignment To</Label>
                            <Input
                                id="date_to"
                                type="date"
                                v-model="filters.date_to"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end mt-4">
                        <Button @click="resetFilters" variant="outline">
                            <RotateCcw class="mr-2 h-4 w-4" />
                            Reset Filters
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Users Performance Table -->
            <Card>
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>
                                    <div class="flex items-center">
                                        <User class="mr-2 h-4 w-4" />
                                        User
                                    </div>
                                </TableHead>
                                <TableHead class="hidden md:table-cell">
                                    <div class="flex items-center">
                                        <BookOpen class="mr-2 h-4 w-4" />
                                        Assignments
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center">
                                        <CheckCircle class="mr-2 h-4 w-4" />
                                        Completion
                                    </div>
                                </TableHead>
                                <TableHead class="hidden lg:table-cell">
                                    <div class="flex items-center">
                                        <BarChart3 class="mr-2 h-4 w-4" />
                                        Progress
                                    </div>
                                </TableHead>
                                <TableHead class="hidden sm:table-cell">
                                    <div class="flex items-center">
                                        <Clock class="mr-2 h-4 w-4" />
                                        Learning Time
                                    </div>
                                </TableHead>
                                <TableHead class="hidden lg:table-cell">
                                    <div class="flex items-center">
                                        <Brain class="mr-2 h-4 w-4" />
                                        Attention
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center">
                                        <Target class="mr-2 h-4 w-4" />
                                        Performance
                                    </div>
                                </TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="users.data.length === 0">
                                <TableCell colspan="8" class="text-center text-muted-foreground py-8">
                                    <div class="flex flex-col items-center">
                                        <Users class="h-12 w-12 text-muted-foreground mb-2" />
                                        No user performance records found
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-else v-for="(user, i) in users.data" :key="i" class="hover:bg-muted/50">
                                <!-- User Info -->
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="font-medium text-foreground">{{ user.name }}</div>
                                        <div class="text-sm text-muted-foreground hidden sm:block">{{ user.email }}</div>
                                        <div class="text-sm text-muted-foreground">{{ user.department }}</div>
                                    </div>
                                </TableCell>

                                <!-- Assignments (Hidden on mobile) -->
                                <TableCell class="hidden md:table-cell">
                                    <div class="space-y-1">
                                        <div class="text-sm font-medium text-foreground">{{ user.total_assignments }} total</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ user.completed_assignments }} completed, {{ user.in_progress_assignments }} in progress
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Completion Rate -->
                                <TableCell>
                                    <div class="space-y-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-semibold" :class="getCompletionRateColor(user.completion_rate)">
                                                {{ user.completion_rate }}%
                                            </span>
                                        </div>
                                        <div class="w-full bg-muted rounded-full h-2">
                                            <div
                                                class="h-2 rounded-full transition-all duration-300"
                                                :class="{
                                                    'bg-green-500': user.completion_rate >= 80,
                                                    'bg-blue-500': user.completion_rate >= 60 && user.completion_rate < 80,
                                                    'bg-yellow-500': user.completion_rate >= 40 && user.completion_rate < 60,
                                                    'bg-orange-500': user.completion_rate >= 20 && user.completion_rate < 40,
                                                    'bg-red-500': user.completion_rate < 20
                                                }"
                                                :style="{width: Math.max(user.completion_rate, 5) + '%'}"
                                            ></div>
                                        </div>
                                        <!-- Mobile assignments info -->
                                        <div class="text-xs text-muted-foreground md:hidden">
                                            {{ user.completed_assignments }}/{{ user.total_assignments }} assignments
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Average Progress (Hidden on mobile) -->
                                <TableCell class="hidden lg:table-cell">
                                    <div class="text-sm font-medium text-foreground">{{ user.avg_progress }}%</div>
                                    <div class="w-full bg-muted rounded-full h-1 mt-1">
                                        <div
                                            class="bg-blue-500 h-1 rounded-full transition-all duration-300"
                                            :style="{width: Math.max(user.avg_progress, 2) + '%'}"
                                        ></div>
                                    </div>
                                </TableCell>

                                <!-- Learning Time (Hidden on mobile) -->
                                <TableCell class="hidden sm:table-cell">
                                    <div class="space-y-1">
                                        <div class="text-sm font-medium text-foreground">{{ user.total_learning_hours }}h</div>
                                        <div class="text-xs text-muted-foreground">{{ user.total_sessions }} sessions</div>
                                    </div>
                                </TableCell>

                                <!-- Attention Score (Hidden on mobile/tablet) -->
                                <TableCell class="hidden lg:table-cell">
                                    <div class="space-y-1">
                                        <div class="text-sm font-medium text-foreground">{{ user.avg_attention_score }}%</div>
                                        <div v-if="user.suspicious_sessions > 0" class="text-xs text-red-600">
                                            {{ user.suspicious_sessions }} suspicious
                                        </div>
                                        <div v-else class="text-xs text-green-600">Clean record</div>
                                    </div>
                                </TableCell>

                                <!-- Performance Rating -->
                                <TableCell>
                                    <div class="space-y-1">
                                        <Badge :variant="getPerformanceRatingVariant(user.performance_rating)" class="flex items-center w-fit">
                                            <component :is="getPerformanceRatingIcon(user.performance_rating)" class="mr-1 h-3 w-3" />
                                            {{ user.performance_rating }}
                                        </Badge>
                                        <!-- Mobile learning time -->
                                        <div class="text-xs text-muted-foreground sm:hidden">
                                            {{ user.total_learning_hours }}h learning
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Actions -->
                                <TableCell>
                                    <Button
                                        @click="showUserDetails(user)"
                                        variant="link"
                                        size="sm"
                                        class="h-auto p-0"
                                    >
                                        View Details
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="users.data && users.data.length > 0 && users.last_page > 1" class="px-4 sm:px-6 py-3 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                        </div>
                        <div class="flex space-x-2">
                            <a v-for="link in users.links"
                               :key="link.label"
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
            </Card>
        </div>

        <!-- Enhanced Modal for User Details -->
        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-4xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center">
                        <Users class="mr-2 h-5 w-5 text-primary" />
                        {{ modalTitle }}
                    </DialogTitle>
                    <DialogDescription>
                        Comprehensive performance analytics and learning metrics
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedUser" class="space-y-6 mt-4">
                    <!-- User Overview -->
                    <div class="bg-muted/50 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-foreground mb-3">User Overview</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <span class="text-sm text-muted-foreground">Name:</span>
                                <div class="font-medium">{{ selectedUser.name }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-muted-foreground">Email:</span>
                                <div class="font-medium">{{ selectedUser.email }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-muted-foreground">Department:</span>
                                <div class="font-medium">{{ selectedUser.department }}</div>
                            </div>
                            <div>
                                <span class="text-sm text-muted-foreground">Performance:</span>
                                <Badge :variant="getPerformanceRatingVariant(selectedUser.performance_rating)" class="flex items-center w-fit">
                                    <component :is="getPerformanceRatingIcon(selectedUser.performance_rating)" class="mr-1 h-3 w-3" />
                                    {{ selectedUser.performance_rating }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-3xl font-bold text-blue-600 mb-2">{{ selectedUser.total_assignments }}</div>
                                <div class="text-sm text-muted-foreground">Total Assignments</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-3xl font-bold text-green-600 mb-2">{{ selectedUser.completion_rate }}%</div>
                                <div class="text-sm text-muted-foreground">Completion Rate</div>
                                <div class="w-full bg-muted rounded-full h-2 mt-2">
                                    <div
                                        class="bg-green-500 h-2 rounded-full transition-all duration-300"
                                        :style="{width: selectedUser.completion_rate + '%'}"
                                    ></div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-3xl font-bold text-purple-600 mb-2">{{ selectedUser.total_learning_hours }}h</div>
                                <div class="text-sm text-muted-foreground">Learning Hours</div>
                                <div class="text-xs text-muted-foreground mt-1">{{ selectedUser.total_sessions }} sessions</div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent class="p-6 text-center">
                                <div class="text-3xl font-bold text-yellow-600 mb-2">{{ selectedUser.avg_attention_score }}%</div>
                                <div class="text-sm text-muted-foreground">Avg Attention</div>
                                <div v-if="selectedUser.suspicious_sessions > 0" class="text-xs text-red-600 mt-1">
                                    {{ selectedUser.suspicious_sessions }} suspicious activities
                                </div>
                                <div v-else class="text-xs text-green-600 mt-1">Clean record</div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Assignment Breakdown -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Assignment Status Breakdown</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                                <div class="p-4 bg-green-50 dark:bg-green-950 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">{{ selectedUser.completed_assignments }}</div>
                                    <div class="text-sm text-muted-foreground">Completed</div>
                                </div>
                                <div class="p-4 bg-blue-50 dark:bg-blue-950 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">{{ selectedUser.in_progress_assignments }}</div>
                                    <div class="text-sm text-muted-foreground">In Progress</div>
                                </div>
                                <div class="p-4 bg-orange-50 dark:bg-orange-950 rounded-lg">
                                    <div class="text-2xl font-bold text-orange-600">{{ selectedUser.total_assignments - selectedUser.completed_assignments - selectedUser.in_progress_assignments }}</div>
                                    <div class="text-sm text-muted-foreground">Not Started</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Performance Insights -->
                    <Card v-if="selectedUser.suspicious_sessions > 0" class="border-yellow-200 bg-yellow-50 dark:bg-yellow-950">
                        <CardContent class="p-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <AlertTriangle class="h-5 w-5 text-yellow-500" />
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Performance Alert</h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <p>This user has {{ selectedUser.suspicious_sessions }} suspicious learning sessions that may require attention.</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
