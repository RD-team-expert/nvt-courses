<!--
  Quiz Attempts Report Page
  Comprehensive reporting interface for tracking quiz attempts, scores, and performance analytics
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
import { Progress } from '@/components/ui/progress'
import {
    Download,
    Filter,
    RotateCcw,
    User,
    FileText,
    Calendar,
    CheckCircle,
    XCircle,
    Clock,
    Target,
    Trophy
} from 'lucide-vue-next'

const props = defineProps({
    attempts: Object,
    quizzes: Array,
    filters: Object,
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Reports & Analytics', href: route('admin.reports.index') },
    { name: 'Quiz Attempts', href: route('admin.reports.quiz-attempts') },
]

// Filter state
const filters = ref({
    quiz_id: props.filters?.quiz_id || '',
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
})

// Handle select changes
const handleQuizChange = (value: string) => {
    filters.value.quiz_id = value === 'all' ? '' : value
}

const handleStatusChange = (value: string) => {
    filters.value.status = value === 'all' ? '' : value
}

// Apply filters with debounce
const applyFilters = debounce(() => {
    router.get(route('admin.reports.quiz-attempts'), filters.value, {
        preserveState: true,
        replace: true,
    })
}, 500)

// Watch for filter changes
watch(filters, () => {
    applyFilters()
}, { deep: true })

// Reset filters
const resetFilters = () => {
    filters.value = {
        quiz_id: '',
        status: '',
        date_from: '',
        date_to: '',
    }
    applyFilters()
}

// Export to CSV
const exportToCsv = () => {
    const queryParams = new URLSearchParams(filters.value).toString()
    window.location.href = route('admin.reports.export.quiz-attempts') + '?' + queryParams
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
        minute: '2-digit',
    })
}

// Handle pagination
const handlePageChange = (page) => {
    router.get(route('admin.reports.quiz-attempts'), {
        ...filters.value,
        page,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
        onSuccess: () => {
            document.querySelector('.bg-white.rounded-lg.shadow')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
        },
    })
}

// Get status badge variant and icon
const getStatusInfo = (attempt) => {
    if (!attempt.completed_at) {
        return { variant: 'secondary', icon: Clock, label: 'Pending' }
    }
    if (attempt.passed) {
        return { variant: 'default', icon: CheckCircle, label: 'Passed' }
    }
    return { variant: 'destructive', icon: XCircle, label: 'Failed' }
}

// Calculate score percentage
const getScorePercentage = (attempt) => {
    if (!attempt.quiz_total_points || attempt.quiz_total_points === 0) return 0
    return Math.round((attempt.total_score / attempt.quiz_total_points) * 100)
}

// Get score color based on performance
const getScoreColor = (percentage) => {
    if (percentage >= 80) return 'text-green-600'
    if (percentage >= 60) return 'text-yellow-600'
    return 'text-red-600'
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-foreground">Quiz Attempts Report</h1>
                    <p class="text-sm text-muted-foreground mt-1">Comprehensive reporting interface for tracking quiz attempts, scores, and performance analytics</p>
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
                            <CardTitle>Filter Attempts</CardTitle>
                            <CardDescription>Use the filters below to narrow down the quiz attempt records</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="space-y-2">
                            <Label for="quiz_filter">Quiz</Label>
                            <Select
                                :model-value="filters.quiz_id || 'all'"
                                @update:model-value="handleQuizChange"
                            >
                                <SelectTrigger id="quiz_filter">
                                    <SelectValue placeholder="All Quizzes" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Quizzes</SelectItem>
                                    <SelectItem v-for="quiz in quizzes" :key="quiz.id" :value="quiz.id.toString()">
                                        {{ quiz.title }}
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
                                    <SelectItem value="passed">Passed</SelectItem>
                                    <SelectItem value="failed">Failed</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
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

            <!-- Attempts Table -->
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
                                        <FileText class="mr-2 h-4 w-4" />
                                        Quiz
                                    </div>
                                </TableHead>
                                <TableHead class="hidden md:table-cell">
                                    <div class="flex items-center">
                                        <Target class="mr-2 h-4 w-4" />
                                        Score
                                    </div>
                                </TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="hidden md:table-cell">
                                    <div class="flex items-center">
                                        <Trophy class="mr-2 h-4 w-4" />
                                        Attempt
                                    </div>
                                </TableHead>
                                <TableHead>
                                    <div class="flex items-center">
                                        <Calendar class="mr-2 h-4 w-4" />
                                        Completed
                                    </div>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="attempts.data.length === 0">
                                <TableCell colspan="6" class="text-center text-muted-foreground py-8">
                                    <div class="flex flex-col items-center">
                                        <FileText class="h-12 w-12 text-muted-foreground mb-2" />
                                        No attempt records found
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-else v-for="(attempt, i) in attempts.data" :key="i" class="hover:bg-muted/50">
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="font-medium text-foreground">{{ attempt.user_name }}</div>
                                        <div class="text-xs text-muted-foreground hidden sm:block">{{ attempt.user_email }}</div>
                                        <div class="text-xs text-muted-foreground sm:hidden">{{ attempt.quiz_title }}</div>
                                    </div>
                                </TableCell>
                                <TableCell class="hidden sm:table-cell">
                                    <Badge variant="outline">{{ attempt.quiz_title }}</Badge>
                                </TableCell>
                                <TableCell class="hidden md:table-cell">
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="font-mono">{{ attempt.total_score }}/{{ attempt.quiz_total_points }}</span>
                                            <span class="font-semibold" :class="getScoreColor(getScorePercentage(attempt))">
                                                {{ getScorePercentage(attempt) }}%
                                            </span>
                                        </div>
                                        <Progress
                                            :value="getScorePercentage(attempt)"
                                            class="h-2"
                                        />
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="getStatusInfo(attempt).variant" class="flex items-center w-fit">
                                        <component :is="getStatusInfo(attempt).icon" class="mr-1 h-3 w-3" />
                                        {{ getStatusInfo(attempt).label }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="hidden md:table-cell">
                                    <Badge variant="secondary" class="font-mono">
                                        #{{ attempt.attempt_number }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm text-foreground">{{ formatDate(attempt.completed_at) }}</div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div class="px-4 sm:px-6 py-3 border-t">
                    <Pagination
                        v-if="attempts.data && attempts.data.length > 0 && attempts.last_page > 1"
                        :links="attempts.links"
                        @page-changed="handlePageChange"
                        class="mt-4"
                    />
                    <div v-if="attempts.data && attempts.data.length > 0" class="text-sm text-muted-foreground mt-2">
                        Showing {{ attempts.from }} to {{ attempts.to }} of {{ attempts.total }} results
                    </div>
                </div>
            </Card>
        </div>
    </AdminLayout>
</template>
