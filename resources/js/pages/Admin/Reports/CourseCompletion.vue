<!--
  Course Completion Report Page
  Comprehensive reporting interface for tracking course completion status and statistics
-->
<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { debounce } from 'lodash'
import Pagination from '@/components/Pagination.vue'
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
    GraduationCap,
    Calendar,
    Star,
    MessageSquare,
    FileText,
    CheckCircle,
    Clock,
    AlertCircle,
    XCircle,
    Pause
} from 'lucide-vue-next'

const props = defineProps({
    completions: Object,
    courses: Array,
    filters: Object,
    debug: Object
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Reports & Analytics', href: route('admin.reports.index') },
    { name: 'Course Completion', href: route('admin.reports.course-completion') }
]

// Filter state
const filters = ref({
    course_id: props.filters?.course_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || ''
})

// Modal state for feedback and comments
const showModal = ref(false)
const modalTitle = ref('')
const modalContent = ref('')

// Handle select changes
const handleCourseChange = (value: string) => {
    filters.value.course_id = value === 'all' ? '' : value
}

// ✅ STATUS HELPER FUNCTIONS
const getStatusBadgeVariant = (status) => {
    // Convert to lowercase for consistent comparison
    const normalizedStatus = status ? status.toLowerCase() : 'unknown'

    switch (normalizedStatus) {
        case 'completed':
            return 'default'
        case 'enrolled':
            return 'secondary'
        case 'in_progress':
        case 'in-progress':
        case 'active':
            return 'outline'
        case 'pending':
            return 'secondary'
        case 'cancelled':
        case 'canceled':
            return 'destructive'
        case 'on_hold':
        case 'on-hold':
            return 'outline'
        default:
            return 'secondary'
    }
}

const getStatusIcon = (status) => {
    const normalizedStatus = status ? status.toLowerCase() : 'unknown'

    switch (normalizedStatus) {
        case 'completed':
            return CheckCircle
        case 'enrolled':
            return User
        case 'in_progress':
        case 'in-progress':
        case 'active':
            return Clock
        case 'pending':
            return AlertCircle
        case 'cancelled':
        case 'canceled':
            return XCircle
        case 'on_hold':
        case 'on-hold':
            return Pause
        default:
            return AlertCircle
    }
}

const getStatusLabel = (status) => {
    if (!status) return 'Unknown'

    // Convert to proper case for display
    const normalizedStatus = status.toLowerCase()

    switch (normalizedStatus) {
        case 'completed':
            return 'Completed'
        case 'enrolled':
            return 'Enrolled'
        case 'in_progress':
        case 'in-progress':
            return 'In Progress'
        case 'active':
            return 'Active'
        case 'pending':
            return 'Pending'
        case 'cancelled':
        case 'canceled':
            return 'Cancelled'
        case 'on_hold':
        case 'on-hold':
            return 'On Hold'
        default:
            // Capitalize first letter of unknown statuses
            return status.charAt(0).toUpperCase() + status.slice(1).toLowerCase()
    }
}

// Apply filters with debounce
const applyFilters = debounce(() => {
    router.get(route('admin.reports.course-completion'), filters.value, {
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
        date_from: '',
        date_to: ''
    }
    applyFilters()
}

// Export to CSV
const exportToCsv = () => {
    const queryParams = new URLSearchParams(filters.value).toString()
    window.location.href = route('admin.reports.export.course-completion') + '?' + queryParams
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

// Handle pagination
const handlePageChange = (page) => {
    router.get(route('admin.reports.course-completion'), {
        ...filters.value,
        page: page
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
        onSuccess: () => {
            document.querySelector('.bg-white.rounded-lg.shadow')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }
    })
}

// Modal functions
const showFeedback = (feedback, userName) => {
    modalTitle.value = `Feedback from ${userName}`
    modalContent.value = feedback || 'No feedback provided'
    showModal.value = true
}

const showComment = (comment, userName) => {
    modalTitle.value = `Comment from ${userName}`
    modalContent.value = comment || 'No comment provided'
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    modalTitle.value = ''
    modalContent.value = ''
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
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Course Completion Report</h1>
                    <p class="text-sm text-muted-foreground mt-1">Comprehensive reporting interface for tracking course completion status and statistics</p>
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
                            <CardTitle>Filter Completions</CardTitle>
                            <CardDescription>Use the filters below to narrow down the completion records</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                            <Label for="date_from">Completed From</Label>
                            <Input
                                id="date_from"
                                type="date"
                                v-model="filters.date_from"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="date_to">Completed To</Label>
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

            <!-- Completions Table -->
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
                                <TableHead class="hidden sm:table-cell">
                                    <div class="flex items-center">
                                        <GraduationCap class="mr-2 h-4 w-4" />
                                        Course
                                    </div>
                                </TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="hidden md:table-cell">
                                    <div class="flex items-center">
                                        <Calendar class="mr-2 h-4 w-4" />
                                        Registered
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center">
                                        <CheckCircle class="mr-2 h-4 w-4" />
                                        Completed
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
                                        Feedback
                                    </div>
                                </TableHead>
                                <TableHead class="hidden xl:table-cell">
                                    <div class="flex items-center">
                                        <FileText class="mr-2 h-4 w-4" />
                                        Comment
                                    </div>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="completions.data.length === 0">
                                <TableCell colspan="8" class="text-center text-muted-foreground py-8">
                                    <div class="flex flex-col items-center">
                                        <CheckCircle class="h-12 w-12 text-muted-foreground mb-2" />
                                        No completion records found
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-else v-for="(record, i) in completions.data" :key="i" class="hover:bg-muted/50">
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="font-medium text-foreground">{{ record.user_name }}</div>
                                        <div class="text-xs text-muted-foreground hidden sm:block">{{ record.user_email }}</div>
                                        <div class="text-xs text-muted-foreground sm:hidden">{{ record.course_name }}</div>
                                    </div>
                                </TableCell>
                                <TableCell class="hidden sm:table-cell">
                                    <Badge variant="outline">{{ record.course_name }}</Badge>
                                </TableCell>

                                <!-- ✅ STATUS CELL -->
                                <TableCell>
                                    <Badge :variant="getStatusBadgeVariant(record.course_status)" class="flex items-center w-fit">
                                        <component :is="getStatusIcon(record.course_status)" class="mr-1 h-3 w-3" />
                                        {{ getStatusLabel(record.course_status) }}
                                    </Badge>
                                </TableCell>

                                <TableCell class="hidden md:table-cell">
                                    <div class="text-sm text-foreground">{{ formatDate(record.registered_at) }}</div>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm text-foreground">{{ formatDate(record.completed_at) }}</div>
                                </TableCell>
                                <TableCell class="hidden md:table-cell">
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
                                <TableCell class="hidden lg:table-cell">
                                    <div class="max-w-xs">
                                        <Button
                                            v-if="record.feedback"
                                            @click="showFeedback(record.feedback, record.user_name)"
                                            variant="link"
                                            size="sm"
                                            class="h-auto p-0 text-left justify-start"
                                            :title="'Click to view full feedback from ' + record.user_name"
                                        >
                                            {{ record.feedback.length > 30 ? record.feedback.substring(0, 30) + '...' : record.feedback }}
                                        </Button>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </div>
                                </TableCell>
                                <TableCell class="hidden xl:table-cell">
                                    <div class="max-w-xs">
                                        <Button
                                            v-if="record.comment"
                                            @click="showComment(record.comment, record.user_name)"
                                            variant="link"
                                            size="sm"
                                            class="h-auto p-0 text-left justify-start"
                                            :title="'Click to view full comment from ' + record.user_name"
                                        >
                                            {{ record.comment.length > 30 ? record.comment.substring(0, 30) + '...' : record.comment }}
                                        </Button>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div class="px-4 sm:px-6 py-3 border-t">
                    <Pagination
                        v-if="completions.data && completions.data.length > 0 && completions.last_page > 1"
                        :links="completions.links"
                        @page-changed="handlePageChange"
                        class="mt-4"
                    />

                    <!-- Show pagination info -->
                    <div v-if="completions.data && completions.data.length > 0" class="text-sm text-muted-foreground mt-2">
                        Showing {{ completions.from }} to {{ completions.to }} of {{ completions.total }} results
                    </div>
                </div>
            </Card>
        </div>

        <!-- Modal for displaying full feedback/comment -->
        <Dialog v-model:open="showModal">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center">
                        <MessageSquare class="mr-2 h-5 w-5 text-primary" />
                        {{ modalTitle }}
                    </DialogTitle>
                </DialogHeader>
                <div class="mt-4">
                    <div class="text-sm text-muted-foreground whitespace-pre-wrap break-words max-h-96 overflow-y-auto p-4 bg-muted/50 rounded-lg">
                        {{ modalContent }}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
