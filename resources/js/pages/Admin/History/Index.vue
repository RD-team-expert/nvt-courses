<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import type { BreadcrumbItemType } from '@/types'

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
    department_id: props.filters?.department_id || null,
    user_id: props.filters?.user_id || null,
    course_id: props.filters?.course_id || null,
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

    if (filterForm.department_id) params.append('department_id', filterForm.department_id.toString())
    if (filterForm.user_id) params.append('user_id', filterForm.user_id.toString())
    if (filterForm.course_id) params.append('course_id', filterForm.course_id.toString())
    if (filterForm.start_date) params.append('start_date', filterForm.start_date)
    if (filterForm.end_date) params.append('end_date', filterForm.end_date)

    window.open(`${route('admin.evaluations.history.export')}?${params.toString()}`, '_blank')
}

function exportSummary() {
    const params = new URLSearchParams()

    if (filterForm.department_id) params.append('department_id', filterForm.department_id.toString())
    if (filterForm.user_id) params.append('user_id', filterForm.user_id.toString())
    if (filterForm.course_id) params.append('course_id', filterForm.course_id.toString())
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
        // Fallback if no incentives configured
        return { label: 'No Tiers Configured', class: 'bg-gray-100 text-gray-800 border-gray-200' }
    }

    // Find the matching tier for this score
    const tier = safeAnalytics.value.performance_distribution.find(tier =>
        score >= tier.min_score && score <= tier.max_score
    )

    if (!tier) {
        return { label: 'Unranked', class: 'bg-gray-100 text-gray-800 border-gray-200' }
    }

    const colorClasses = {
        'emerald': 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'green': 'bg-green-100 text-green-800 border-green-200',
        'blue': 'bg-blue-100 text-blue-800 border-blue-200',
        'yellow': 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'red': 'bg-red-100 text-red-800 border-red-200'
    }

    return {
        label: tier.name,
        class: colorClasses[tier.color_class] || 'bg-gray-100 text-gray-800 border-gray-200'
    }
}

// Get category score color
function getCategoryColor(score: number) {
    if (score >= 5) return 'bg-emerald-100 text-emerald-800 border-emerald-200'
    if (score >= 4) return 'bg-green-100 text-green-800 border-green-200'
    if (score >= 3) return 'bg-blue-100 text-blue-800 border-blue-200'
    if (score >= 2) return 'bg-yellow-100 text-yellow-800 border-yellow-200'
    return 'bg-red-100 text-red-800 border-red-200'
}

// Get tier background color for performance distribution
function getTierBackgroundColor(colorClass: string) {
    const backgrounds = {
        'emerald': 'bg-emerald-50 border-emerald-200',
        'green': 'bg-green-50 border-green-200',
        'blue': 'bg-blue-50 border-blue-200',
        'yellow': 'bg-yellow-50 border-yellow-200',
        'red': 'bg-red-50 border-red-200'
    }
    return backgrounds[colorClass] || 'bg-gray-50 border-gray-200'
}

// Get tier text color for performance distribution
function getTierTextColor(colorClass: string) {
    const textColors = {
        'emerald': 'text-emerald-600',
        'green': 'text-green-600',
        'blue': 'text-blue-600',
        'yellow': 'text-yellow-600',
        'red': 'text-red-600'
    }
    return textColors[colorClass] || 'text-gray-600'
}

