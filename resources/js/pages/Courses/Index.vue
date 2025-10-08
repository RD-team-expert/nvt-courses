<!--
  Courses Index Page
  Display available courses in a grid layout with pagination and status information
  Updated to show scheduling data (days, duration, session times)
-->
<script setup lang="ts">
import { Link, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Pagination from '@/components/Pagination2.vue'
import { type BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
    BookOpen,
    Calendar,
    Clock,
    Users,
    ArrowRight,
    UserCheck,
    CheckCircle,
    ClipboardList,
    CalendarDays,
    Timer
} from 'lucide-vue-next'

// Log the entire courses prop to inspect the data
const props = defineProps({
    courses: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            meta: {},
        }),
    },
})

// Debug: Log the courses prop and links
console.log('Courses prop:', props.courses)
console.log('Pagination links:', props.courses?.links || 'No links provided')

// Format status for display
const formatStatus = (status) => {
    const statusMap = {
        pending: 'Pending',
        in_progress: 'In Progress',
        completed: 'Completed',
    }
    return statusMap[status] || status
}

// Get status variant
const getStatusVariant = (status) => {
    switch (status) {
        case 'in_progress': return 'default'
        case 'pending': return 'secondary'
        case 'completed': return 'outline'
        default: return 'outline'
    }
}

// Get user status variant
const getUserStatusVariant = (status) => {
    switch (status) {
        case 'enrolled': return 'default'
        case 'completed': return 'outline'
        default: return 'secondary'
    }
}

// Get assignment status variant
const getAssignmentStatusVariant = (status) => {
    switch (status) {
        case 'pending': return 'secondary'
        case 'accepted': return 'default'
        case 'declined': return 'destructive'
        case 'completed': return 'outline'
        default: return 'outline'
    }
}

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return '—'
    const date = new Date(dateString)
    return date.toLocaleDateString()
}

// Format sessions display
const formatSessions = (totalSessions, enrolledSessions = 0) => {
    if (totalSessions === 1) {
        return '1 Session'
    }
    return `${totalSessions} seats`
}

// NEW: Get course schedule summary from availabilities
const getCourseScheduleSummary = (course) => {
    if (!course.availabilities || course.availabilities.length === 0) {
        return null;
    }

    // Get unique days from all availabilities
    const allDays = new Set();
    let totalWeeks = 0;
    let commonSessionTime = null;
    let sessionDuration = null;

    course.availabilities.forEach(availability => {
        if (availability.selected_days && availability.selected_days.length > 0) {
            availability.selected_days.forEach(day => allDays.add(day));
        }
        if (availability.duration_weeks) {
            totalWeeks = Math.max(totalWeeks, availability.duration_weeks);
        }
        if (availability.formatted_session_time) {
            commonSessionTime = availability.formatted_session_time;
        }
        if (availability.formatted_session_duration) {
            sessionDuration = availability.formatted_session_duration;
        }
    });

    // Format unique days
    const dayNames = {
        'monday': 'Mon',
        'tuesday': 'Tue',
        'wednesday': 'Wed',
        'thursday': 'Thu',
        'friday': 'Fri',
        'saturday': 'Sat',
        'sunday': 'Sun'
    };

    const formattedDays = Array.from(allDays)
        .map(day => dayNames[day] || day)
        .join(', ');

    return {
        days: formattedDays || 'TBD',
        weeks: totalWeeks || null,
        sessionTime: commonSessionTime,
        sessionDuration: sessionDuration
    };
}

// Define breadcrumbs with proper typing
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Courses', href: route('courses.index') },
]
</script>

