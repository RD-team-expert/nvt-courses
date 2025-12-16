<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { debounce } from 'lodash'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
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
import { Download, Filter, RotateCcw } from 'lucide-vue-next'

interface Assignment {
    id: number
    user_name: string
    employee_code: string
    department: string
    course_type: string
    course_name: string
    completion_status: string
    days_overdue: number | null
    progress_percentage: number
    started_date: string | null
    completion_date: string | null
    learning_score: number
    score_band: string
    compliance_status: string
}

interface FilterOptions {
    departments: Array<{ id: number; name: string }>
    users: Array<{ id: number; name: string; email: string }>
    courses: Array<{ id: number; name: string; type: string }>
    courseTypes: Array<{ value: string; label: string }>
    statuses: Array<{ value: string; label: string }>
}

interface Props {
    assignments: {
        data: Assignment[]
        current_page: number
        per_page: number
        total: number
        last_page: number
        from: number
        to: number
    }
    filters: {
        department_id?: string
        course_type?: string
        date_from?: string
        date_to?: string
        status?: string
        user_id?: string
        course_id?: string
    }
    filterOptions: FilterOptions
    error?: string
}

const props = defineProps<Props>()

// Filter state
const localFilters = ref({ ...props.filters })

// Debounced filter update
const updateFilters = debounce(() => {
    router.get(route('admin.reports.user-course-progress'), localFilters.value, {
        preserveState: true,
        preserveScroll: true,
    })
}, 500)

watch(localFilters, updateFilters, { deep: true })

// Clear filters
const clearFilters = () => {
    localFilters.value = {}
    router.get(route('admin.reports.user-course-progress'))
}

// Export to Excel
const exportData = () => {
    window.location.href = route('admin.reports.user-course-progress.export', localFilters.value)
}

// Get badge variant for score band
const getScoreBandVariant = (band: string) => {
    if (band === 'Excellent') return 'default'
    if (band === 'Good') return 'secondary'
    return 'destructive'
}

// Get badge variant for compliance status
const getComplianceVariant = (status: string) => {
    if (status === 'Compliant') return 'default'
    if (status === 'At Risk') return 'secondary'
    return 'destructive'
}

// Pagination
const goToPage = (page: number) => {
    router.get(route('admin.reports.user-course-progress'), {
        ...localFilters.value,
        page,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}
</script>

<template>
    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">User Course Progress Report</h1>
                    <p class="text-muted-foreground mt-1">
                        View and export comprehensive course progress data
                    </p>
                </div>
                <Button @click="exportData" class="gap-2">
                    <Download class="h-4 w-4" />
                    Export to Excel
                </Button>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="bg-destructive/15 text-destructive px-4 py-3 rounded-md">
                {{ error }}
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Filter class="h-5 w-5" />
                        Filters
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Department Filter -->
                        <div class="space-y-2">
                            <Label>Department</Label>
                            <Select v-model="localFilters.department_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Departments" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="dept in filterOptions.departments"
                                        :key="dept.id"
                                        :value="dept.id.toString()"
                                    >
                                        {{ dept.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Course Type Filter -->
                        <div class="space-y-2">
                            <Label>Course Type</Label>
                            <Select v-model="localFilters.course_type">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="type in filterOptions.courseTypes"
                                        :key="type.value"
                                        :value="type.value"
                                    >
                                        {{ type.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Status Filter -->
                        <div class="space-y-2">
                            <Label>Status</Label>
                            <Select v-model="localFilters.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="status in filterOptions.statuses"
                                        :key="status.value"
                                        :value="status.value"
                                    >
                                        {{ status.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Date From -->
                        <div class="space-y-2">
                            <Label>Date From</Label>
                            <Input
                                v-model="localFilters.date_from"
                                type="date"
                            />
                        </div>

                        <!-- Date To -->
                        <div class="space-y-2">
                            <Label>Date To</Label>
                            <Input
                                v-model="localFilters.date_to"
                                type="date"
                            />
                        </div>

                        <!-- Clear Filters Button -->
                        <div class="flex items-end">
                            <Button @click="clearFilters" variant="outline" class="w-full gap-2">
                                <RotateCcw class="h-4 w-4" />
                                Clear Filters
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Results Table -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        Course Progress Data
                        <span class="text-sm font-normal text-muted-foreground ml-2">
                            ({{ assignments?.total || 0 }} total records)
                        </span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Employee</TableHead>
                                    <TableHead>Department</TableHead>
                                    <TableHead>Course Type</TableHead>
                                    <TableHead>Course Name</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Progress</TableHead>
                                    <TableHead>Days Overdue</TableHead>
                                    <TableHead>Started</TableHead>
                                    <TableHead>Completed</TableHead>
                                    <TableHead>Learning Score</TableHead>
                                    <TableHead>Score Band</TableHead>
                                    <TableHead>Compliance</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="!assignments?.data || assignments.data.length === 0">
                                    <TableCell colspan="12" class="text-center py-8 text-muted-foreground">
                                        No course progress data found. Try adjusting your filters.
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="assignment in assignments?.data || []" :key="assignment.id">
                                    <TableCell>
                                        <div class="font-medium">{{ assignment.user_name }}</div>
                                    </TableCell>
                                    <TableCell>{{ assignment.department }}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{{ assignment.course_type }}</Badge>
                                    </TableCell>
                                    <TableCell>{{ assignment.course_name }}</TableCell>
                                    <TableCell>
                                        <Badge :variant="assignment.completion_status === 'Completed' ? 'default' : 'secondary'">
                                            {{ assignment.completion_status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ assignment.progress_percentage }}%</TableCell>
                                    <TableCell>
                                        <span v-if="assignment.days_overdue !== null && assignment.days_overdue > 0" class="text-destructive font-medium">
                                            {{ assignment.days_overdue }} days
                                        </span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="assignment.started_date" class="text-sm">{{ assignment.started_date }}</span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="assignment.completion_date" class="text-sm">{{ assignment.completion_date }}</span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </TableCell>
                                    <TableCell>
                                        <span class="font-medium">{{ assignment.learning_score }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getScoreBandVariant(assignment.score_band)">
                                            {{ assignment.score_band }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getComplianceVariant(assignment.compliance_status)">
                                            {{ assignment.compliance_status }}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="assignments?.last_page > 1" class="flex items-center justify-between mt-4">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ assignments.from }} to {{ assignments.to }} of {{ assignments.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button
                                @click="goToPage(assignments.current_page - 1)"
                                :disabled="assignments.current_page === 1"
                                variant="outline"
                                size="sm"
                            >
                                Previous
                            </Button>
                            <Button
                                @click="goToPage(assignments.current_page + 1)"
                                :disabled="assignments.current_page === assignments.last_page"
                                variant="outline"
                                size="sm"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