// Get tier label color for performance distribution
function getTierLabelColor(colorClass: string) {
    const labelColors = {
        'emerald': 'text-emerald-700',
        'green': 'text-green-700',
        'blue': 'text-blue-700',
        'yellow': 'text-yellow-700',
        'red': 'text-red-700'
    }
    return labelColors[colorClass] || 'text-gray-700'
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

// Format monthly trends for display
const monthlyTrendsData = computed(() => {
    const trends = safeAnalytics.value.monthly_trends
    return Object.entries(trends).map(([month, count]) => ({
        month: new Date(month + '-01').toLocaleDateString('en-US', { month: 'short', year: 'numeric' }),
        count
    }))
})

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Evaluations', href: route('admin.evaluations.index') },
    { name: 'History', href: null }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Evaluation History</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            View and manage all evaluation records and history
                            <span v-if="safeHistory.total > 0" class="font-medium">
                                ({{ safeHistory.total }} total records)
                            </span>
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <button
                            @click="showFilters = !showFilters"
                            class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition-colors duration-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500"
                            :class="hasActiveFilters
                                ? 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z" />
                            </svg>
                            Filters
                            <span v-if="hasActiveFilters" class="ml-2 inline-flex items-center justify-center w-5 h-5 text-xs bg-indigo-500 text-white rounded-full">
                                {{ [filterForm.department_id, filterForm.user_id, filterForm.course_id, filterForm.start_date, filterForm.end_date].filter(Boolean).length }}
                            </span>
                        </button>
                        <button
                            @click="exportSummary"
                            class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-blue-700 focus:outline-hidden focus:ring-2 focus:ring-blue-500"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Export Summary
                        </button>
                        <button
                            @click="exportHistory"
                            class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-green-700 focus:outline-hidden focus:ring-2 focus:ring-green-500"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                            </svg>
                            Export Details
                        </button>
                    </div>
                </div>
            </div>

            <!-- Analytics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Total Evaluations -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">Total Evaluations</p>
                            <p class="text-2xl font-bold text-blue-600">{{ safeAnalytics.total_evaluations.toLocaleString() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Total Incentives -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">Total Incentives</p>
                            <p class="text-2xl font-bold text-green-600">${{ safeAnalytics.total_incentives.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Average Score -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">Average Score</p>
                            <p class="text-2xl font-bold text-purple-600">{{ safeAnalytics.average_score }}</p>
                        </div>
                    </div>
                </div>

                <!-- Performance Summary -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-3">
                        <div class="shrink-0">
                            <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-900">Performance</p>
                            <p class="text-lg font-bold text-indigo-600">
                                {{ getPerformanceLevel(safeAnalytics.average_score).label }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Panel -->
            <div v-if="showFilters" class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Filter Evaluations</h3>
                    <div class="flex space-x-2">
                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            type="button"
                            class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-200"
                        >
                            Clear All
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Department Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <select
                            v-model="filterForm.department_id"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                        >
                            <option :value="null">All Departments</option>
                            <option v-for="dept in safeDepartments" :key="dept.id" :value="dept.id">
                                {{ dept.name }}
                            </option>
                        </select>
                    </div>

                    <!-- User Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
                        <select
                            v-model="filterForm.user_id"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                        >
                            <option :value="null">All Employees</option>
                            <option v-for="user in safeUsers" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Course Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Course</label>
                        <select
                            v-model="filterForm.course_id"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                        >
                            <option :value="null">All Courses</option>
                            <option v-for="course in safeCourses" :key="course.id" :value="course.id">
                                {{ course.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Start Date Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <input
                            type="date"
                            v-model="filterForm.start_date"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- End Date Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input
                            type="date"
                            v-model="filterForm.end_date"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button
                        @click="applyFilters"
                        type="button"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500"
                        :disabled="filterForm.processing"
                    >
                        <svg v-if="filterForm.processing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        <span v-if="filterForm.processing">Applying...</span>
                        <span v-else">Apply Filters</span>
                    </button>
                </div>
            </div>

            <!-- DYNAMIC Performance Distribution Chart -->
            <div class="mb-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Performance Distribution</h3>
                    <div class="text-sm text-gray-500">
                        {{ safeAnalytics.performance_distribution.length > 0
                        ? `Based on ${safeAnalytics.performance_distribution.length} configured tiers`
                        : 'No performance tiers configured' }}
                    </div>
                </div>

                <!-- Performance Tiers -->
                <div v-if="safeAnalytics.performance_distribution.length > 0" class="grid gap-4" :class="performanceGridCols">
                    <div
                        v-for="tier in safeAnalytics.performance_distribution"
                        :key="tier.id"
                        class="text-center p-4 rounded-lg border transition-all duration-200 hover:shadow-md"
                        :class="getTierBackgroundColor(tier.color_class)"
                    >
                        <div
                            class="text-2xl font-bold mb-1"
                            :class="getTierTextColor(tier.color_class)"
                        >
                            {{ tier.count }}
                        </div>
                        <div
                            class="text-sm font-medium mb-1"
                            :class="getTierLabelColor(tier.color_class)"
                        >
                            {{ tier.name }}
                        </div>
                        <div class="text-xs text-gray-600 mb-1">
                            {{ tier.range }} points
                        </div>
                        <div class="text-xs text-gray-500">
                            ${{ parseFloat(tier.incentive_amount).toFixed(2) }}
                        </div>
                    </div>
                </div>

                <!-- No Incentives Configured Message -->
                <div v-else class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Performance Tiers Configured</h3>
                    <p class="text-sm text-gray-500 mb-4">Configure incentive ranges to see performance distribution.</p>
                    <Link
                        :href="route('admin.evaluations.index')"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-colors duration-200"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Configure Incentives
                    </Link>
                </div>
            </div>

            <!-- Top Categories -->
            <div v-if="safeAnalytics.top_categories.length > 0" class="mb-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performing Categories</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <div
                        v-for="category in safeAnalytics.top_categories"
                        :key="category.category_name"
                        class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-sm transition-shadow duration-200"
                    >
                        <h4 class="font-medium text-gray-900 text-sm mb-2">{{ category.category_name }}</h4>
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-bold text-indigo-600">{{ category.avg_score }}</span>
                            <span class="text-xs text-gray-500">{{ category.count }} evals</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <!-- List Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Evaluation Records</h3>
                        <div class="text-sm text-gray-500">
                            {{ safeHistory.total > 0
                            ? `Showing ${safeHistory.from}-${safeHistory.to} of ${safeHistory.total} results`
                            : 'No results found' }}
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="groupedHistory.length === 0" class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Evaluation History</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        {{ hasActiveFilters ? 'No evaluations found with the selected filters.' : 'There are no evaluation records to display.' }}
                    </p>
                    <div v-if="hasActiveFilters">
                        <button
                            @click="clearFilters"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-indigo-700"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- History Items -->
                <div v-else class="divide-y divide-gray-200">
                    <div
                        v-for="group in groupedHistory"
                        :key="group.evaluation.id"
                        class="p-6 hover:bg-gray-50 transition-colors duration-200"
                    >
                        <!-- Evaluation Header -->
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ group.evaluation.user.name }}
                                    </h3>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                        :class="getPerformanceLevel(group.evaluation.total_score).class"
                                    >
                                        {{ getPerformanceLevel(group.evaluation.total_score).label }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-gray-600">
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
                                    <div class="text-2xl font-bold text-gray-900">
                                        {{ group.evaluation.total_score }}
                                    </div>
                                    <div class="text-sm text-gray-500">Total Score</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xl font-bold text-green-600">
                                        ${{ parseFloat(group.evaluation.incentive_amount.toString()).toFixed(2) }}
                                    </div>
                                    <div class="text-sm text-gray-500">Incentive</div>
                                </div>
                                <button
                                    @click="viewDetails(group.evaluation.id)"
                                    class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500"
                                >
                                    View Details
                                </button>
                            </div>
                        </div>

                        <!-- Category Summary -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <div
                                v-for="item in group.items"
                                :key="item.id"
                                class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:shadow-sm transition-shadow duration-200"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-gray-900 text-sm">{{ item.category_name }}</h4>
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-bold border"
                                        :class="getCategoryColor(item.score)"
                                    >
                                        {{ item.score }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-2">{{ item.type_name }}</p>
                                <p v-if="item.comments" class="text-xs text-gray-500 line-clamp-2">{{ item.comments }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="safeHistory.last_page > 1" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <Link
                                v-if="safeHistory.current_page > 1"
                                :href="route('admin.evaluations.history', { ...filterForm.data, page: safeHistory.current_page - 1 })"
                                class="inline-flex items-center rounded-lg bg-white border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <span v-else class="inline-flex items-center rounded-lg bg-gray-100 border border-gray-300 px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed">
                                Previous
                            </span>
                        </div>

                        <div class="flex items-center space-x-1">
                            <template v-for="page in Math.min(5, safeHistory.last_page)" :key="page">
                                <Link
                                    :href="route('admin.evaluations.history', { ...filterForm.data, page })"
                                    class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-200"
                                    :class="page === safeHistory.current_page
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'"
                                >
                                    {{ page }}
                                </Link>
                            </template>
                        </div>

                        <div class="flex items-center space-x-2">
                            <Link
                                v-if="safeHistory.current_page < safeHistory.last_page"
                                :href="route('admin.evaluations.history', { ...filterForm.data, page: safeHistory.current_page + 1 })"
                                class="inline-flex items-center rounded-lg bg-white border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Next
                            </Link>
                            <span v-else class="inline-flex items-center rounded-lg bg-gray-100 border border-gray-300 px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed">
                                Next
                            </span>
                        </div>
                    </div>
                </div>
            </div>
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