<template>
    <Head title="Courses" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-6 sm:py-8 max-w-7xl">
            <div class="flex flex-col space-y-6">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-foreground tracking-tight">Available Courses</h1>
                        <p v-if="courses.data && courses.data.length > 0" class="text-sm text-muted-foreground mt-1">
                            Showing {{ courses.meta?.from || courses.from }} to {{ courses.meta?.to || courses.to }} of {{ courses.meta?.total || courses.total }} courses
                        </p>
                    </div>
                </div>

                <!-- Courses Grid -->
                <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Card
                        v-for="course in courses.data"
                        :key="course.id"
                        class="group hover:shadow-lg transition-all duration-200 hover:-translate-y-1 overflow-hidden"
                    >
                        <!-- Course Image -->
                        <div class="relative bg-muted overflow-hidden aspect-video">
                            <img
                                v-if="course.image_path"
                                :src="`/storage/${course.image_path}`"
                                :alt="course.name"
                                class="absolute inset-0 w-full h-full object-fill transition-transform duration-500 group-hover:scale-105"
                                loading="lazy"
                            />
                            <div
                                v-else
                                class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-muted to-muted/80"
                            >
                                <BookOpen class="w-12 h-12 text-muted-foreground" />
                            </div>

                            <!-- Course Status Badge -->
                            <div class="absolute top-3 right-3">
                                <Badge :variant="getStatusVariant(course.status)" class="backdrop-blur-sm bg-blue-50">
                                    {{ formatStatus(course.status) }}
                                </Badge>
                            </div>

                            <!-- Privacy Badge -->
                            <div class="absolute top-3 left-3">
                                <Badge
                                    :variant="course.privacy === 'public' ? 'default' : 'secondary'"
                                    class="backdrop-blur-sm bg-blue-50 text-black"
                                >
                                    {{ course.privacy === 'public' ? '🌍 Public' : '🔒 Private' }}
                                </Badge>
                            </div>
                        </div>

                        <CardContent class="p-4 sm:p-5 flex flex-col flex-grow">
                            <!-- Course Title -->
                            <h2 class="text-lg font-semibold mb-3 text-foreground line-clamp-2 leading-snug">
                                {{ course.name }}
                            </h2>

                            <!-- Course Info -->
                            <div class="space-y-2 mb-4 flex-grow text-sm text-muted-foreground">
                                <!-- Course Dates -->
                                <div class="flex items-center">
                                    <Calendar class="w-4 h-4 mr-2 text-muted-foreground" />
                                    <span class="font-medium mr-2 min-w-[3rem]">Start:</span>
                                    <span>{{ formatDate(course.start_date) }}</span>
                                </div>
                                <div class="flex items-center">
                                    <Calendar class="w-4 h-4 mr-2 text-muted-foreground" />
                                    <span class="font-medium mr-2 min-w-[3rem]">End:</span>
                                    <span>{{ formatDate(course.end_date) }}</span>
                                </div>

                                <!-- NEW: Course Schedule Information -->
                                <template v-if="getCourseScheduleSummary(course)">
                                    <div class="border-t pt-2 mt-3">
                                        <h4 class="text-xs font-semibold text-foreground mb-2">Schedule</h4>

                                        <!-- Days of Week -->
                                        <div v-if="getCourseScheduleSummary(course).days !== 'TBD'" class="flex items-center mb-1">
                                            <CalendarDays class="w-4 h-4 mr-2 text-muted-foreground" />
                                            <span class="font-medium mr-2 min-w-[3rem]">Days:</span>
                                            <Badge variant="outline" class="text-xs">
                                                {{ getCourseScheduleSummary(course).days }}
                                            </Badge>
                                        </div>

                                        <!-- Duration in Weeks -->
                                        <div v-if="getCourseScheduleSummary(course).weeks" class="flex items-center mb-1">
                                            <Clock class="w-4 h-4 mr-2 text-muted-foreground" />
                                            <span class="font-medium mr-2 min-w-[3rem]">Duration:</span>
                                            <span>{{ getCourseScheduleSummary(course).weeks }} weeks</span>
                                        </div>

                                        <!-- Session Time -->
                                        <div v-if="getCourseScheduleSummary(course).sessionTime" class="flex items-center mb-1">
                                            <Timer class="w-4 h-4 mr-2 text-muted-foreground" />
                                            <span class="font-medium mr-2 min-w-[3rem]">Time:</span>
                                            <span>{{ getCourseScheduleSummary(course).sessionTime }}</span>
                                        </div>

                                        <!-- Session Duration -->
                                        <div v-if="getCourseScheduleSummary(course).sessionDuration" class="flex items-center">
                                            <Clock class="w-4 h-4 mr-2 text-muted-foreground" />
                                            <span class="font-medium mr-2 min-w-[3rem]">Length:</span>
                                            <span>{{ getCourseScheduleSummary(course).sessionDuration }}</span>
                                        </div>
                                    </div>
                                </template>

                                <!-- Course Level -->
                                <div v-if="course.level" class="flex items-center pt-2">
                                    <Badge variant="outline" class="text-xs">{{ course.level }}</Badge>
                                </div>

                                <!-- Total Course Duration -->
                                <div v-if="course.duration" class="flex items-center">
                                    <Clock class="w-4 h-4 mr-2 text-muted-foreground" />
                                    <span class="font-medium mr-2 min-w-[3rem]">Total:</span>
                                    <span>{{ course.duration }} hours</span>
                                </div>

                                <!-- Availability -->
                                <div v-if="course.total_capacity" class="flex items-center">
                                    <Users class="w-4 h-4 mr-2 text-muted-foreground" />
                                    <span class="font-medium mr-2 min-w-[4rem]">Seats:</span>
                                    <span>{{ formatSessions(course.total_capacity) }}</span>
                                </div>
                            </div>

                            <!-- User Enrollment Status -->
                            <div v-if="course.user_status" class="mb-4">
                                <Badge :variant="getUserStatusVariant(course.user_status)" class="w-fit">
                                    <UserCheck v-if="course.user_status === 'enrolled'" class="mr-1 h-3 w-3" />
                                    <CheckCircle v-else-if="course.user_status === 'completed'" class="mr-1 h-3 w-3" />
                                    {{ course.user_status === 'enrolled' ? 'Enrolled' : 'Completed' }}
                                </Badge>
                            </div>

                            <!-- Assignment Status -->
                            <div v-if="course.user_assignment" class="mb-4">
                                <Badge :variant="getAssignmentStatusVariant(course.user_assignment.status)" class="w-fit">
                                    <ClipboardList class="mr-1 h-3 w-3" />
                                    Assigned by {{ course.user_assignment.assigned_by }}
                                </Badge>
                            </div>

                            <!-- View Details Button -->
                            <Button :as="Link" :href="`/courses/${course.id}`" class="w-full mt-auto group">
                                View Details
                                <ArrowRight class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State -->
                <div v-if="!courses.data || courses.data.length === 0" class="text-center py-16 px-4">
                    <BookOpen class="h-16 w-16 mx-auto mb-4 text-muted-foreground" />
                    <p class="text-lg text-muted-foreground font-medium">No courses available at the moment</p>
                    <p class="text-muted-foreground mt-2">Check back later for new courses</p>
                </div>

                <!-- Pagination Component -->
                <div v-if="courses.data && courses.data.length > 0 && courses.links && courses.links.length > 0" class="flex justify-center items-center mt-8">
                    <Pagination :links="courses.links" @rendered="console.log('Pagination component rendered with links:', courses.links)" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.aspect-video {
    aspect-ratio: 16 / 9;
}

@media (max-width: 640px) {
    .grid {
        grid-template-columns: 1fr;
    }
}
</style>
