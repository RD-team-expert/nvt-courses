<!-- Dashboard Page -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed, onMounted } from 'vue'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Alert, AlertDescription } from '@/components/ui/alert'

// Icons
import {
    BarChart,
    BookOpenCheck,
    Clock,
    Users,
    ExternalLink,
    ArrowRight,
    Calendar,
    Activity
} from 'lucide-vue-next'

// Get current user from page props
const page = usePage()
const user = computed(() => page.props.auth?.user)
const isAdmin = computed(() => user.value?.is_admin === true || user.value?.role === 'admin')

// Get dashboard data from props
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalCourses: 0,
            totalUsers: 0,
            activeSessions: 0,
            completionRate: 0,
            pendingCourses: 0,
            completedCourses: 0,
            userCourses: 0,
            userAttendanceRate: 0,
            userCompletedCourses: 0,
            userTotalCourses: 0,
            upcomingSessions: 0
        })
    },
    recentActivity: {
        type: Array,
        default: () => []
    },
    userCourses: {
        type: Array,
        default: () => []
    },
    userAttendance: {
        type: Array,
        default: () => []
    }
})

const breadcrumbs: BreadcrumbItem[] = [
    {
        name: 'Dashboard',
        href: '/dashboard',
    },
]

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString()
}

// Format time ago for activity feed
const timeAgo = (dateString) => {
    if (!dateString) return ''

    const date = new Date(dateString)
    const now = new Date()
    const diffInSeconds = Math.floor((now - date) / 1000)

    if (diffInSeconds < 60) {
        return 'just now'
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60)
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600)
        return `${hours} hour${hours > 1 ? 's' : ''} ago`
    } else {
        const days = Math.floor(diffInSeconds / 86400)
        return `${days} day${days > 1 ? 's' : ''} ago`
    }
}

// Get status badge variant
const getStatusVariant = (status: string) => {
    if (status === 'completed') return 'default'
    if (status === 'in_progress' || status === 'enrolled') return 'secondary'
    if (status === 'pending') return 'secondary'
    if (status === 'present') return 'default'
    return 'outline'
}

