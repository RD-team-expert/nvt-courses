<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import type { BreadcrumbItemType } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue
} from '@/components/ui/select'
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible'
import { Separator } from '@/components/ui/separator'

// Icons
import {
    Filter,
    Download,
    BarChart3,
    DollarSign,
    FileText,
    Star,
    TrendingUp,
    Calendar,
    User,
    Eye,
    ChevronDown,
    Settings,
    Loader2,
    X
} from 'lucide-vue-next'

const props = defineProps<{
    history?: {
        data: Array<{
            id: number
            evaluation_id: number
            category_name: string
            type_name: string
            score: number
            comments?: string
            created_at: string
            evaluation: {
                id: number
                total_score: number
                incentive_amount: number
                created_at: string
                user: {
                    id: number
                    name: string
                    email: string
                }
                course?: {
                    id: number
                    name: string
                }
                department?: {
                    id: number
                    name: string
                }
            }
        }>
        current_page: number
        last_page: number
        per_page: number
        total: number
        from: number
        to: number
    }
    departments?: Array<{
        id: number
        name: string
    }>
    users?: Array<{
        id: number
        name: string
    }>
    courses?: Array<{
        id: number
        name: string
    }>
    filters?: {
        department_id?: number
        user_id?: number
        course_id?: number
        start_date?: string
        end_date?: string
    }
    analytics?: {
        total_evaluations: number
        total_incentives: number
        average_score: number
        performance_distribution: Array<{
            id: number
            name: string
            range: string
            min_score: number
            max_score: number
            incentive_amount: number
            count: number
            color_class: string
        }>
        monthly_trends: { [key: string]: number }
        top_categories: Array<{
            category_name: string
            avg_score: number
            count: number
        }>
    }
}>()

// State management
const showFilters = ref(false)

// Form for filters
const filterForm = useForm({
    department_id: props.filters?.department_id?.toString() || '',
    user_id: props.filters?.user_id?.toString() || '',
    course_id: props.filters?.course_id?.toString() || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
})

// Safe defaults
const safeHistory = computed(() => props.history || { data: [], current_page: 1, last_page: 1, per_page: 15, total: 0, from: 0, to: 0 })
const safeDepartments = computed(() => props.departments || [])
const safeUsers = computed(() => props.users || [])
const safeCourses = computed(() => props.courses || [])
const safeAnalytics = computed(() => props.analytics || {
    total_evaluations: 0,
    total_incentives: 0,
    average_score: 0,
    performance_distribution: [],
    monthly_trends: {},
    top_categories: []
})

// Group history by evaluation
const groupedHistory = computed(() => {
    const groups: { [key: number]: any } = {}

    safeHistory.value.data.forEach(item => {
        if (!groups[item.evaluation_id]) {
            groups[item.evaluation_id] = {
                evaluation: item.evaluation,
                items: []
            }
        }
        groups[item.evaluation_id].items.push(item)
    })

    return Object.values(groups)
})

// Filter status
const hasActiveFilters = computed(() => {
    return filterForm.department_id || filterForm.user_id || filterForm.course_id ||
        filterForm.start_date || filterForm.end_date
})

// Apply filters
function applyFilters() {
    filterForm.get(route('admin.evaluations.history'), {
        preserveState: true,
        preserveScroll: true
    })
}

// Clear filters
function clearFilters() {
    filterForm.reset()
    router.get(route('admin.evaluations.history'))
}

// Export functions
function exportHistory() {
    const params = new URLSearchParams()

    if (filterForm.department_id) params.append('department_id', filterForm.department_id)
    if (filterForm.user_id) params.append('user_id', filterForm.user_id)
    if (filterForm.course_id) params.append('course_id', filterForm.course_id)
    if (filterForm.start_date) params.append('start_date', filterForm.start_date)
    if (filterForm.end_date) params.append('end_date', filterForm.end_date)

    window.open(`${route('admin.evaluations.history.export')}?${params.toString()}`, '_blank')
}

function exportSummary() {
    const params = new URLSearchParams()

    if (filterForm.department_id) params.append('department_id', filterForm.department_id)
    if (filterForm.user_id) params.append('user_id', filterForm.user_id)
    if (filterForm.course_id) params.append('course_id', filterForm.course_id)
    if (filterForm.start_date) params.append('start_date', filterForm.start_date)
    if (filterForm.end_date) params.append('end_date', filterForm.end_date)

    window.open(`${route('admin.evaluations.history.export-summary')}?${params.toString()}`, '_blank')
}

