<!--
  Attendance Records Report Page
  Comprehensive reporting interface for tracking and analyzing attendance records
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
    Download,
    Filter,
    RotateCcw,
    Clock,
    User,
    GraduationCap,
    Star,
    StarHalf,
    MessageSquare
} from 'lucide-vue-next'

const props = defineProps({
    attendance: Array,
    users: Array,
    courses: Array, // Add courses prop
    filters: Object
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Reports & Analytics', href: route('admin.reports.index') },
    { name: 'Attendance Records', href: route('admin.reports.attendance') }
]

// Filter state
const filters = ref({
    user_id: props.filters?.user_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    course_id: props.filters?.course_id || '' // Add course_id filter
})

// Handle select changes
const handleUserChange = (value: string) => {
    filters.value.user_id = value === 'all' ? '' : value
}

const handleCourseChange = (value: string) => {
    filters.value.course_id = value === 'all' ? '' : value
}

// Apply filters with debounce
const applyFilters = debounce(() => {
    router.get(route('admin.reports.attendance'), filters.value, {
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
        date_from: '',
        date_to: '',
        course_id: '' // Reset course_id filter
    }
    applyFilters()
}

// Export to CSV
const exportToCsv = () => {
    const queryParams = new URLSearchParams(filters.value).toString()
    window.location.href = route('admin.reports.export.attendance') + '?' + queryParams
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

// Format duration in minutes to human-readable format
const formatHumanDuration = (minutes) => {
    if (!minutes || isNaN(minutes) || minutes < 0) return '—'

    // Round to nearest integer to avoid decimal values
    minutes = Math.round(minutes)

    const hours = Math.floor(minutes / 60)
    const remainingMinutes = minutes % 60

    if (hours > 0) {
        return `${hours} ${hours === 1 ? 'hour' : 'hours'}${remainingMinutes > 0 ? ` ${remainingMinutes} ${remainingMinutes === 1 ? 'minute' : 'minutes'}` : ''}`
    }
    return `${remainingMinutes} ${remainingMinutes === 1 ? 'minute' : 'minutes'}`
}

// Generate stars for rating display
const generateStars = (rating: number) => {
    const stars = []
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars.push('full')
        } else if (i - 0.5 <= rating) {
            stars.push('half')
        } else {
            stars.push('empty')
        }
    }
    return stars
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Attendance Records Report</h1>
                    <p class="text-sm text-muted-foreground mt-1">Comprehensive reporting interface for tracking and analyzing attendance records</p>
                </div>
                <Button @click="exportToCsv" class="w-full sm:w-auto">
                    <Download class="mr-2 h-4 w-4" />
                    Export to CSV
                </Button>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <div class="flex items-center">
                        <Filter class="mr-2 h-5 w-5 text-primary" />
                        <div>
                            <CardTitle>Filter Attendance Records</CardTitle>
                            <CardDescription>Use the filters below to narrow down the attendance records</CardDescription>
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
                                    <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
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
                                    <SelectItem value="general">General Attendance</SelectItem>
                                    <SelectItem v-for="course in courses" :key="course.id" :value="course.id.toString()">
                                        {{ course.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="date_from">Date From</Label>
                            <Input
                                id="date_from"
                                type="date"
                                v-model="filters.date_from"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="date_to">Date To</Label>
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

            <!-- Attendance Records Table -->
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
                                <TableHead>
                                    <div class="flex items-center">
                                        <GraduationCap class="mr-2 h-4 w-4" />
                                        Course
                                    </div>
                                </TableHead>
                                <TableHead>Clock In</TableHead>
                                <TableHead class="hidden sm:table-cell">Clock Out</TableHead>
                                <TableHead>
                                    <div class="flex items-center">
                                        <Clock class="mr-2 h-4 w-4" />
                                        Duration
                                    </div>
                                </TableHead>
                                <TableHead class="hidden md:table-cell">
                                    <div class="flex items-center">
                                        <Star class="mr-2 h-4 w-4" />
                                        Rating
                                    </div>
                                </TableHead>
                                <TableHead class="hidden lg:table-cell">
                                    <div class="flex items-center">
                                        <MessageSquare class="mr-2 h-4 w-4" />
                                        Comment
                                    </div>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="attendance.length === 0">
                                <TableCell colspan="7" class="text-center text-muted-foreground py-8">
                                    <div class="flex flex-col items-center">
                                        <Clock class="h-12 w-12 text-muted-foreground mb-2" />
                                        No attendance records found
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-else v-for="(record, i) in attendance" :key="i" class="hover:bg-muted/50">
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="font-medium text-foreground">{{ record.user_name }}</div>
                                        <div class="text-xs text-muted-foreground hidden sm:block">{{ record.user_email }}</div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">
                                        {{ record.course_name || 'General Attendance' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm text-foreground">{{ formatDate(record.clock_in) }}</div>
                                </TableCell>
                                <TableCell class="hidden sm:table-cell">
                                    <div class="text-sm text-foreground">{{ record.clock_out ? formatDate(record.clock_out) : '—' }}</div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary" class="font-mono">
                                        {{ formatHumanDuration(record.duration_in_minutes) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="hidden md:table-cell">
                                    <div v-if="record.rating" class="flex items-center space-x-2">
                                        <span class="text-sm font-medium">{{ record.rating }}/5</span>
                                        <div class="flex">
                                            <template v-for="(star, index) in generateStars(record.rating)" :key="index">
                                                <Star v-if="star === 'full'" class="h-4 w-4 fill-yellow-400 text-yellow-400" />
                                                <StarHalf v-else-if="star === 'half'" class="h-4 w-4 fill-yellow-400 text-yellow-400" />
                                                <Star v-else class="h-4 w-4 text-muted-foreground" />
                                            </template>
                                        </div>
                                    </div>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="hidden lg:table-cell">
                                    <div class="max-w-xs">
                                        <div v-if="record.comment" class="text-sm text-muted-foreground truncate">
                                            {{ record.comment }}
                                        </div>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Card>
        </div>
    </AdminLayout>
</template>