// Get activity action variant
const getActivityVariant = (action: string) => {
    if (action === 'create') return 'secondary'
    if (action === 'update') return 'default'
    if (action === 'delete') return 'destructive'
    if (action === 'enroll') return 'default'
    if (action === 'login') return 'secondary'
    return 'outline'
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
            <!-- Welcome Section -->
            <Card>
                <CardContent class="p-6">
                    <h1 class="text-xl sm:text-2xl font-bold mb-2">
                        Welcome back, {{ user?.name }}!
                    </h1>
                    <CardDescription class="text-base">
                        {{ isAdmin ? 'Manage your courses and users from your admin dashboard.' : 'Track your courses and attendance from your dashboard.' }}
                    </CardDescription>
                </CardContent>
            </Card>

            <!-- Admin Dashboard -->
            <div v-if="isAdmin" class="space-y-6">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                    <BookOpenCheck class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground font-medium">Total Courses</p>
                                    <p class="text-2xl font-bold">{{ stats.totalCourses || 0 }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                    <Users class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground font-medium">Total Users</p>
                                    <p class="text-2xl font-bold">{{ stats.totalUsers || 0 }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                    <Clock class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground font-medium">Active Sessions</p>
                                    <p class="text-2xl font-bold">{{ stats.activeSessions || 0 }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
                                    <BarChart class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground font-medium">Completion Rate</p>
                                    <p class="text-2xl font-bold">{{ stats.completionRate || 0 }}%</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Recent Activity -->
                <Card>
                    <CardHeader>
                        <div class="flex justify-between items-center">
                            <CardTitle class="flex items-center gap-2">
                                <Activity class="h-5 w-5" />
                                Recent Activity
                            </CardTitle>
                            <Button asChild variant="ghost" size="sm">
                                <Link href="/activities">
                                    <ExternalLink class="h-4 w-4 mr-1" />
                                    View all
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div v-if="recentActivity.length === 0" class="text-center py-8">
                                <Clock class="h-12 w-12 mx-auto text-muted-foreground mb-2" />
                                <CardTitle class="text-base mb-2">No recent activity</CardTitle>
                                <CardDescription>Activity will appear here as users interact with the system</CardDescription>
                            </div>
                            <Card
                                v-for="(activity, index) in recentActivity"
                                :key="activity.id || index"
                                class="hover:bg-accent/50 transition-colors"
                            >
                                <CardContent class="flex items-start p-3">
                                    <Avatar class="h-10 w-10 mr-3 shrink-0">
                                        <AvatarImage v-if="activity.user?.avatar" :src="activity.user.avatar" :alt="activity.user?.name" />
                                        <AvatarFallback>
                                            {{ activity.user?.name ? activity.user.name.charAt(0).toUpperCase() : 'S' }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium break-words">{{ activity.description }}</p>
                                        <div class="flex items-center mt-1 gap-2">
                                            <Badge
                                                v-if="activity.action"
                                                :variant="getActivityVariant(activity.action)"
                                                class="text-xs"
                                            >
                                                {{ activity.action }}
                                            </Badge>
                                            <span class="text-xs text-muted-foreground">{{ timeAgo(activity.created_at) }}</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- User Dashboard -->
            <div v-else class="space-y-6">
                <!-- User Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                    <BookOpenCheck class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground font-medium">My Courses</p>
                                    <p class="text-2xl font-bold">{{ stats.userCourses || 0 }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                    <Clock class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground font-medium">Attendance Rate</p>
                                    <p class="text-2xl font-bold">{{ stats.userAttendanceRate || 0 }}%</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
                                    <BarChart class="h-6 w-6" />
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground font-medium">Completed</p>
                                    <p class="text-2xl font-bold">
                                        {{ stats.userCompletedCourses || 0 }}/{{ stats.userTotalCourses || 0 }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- My Courses -->
                <Card>
                    <CardHeader>
                        <div class="flex justify-between items-center">
                            <CardTitle class="flex items-center gap-2">
                                <BookOpenCheck class="h-5 w-5" />
                                My Courses
                            </CardTitle>
                            <Button asChild variant="ghost" size="sm">
                                <Link href="/courses">
                                    <ExternalLink class="h-4 w-4 mr-1" />
                                    View all
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-if="userCourses.length === 0" class="col-span-full text-center py-8">
                                <BookOpenCheck class="h-12 w-12 mx-auto text-muted-foreground mb-2" />
                                <CardTitle class="text-base mb-2">No courses yet</CardTitle>
                                <CardDescription>You are not enrolled in any courses yet</CardDescription>
                            </div>
                            <Card
                                v-for="(course, index) in userCourses"
                                :key="course.id || index"
                                class="overflow-hidden hover:shadow-md transition-shadow"
                            >
                                <div class="h-32 bg-muted">
                                    <img
                                        v-if="course.image_path"
                                        :src="`/storage/${course.image_path}`"
                                        :alt="course.name"
                                        class="w-full h-full object-contain"
                                        @error="$event.target.src = '/images/course-placeholder.jpg'"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <BookOpenCheck class="h-10 w-10 text-muted-foreground" />
                                    </div>
                                </div>
                                <CardContent class="p-3">
                                    <CardTitle class="text-base mb-3">{{ course.name || 'Course Name' }}</CardTitle>
                                    <div class="flex justify-between items-center">
                                        <Badge :variant="getStatusVariant(course.status)">
                                            {{ course.status ? (course.status.charAt(0).toUpperCase() + course.status.slice(1)).replace('_', ' ') : 'Enrolled' }}
                                        </Badge>
                                        <Button asChild variant="ghost" size="sm">
                                            <Link :href="`/courses/${course.id}`">
                                                Continue
                                                <ArrowRight class="h-3 w-3 ml-1" />
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Attendance -->
                <Card>
                    <CardHeader>
                        <div class="flex justify-between items-center">
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                Recent Attendance
                            </CardTitle>
                            <Button asChild variant="ghost" size="sm">
                                <Link href="/attendance">
                                    <ExternalLink class="h-4 w-4 mr-1" />
                                    View all
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Course</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="userAttendance.length === 0">
                                        <TableCell colspan="3" class="text-center py-8">
                                            <Clock class="h-12 w-12 mx-auto text-muted-foreground mb-2" />
                                            <CardTitle class="text-base mb-2">No attendance records</CardTitle>
                                            <CardDescription>Your attendance history will appear here</CardDescription>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-for="(record, index) in userAttendance" :key="record.id || index">
                                        <TableCell>{{ formatDate(record.date) }}</TableCell>
                                        <TableCell>{{ record.course_name || 'General Attendance' }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="getStatusVariant(record.status)">
                                                {{ record.status ? (record.status.charAt(0).toUpperCase() + record.status.slice(1)).replace('_', ' ') : 'Present' }}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Upcoming Sessions -->
                <Alert v-if="stats.upcomingSessions > 0">
                    <Clock class="h-4 w-4" />
                    <AlertDescription>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium mb-1">Upcoming Sessions</p>
                                <p class="text-sm">
                                    You have {{ stats.upcomingSessions }} upcoming session{{ stats.upcomingSessions > 1 ? 's' : '' }}.
                                    Check your course schedule for details.
                                </p>
                            </div>
                            <div class="ml-4">
                                <Badge variant="secondary">{{ stats.upcomingSessions }} upcoming</Badge>
                            </div>
                        </div>
                        <Button asChild variant="ghost" size="sm" class="mt-2 p-0 h-auto">
                            <Link href="/courses" class="inline-flex items-center text-sm font-medium">
                                View my courses
                                <ArrowRight class="w-3 h-3 ml-1" />
                            </Link>
                        </Button>
                    </AlertDescription>
                </Alert>
            </div>
        </div>
    </AppLayout>
</template>