// View evaluation details
function viewDetails(evaluationId: number) {
    router.visit(route('admin.evaluations.history.details', evaluationId))
}

// Enhanced performance level function that uses dynamic ranges
function getPerformanceLevel(score: number) {
    if (!safeAnalytics.value.performance_distribution.length) {
        return { label: 'No Tiers Configured', variant: 'outline' as const }
    }

    const tier = safeAnalytics.value.performance_distribution.find(tier =>
        score >= tier.min_score && score <= tier.max_score
    )

    if (!tier) {
        return { label: 'Unranked', variant: 'outline' as const }
    }

    const variants = {
        'emerald': 'default',
        'green': 'default',
        'blue': 'secondary',
        'yellow': 'secondary',
        'red': 'destructive'
    }

    return {
        label: tier.name,
        variant: variants[tier.color_class] || 'outline'
    }
}

// Get category score badge variant
function getCategoryVariant(score: number) {
    if (score >= 5) return 'default'
    if (score >= 4) return 'default'
    if (score >= 3) return 'secondary'
    if (score >= 2) return 'secondary'
    return 'destructive'
}

// Get tier variant for performance distribution
function getTierVariant(colorClass: string) {
    const variants = {
        'emerald': 'default',
        'green': 'default',
        'blue': 'secondary',
        'yellow': 'secondary',
        'red': 'destructive'
    }
    return variants[colorClass] || 'outline'
}

// Calculate grid columns based on number of tiers
const performanceGridCols = computed(() => {
    const tierCount = safeAnalytics.value.performance_distribution.length
    if (tierCount <= 1) return 'grid-cols-1'
    if (tierCount <= 2) return 'grid-cols-1 md:grid-cols-2'
    if (tierCount <= 3) return 'grid-cols-1 md:grid-cols-3'
    if (tierCount <= 4) return 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4'
    if (tierCount <= 5) return 'grid-cols-1 md:grid-cols-3 lg:grid-cols-5'
    if (tierCount <= 6) return 'grid-cols-1 md:grid-cols-3 lg:grid-cols-6'
    return 'grid-cols-1 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8'
})

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Evaluations', href: route('admin.evaluations.index') },
    { name: 'History', href: null }
]

