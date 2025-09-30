<!--
  Course Registrations Report Page
  Comprehensive reporting interface for tracking course registration statistics and user enrollment data
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
    User,
    GraduationCap,
    Calendar,
    Star,
    CheckCircle,
    Clock,
    AlertCircle
} from 'lucide-vue-next'

const props = defineProps({
    registrations: Array,
    courses: Array,
    filters: Object
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Reports & Analytics', href: route('admin.reports.index') },
    { name: 'Course Registrations', href: route('admin.reports.course-registrations') }
]

// Filter state
const filters = ref({
    course_id: props.filters?.course_id || '',
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || ''
})

// Handle select changes
const handleCourseChange = (value: string) => {
    filters.value.course_id = value === 'all' ? '' : value
}

const handleStatusChange = (value: string) => {
    filters.value.status = value === 'all' ? '' : value
}

// Apply filters with debounce
const applyFilters = debounce(() => {
    // Make a copy of the filters to avoid reactivity issues
    const filterParams = { ...filters.value }

    // Format dates if needed
    if (filterParams.date_from) {
        filterParams.date_from = filterParams.date_from.trim()
    }
    if (filterParams.date_to) {
        filterParams.date_to = filterParams.date_to.trim()
    }

    router.get(route('admin.reports.course-registrations'), filterParams, {
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
        course_id: '',
        status: '',
        date_from: '',
        date_to: ''
    }
    applyFilters()
}

// Export to CSV
const exportToCsv = () => {
    const queryParams = new URLSearchParams(filters.value).toString()
    window.location.href = route('admin.reports.export.course-registrations') + '?' + queryParams
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

// Get status badge variant
const getStatusVariant = (status) => {
    switch (status) {
        case 'pending':
            return 'secondary'
        case 'in_progress':
            return 'default'
        case 'completed':
            return 'outline'
        default:
            return 'secondary'
    }
}

// Get status icon
const getStatusIcon = (status) => {
    switch (status) {
        case 'pending':
            return AlertCircle
        case 'in_progress':
            return Clock
        case 'completed':
            return CheckCircle
        default:
            return AlertCircle
    }
}

// Format status label
const formatStatus = (status) => {
    return status.replace('_', ' ').charAt(0).toUpperCase() + status.replace('_', ' ').slice(1)
}

// Generate stars for rating display
const generateStars = (rating: number) => {
    const stars = []
    for (let i = 1; i <= 5; i++) {
        stars.push(i <= rating ? 'filled' : 'empty')
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
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Course Registrations Report</h1>
                    <p class="text-sm text-muted-foreground mt-1">Comprehensive reporting interface for tracking course registration statistics and user enrollment data</p>
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
                            <CardTitle>Filter Registrations</CardTitle>
                            <CardDescription>Use the filters below to narrow down the registration records</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
                            <Label for="status_filter">Status</Label>
                            <Select
                                :model-value="filters.status || 'all'"
                                @update:model-value="handleStatusChange"
                            >
                                <SelectTrigger id="status_filter">
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="in_progress">In Progress</SelectItem>
                                    <SelectItem value="completed">Completed</SelectItem>
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

            <!-- Registrations Table -->
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
                                <TableHead>Status</TableHead>
                                <TableHead>
                                    <div class="flex items-center">
                                        <Calendar class="mr-2 h-4 w-4" />
                                        Registered At
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center">
                                        <CheckCircle class="mr-2 h-4 w-4" />
                                        Completed At
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center">
                                        <Star class="mr-2 h-4 w-4" />
                                        Rating
                                    </div>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="registrations.length === 0">
                                <TableCell colspan="6" class="text-center text-muted-foreground py-8">
                                    <div class="flex flex-col items-center">
                                        <GraduationCap class="h-12 w-12 text-muted-foreground mb-2" />
                                        No registration records found
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-else v-for="(record, i) in registrations" :key="i" class="hover:bg-muted/50">
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="font-medium text-foreground">{{ record.user_name }}</div>
                                        <div class="text-sm text-muted-foreground">{{ record.user_email }}</div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">{{ record.course_name }}</Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getStatusVariant(record.status)" class="flex items-center w-fit">
                                        <component :is="getStatusIcon(record.status)" class="mr-1 h-3 w-3" />
                                        {{ formatStatus(record.status) }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm text-foreground">{{ formatDate(record.registered_at) }}</div>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm text-foreground">{{ record.completed_at ? formatDate(record.completed_at) : '—' }}</div>
                                </TableCell>
                                <TableCell>
                                    <div v-if="record.rating" class="flex items-center space-x-2">
                                        <span class="text-sm font-medium">{{ record.rating }}/5</span>
                                        <div class="flex">
                                            <Star
                                                v-for="(star, index) in generateStars(record.rating)"
                                                :key="index"
                                                class="h-4 w-4"
                                                :class="star === 'filled' ? 'text-yellow-400 fill-yellow-400' : 'text-muted-foreground'"
                                            />
                                        </div>
                                    </div>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Card>
        </div>
    </AdminLayout>
</template>