// Active filter count
const activeFilterCount = computed(() => {
    return [filterForm.department_id, filterForm.user_id, filterForm.course_id, filterForm.start_date, filterForm.end_date].filter(Boolean).length
})
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Evaluation History</h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        View and manage all evaluation records and history
                        <span v-if="safeHistory.total > 0" class="font-medium">
                            ({{ safeHistory.total }} total records)
                        </span>
                    </p>
                </div>
                <div class="flex space-x-3">
                    <Button
                        @click="showFilters = !showFilters"
                        variant="outline"
                        :class="hasActiveFilters ? 'border-primary' : ''"
                    >
                        <Filter class="mr-2 h-4 w-4" />
                        Filters
                        <Badge v-if="hasActiveFilters" variant="secondary" class="ml-2">
                            {{ activeFilterCount }}
                        </Badge>
                    </Button>
                    <Button @click="exportSummary" variant="outline">
                        <BarChart3 class="mr-2 h-4 w-4" />
                        Export Summary
                    </Button>
                    <Button @click="exportHistory">
                        <Download class="mr-2 h-4 w-4" />
                        Export Details
                    </Button>
                </div>
            </div>

            <!-- Analytics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Total Evaluations -->
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <FileText class="h-8 w-8 text-primary" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Evaluations</p>
                                <p class="text-2xl font-bold text-primary">{{ safeAnalytics.total_evaluations.toLocaleString() }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Total Incentives -->
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <DollarSign class="h-8 w-8 text-green-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Total Incentives</p>
                                <p class="text-2xl font-bold text-green-600">${{ safeAnalytics.total_incentives.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Average Score -->
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <TrendingUp class="h-8 w-8 text-purple-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Average Score</p>
                                <p class="text-2xl font-bold text-purple-600">{{ safeAnalytics.average_score }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Performance Summary -->
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <div class="shrink-0">
                                <Star class="h-8 w-8 text-yellow-600" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-muted-foreground">Performance</p>
                                <p class="text-lg font-bold text-yellow-600">
                                    {{ getPerformanceLevel(safeAnalytics.average_score).label }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters Panel -->
            <Collapsible v-model:open="showFilters">
                <CollapsibleContent>
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle>Filter Evaluations</CardTitle>
                                <Button
                                    v-if="hasActiveFilters"
                                    @click="clearFilters"
                                    variant="outline"
                                    size="sm"
                                >
                                    <X class="mr-2 h-4 w-4" />
                                    Clear All
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                <!-- Department Filter -->
                                <div class="space-y-2">
                                    <Label>Department</Label>
                                    <Select v-model="filterForm.department_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Departments" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">All Departments</SelectItem>
                                            <SelectItem v-for="dept in safeDepartments" :key="dept.id" :value="dept.id.toString()">
                                                {{ dept.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- User Filter -->
                                <div class="space-y-2">
                                    <Label>Employee</Label>
                                    <Select v-model="filterForm.user_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Employees" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">All Employees</SelectItem>
                                            <SelectItem v-for="user in safeUsers" :key="user.id" :value="user.id.toString()">
                                                {{ user.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Course Filter -->
                                <div class="space-y-2">
                                    <Label>Course</Label>
                                    <Select v-model="filterForm.course_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Courses" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">All Courses</SelectItem>
                                            <SelectItem v-for="course in safeCourses" :key="course.id" :value="course.id.toString()">
                                                {{ course.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Start Date Filter -->
                                <div class="space-y-2">
                                    <Label>Start Date</Label>
                                    <Input
                                        type="date"
                                        v-model="filterForm.start_date"
                                    />
                                </div>

                                <!-- End Date Filter -->
                                <div class="space-y-2">
                                    <Label>End Date</Label>
                                    <Input
                                        type="date"
                                        v-model="filterForm.end_date"
                                    />
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end">
                                <Button
                                    @click="applyFilters"
                                    :disabled="filterForm.processing"
                                >
                                    <Loader2 v-if="filterForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                                    <span v-if="filterForm.processing">Applying...</span>
                                    <span v-else>Apply Filters</span>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </CollapsibleContent>
            </Collapsible>

            <!-- Performance Distribution Chart -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Performance Distribution</CardTitle>
                        <CardDescription>
                            {{ safeAnalytics.performance_distribution.length > 0
                            ? `Based on ${safeAnalytics.performance_distribution.length} configured tiers`
                            : 'No performance tiers configured' }}
                        </CardDescription>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Performance Tiers -->
                    <div v-if="safeAnalytics.performance_distribution.length > 0" class="grid gap-4" :class="performanceGridCols">
                        <Card
                            v-for="tier in safeAnalytics.performance_distribution"
                            :key="tier.id"
                            class="text-center hover:shadow-md transition-shadow duration-200"
                        >
                            <CardContent class="p-4">
                                <div class="text-2xl font-bold mb-1">
                                    {{ tier.count }}
                                </div>
                                <Badge :variant="getTierVariant(tier.color_class)" class="mb-2">
                                    {{ tier.name }}
                                </Badge>
                                <div class="text-xs text-muted-foreground mb-1">
                                    {{ tier.range }} points
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    ${{ parseFloat(tier.incentive_amount).toFixed(2) }}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- No Incentives Configured Message -->
                    <div v-else class="text-center py-8">
                        <BarChart3 class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                        <CardTitle class="mb-2">No Performance Tiers Configured</CardTitle>
                        <CardDescription class="mb-4">Configure incentive ranges to see performance distribution.</CardDescription>
                        <Button asChild>
                            <Link :href="route('admin.evaluations.index')">
                                <Settings class="mr-2 h-4 w-4" />
                                Configure Incentives
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Top Categories -->
            <Card v-if="safeAnalytics.top_categories.length > 0">
                <CardHeader>
                    <CardTitle>Top Performing Categories</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <Card
                            v-for="category in safeAnalytics.top_categories"
                            :key="category.category_name"
                            class="hover:shadow-sm transition-shadow duration-200"
                        >
                            <CardContent class="p-4">
                                <CardTitle class="text-sm mb-2">{{ category.category_name }}</CardTitle>
                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-bold text-primary">{{ category.avg_score }}</span>
                                    <Badge variant="outline" class="text-xs">{{ category.count }} evals</Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>

            <!-- History List -->
            <Card>
                <!-- List Header -->
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Evaluation Records</CardTitle>
                        <CardDescription>
                            {{ safeHistory.total > 0
                            ? `Showing ${safeHistory.from}-${safeHistory.to} of ${safeHistory.total} results`
                            : 'No results found' }}
                        </CardDescription>
                    </div>
                </CardHeader>

                <CardContent class="p-0">
                    <!-- Empty State -->
                    <div v-if="groupedHistory.length === 0" class="p-8 text-center">
                        <FileText class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                        <CardTitle class="mb-2">No Evaluation History</CardTitle>
                        <CardDescription class="mb-4">
                            {{ hasActiveFilters ? 'No evaluations found with the selected filters.' : 'There are no evaluation records to display.' }}
                        </CardDescription>
                        <Button v-if="hasActiveFilters" @click="clearFilters">
                            Clear Filters
                        </Button>
                    </div>

                    <!-- History Items -->
                    <div v-else class="divide-y divide-border">
                        <div
                            v-for="group in groupedHistory"
                            :key="group.evaluation.id"
                            class="p-6 hover:bg-accent/50 transition-colors duration-200"
                        >
                            <!-- Evaluation Header -->
                            <div class="flex items-start justify-between mb-6">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <CardTitle class="text-lg">
                                            {{ group.evaluation.user.name }}
                                        </CardTitle>
                                        <Badge :variant="getPerformanceLevel(group.evaluation.total_score).variant">
                                            {{ getPerformanceLevel(group.evaluation.total_score).label }}
                                        </Badge>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-muted-foreground">
                                        <div>
                                            <span class="font-medium">Email:</span> {{ group.evaluation.user.email }}
                                        </div>
                                        <div v-if="group.evaluation.course">
                                            <span class="font-medium">Course:</span> {{ group.evaluation.course.name }}
                                        </div>
                                        <div v-if="group.evaluation.department">
                                            <span class="font-medium">Department:</span> {{ group.evaluation.department.name }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Date:</span> {{ new Date(group.evaluation.created_at).toLocaleDateString() }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-6">
                                    <div class="text-right">
                                        <div class="text-2xl font-bold">
                                            {{ group.evaluation.total_score }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">Total Score</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xl font-bold text-green-600">
                                            ${{ parseFloat(group.evaluation.incentive_amount.toString()).toFixed(2) }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">Incentive</div>
                                    </div>
                                    <Button @click="viewDetails(group.evaluation.id)">
                                        <Eye class="mr-2 h-4 w-4" />
                                        View Details
                                    </Button>
                                </div>
                            </div>

                            <!-- Category Summary -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                <Card
                                    v-for="item in group.items"
                                    :key="item.id"
                                    class="hover:shadow-sm transition-shadow duration-200"
                                >
                                    <CardContent class="p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <CardTitle class="text-sm">{{ item.category_name }}</CardTitle>
                                            <Badge :variant="getCategoryVariant(item.score)">
                                                {{ item.score }}
                                            </Badge>
                                        </div>
                                        <p class="text-sm text-muted-foreground mb-2">{{ item.type_name }}</p>
                                        <p v-if="item.comments" class="text-xs text-muted-foreground line-clamp-2">{{ item.comments }}</p>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="safeHistory.last_page > 1" class="px-6 py-4 border-t border-border">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <Button
                                    v-if="safeHistory.current_page > 1"
                                    asChild
                                    variant="outline"
                                >
                                    <Link :href="route('admin.evaluations.history', { ...filterForm.data, page: safeHistory.current_page - 1 })">
                                        Previous
                                    </Link>
                                </Button>
                                <Button v-else variant="outline" disabled>
                                    Previous
                                </Button>
                            </div>

                            <div class="flex items-center space-x-1">
                                <template v-for="page in Math.min(5, safeHistory.last_page)" :key="page">
                                    <Button
                                        asChild
                                        :variant="page === safeHistory.current_page ? 'default' : 'outline'"
                                        size="sm"
                                    >
                                        <Link :href="route('admin.evaluations.history', { ...filterForm.data, page })">
                                            {{ page }}
                                        </Link>
                                    </Button>
                                </template>
                            </div>

                            <div class="flex items-center space-x-2">
                                <Button
                                    v-if="safeHistory.current_page < safeHistory.last_page"
                                    asChild
                                    variant="outline"
                                >
                                    <Link :href="route('admin.evaluations.history', { ...filterForm.data, page: safeHistory.current_page + 1 })">
                                        Next
                                    </Link>
                                </Button>
                                <Button v-else variant="outline" disabled>
                                    Next
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
