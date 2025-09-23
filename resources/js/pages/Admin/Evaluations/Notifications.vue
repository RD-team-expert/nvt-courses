<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import type { BreadcrumbItemType } from '@/types'

const props = defineProps<{
    employees?: Array<{
        id: number
        name: string
        email: string
        department: string
        department_id: number
        level: string
        evaluation_count: number
        latest_evaluation?: {
            id: number
            course_name: string
            total_score: number
            incentive_amount: number
            created_at: string
        }
    }>
    departments?: Array<{
        id: number
        name: string
    }>
    courses?: Array<{
        id: number
        name: string
    }>
    filters?: {
        department_id?: number
        course_id?: number
        start_date?: string
        end_date?: string
        search?: string
    }
    recentNotifications?: Array<{
        id: number
        name: string
        department_name: string
        employee_count: number
        managers_notified: number
        target_manager_level: string
        status: string
        status_label: string
        status_class: string
        sent_by: string
        sent_at: string
    }>
    preview?: {
        employees: Array<any>
        managers: { L2: Array<any>, L3: Array<any>, L4: Array<any> }
        summary: {
            total_employees: number
            total_managers: number
            departments: Array<string>
            target_levels: Array<string>
        }
        email_subject?: string
    }
    showPreview?: boolean
}>()

// State management
const selectedEmployees = ref<number[]>([])
const showFilters = ref(false)
const showPreviewModal = ref(false)
const showSendModal = ref(false)
const targetLevels = ref<string[]>(['L2'])

// Forms
const filterForm = useForm({
    department_id: props.filters?.department_id || null,
    course_id: props.filters?.course_id || null,
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    search: props.filters?.search || '',
    performance_level: ''
})

const previewForm = useForm({
    employee_ids: [] as number[],
    target_manager_levels: [] as string[],
    email_subject: ''
})

const sendForm = useForm({
    employee_ids: [] as number[],
    target_manager_levels: [] as string[],
    email_subject: '',
    custom_message: ''
})

// Safe defaults
const safeEmployees = computed(() => props.employees || [])
const safeDepartments = computed(() => props.departments || [])
const safeCourses = computed(() => props.courses || [])
const safeRecentNotifications = computed(() => props.recentNotifications || [])

// Computed values
const hasActiveFilters = computed(() => {
    return filterForm.department_id || filterForm.course_id ||
        filterForm.start_date || filterForm.end_date || filterForm.search
})

const allSelected = computed(() => {
    return safeEmployees.value.length > 0 &&
        selectedEmployees.value.length === safeEmployees.value.length
})

const someSelected = computed(() => {
    return selectedEmployees.value.length > 0 &&
        selectedEmployees.value.length < safeEmployees.value.length
})

const canPreview = computed(() => {
    return selectedEmployees.value.length > 0 && targetLevels.value.length > 0
})

// Watch for showPreview prop change
if (props.showPreview && props.preview) {
    showPreviewModal.value = true
}

// Methods
function toggleSelectAll() {
    if (allSelected.value) {
        selectedEmployees.value = []
    } else {
        selectedEmployees.value = safeEmployees.value.map(emp => emp.id)
    }
}

function applyFilters() {
    console.log('Starting filter application...')
    console.log('Current filter form data:', filterForm.data)

    // FIXED: Convert reactive form data to plain object and filter out empty values
    const filterData = {
        department_id: filterForm.department_id || null,
        course_id: filterForm.course_id || null,
        start_date: filterForm.start_date || '',
        end_date: filterForm.end_date || '',
        search: filterForm.search || '',
        performance_level: filterForm.performance_level || ''
    }

    // Remove empty/null values to avoid Axios serialization issues
    const cleanedData = {}
    for (const [key, value] of Object.entries(filterData)) {
        if (value !== null && value !== '' && value !== undefined) {
            cleanedData[key] = value
        }
    }

    console.log('Cleaned filter data:', cleanedData)

    // Use GET request with clean parameters (FIXED)
    router.get(route('admin.evaluations.notifications'), cleanedData, {
        preserveState: true,
        preserveScroll: true,
        onStart: () => {
            console.log('Filter request started')
        },
        onSuccess: () => {
            console.log('Filter request successful')
        },
        onError: (errors) => {
            console.error('Filter errors:', errors)
        },
        onFinish: () => {
            console.log('Filter request finished')
        }
    })
}

function clearFilters() {
    filterForm.reset()
    router.get(route('admin.evaluations.notifications'))
}

function previewNotification() {
    if (!canPreview.value) {
        console.log('Cannot preview - missing requirements')
        console.log('Selected employees:', selectedEmployees.value)
        console.log('Target levels:', targetLevels.value)
        return
    }

    console.log('Starting preview notification...')
    console.log('Selected employees:', selectedEmployees.value)
    console.log('Target levels:', targetLevels.value)

    previewForm.employee_ids = selectedEmployees.value
    previewForm.target_manager_levels = targetLevels.value
    previewForm.email_subject = generateEmailSubject()

    console.log('Form data:', previewForm.data)
    console.log('Route URL:', route('admin.evaluations.notifications.preview'))

    previewForm.post(route('admin.evaluations.notifications.preview'), {
        preserveState: true,
        onStart: () => {
            console.log('Request started')
        },
        onSuccess: (page) => {
            console.log('Request successful')
            console.log('Page props:', page.props)
            console.log('Preview prop:', page.props.preview)
            console.log('ShowPreview prop:', page.props.showPreview)

            // Force modal to show
            if (page.props.preview) {
                showPreviewModal.value = true
                console.log('Modal should be visible now')
            }
        },
        onError: (errors) => {
            console.error('Preview errors:', errors)
        },
        onFinish: () => {
            console.log('Request finished')
        }
    })
}
function sendNotifications() {
    if (!props.preview) return

    sendForm.employee_ids = selectedEmployees.value
    sendForm.target_manager_levels = targetLevels.value
    sendForm.email_subject = sendForm.email_subject || generateEmailSubject()

    sendForm.post(route('admin.evaluations.notifications.send'), {
        preserveState: false,
        onSuccess: () => {
            console.log('✅ Notifications sent successfully')

            // Close modals first
            showSendModal.value = false
            showPreviewModal.value = false

            // Clear selections
            selectedEmployees.value = []
            targetLevels.value = ['L2']

            // 🎯 REDIRECT BACK TO NOTIFICATIONS PAGE
            router.get(route('admin.evaluations.notifications'), {}, {
                preserveState: false,
                preserveScroll: false,
                replace: true,
                onSuccess: () => {
                    console.log('✅ Successfully redirected to notifications page')
                },
                onError: (errors) => {
                    console.error('❌ Error redirecting:', errors)
                }
            })
        },
        onError: (errors) => {
            console.error('❌ Error sending notifications:', errors)
            // Handle error - maybe show error message
        }
    })
}

function generateEmailSubject(): string {
    if (selectedEmployees.value.length === 0) return 'Evaluation Report'

    const selectedEmp = safeEmployees.value.filter(emp =>
        selectedEmployees.value.includes(emp.id)
    )

    const departments = [...new Set(selectedEmp.map(emp => emp.department))]
    const departmentText = departments.length === 1
        ? departments[0]
        : `${departments.length} Departments`

    return `Evaluation Report - ${departmentText} (${selectedEmployees.value.length} Employees)`
}

function getPerformanceLevel(score: number) {
    if (score >= 20) return { label: 'Exceptional', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' }
    if (score >= 15) return { label: 'Excellent', class: 'bg-green-100 text-green-800 border-green-200' }
    if (score >= 10) return { label: 'Good', class: 'bg-blue-100 text-blue-800 border-blue-200' }
    if (score >= 5) return { label: 'Average', class: 'bg-yellow-100 text-yellow-800 border-yellow-200' }
    return { label: 'Needs Improvement', class: 'bg-red-100 text-red-800 border-red-200' }
}

function formatCurrency(amount: number | string): string {
    // Handle both string and number values from database (FIXED)
    const numAmount = parseFloat(amount?.toString() || '0')

    // Check if conversion was successful
    if (isNaN(numAmount)) {
        return '$0.00'
    }

    return `$${numAmount.toFixed(2)}`
}

function closePreviewModal() {
    console.log('Closing preview modal...')
    showPreviewModal.value = false
    showSendModal.value = false

    // FIXED: Navigate back to the notifications page without preview params
    router.get(route('admin.evaluations.notifications'), {}, {
        preserveState: false,  // Don't preserve state to clear preview data
        preserveScroll: true,
        replace: true,         // Replace the current history entry
        onSuccess: () => {
            console.log('Successfully navigated back to notifications page')
        },
        onError: (errors) => {
            console.error('Error navigating back:', errors)
        }
    })
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Evaluations', href: route('admin.evaluations.index') },
    { name: 'Notifications', href: null }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Evaluation Notifications</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Send evaluation reports to department managers
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <button
                            @click="showFilters = !showFilters"
                            class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            :class="hasActiveFilters
                                ? 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z" />
                            </svg>
                            Filters
                            <span v-if="hasActiveFilters" class="ml-2 inline-flex items-center justify-center w-5 h-5 text-xs bg-indigo-500 text-white rounded-full">
                                {{ [filterForm.department_id, filterForm.course_id, filterForm.start_date, filterForm.end_date, filterForm.search].filter(Boolean).length }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters Panel -->
            <div v-if="showFilters" class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Filter L1 Employees</h3>
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

                    <!-- Search Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Employee</label>
                        <input
                            type="text"
                            v-model="filterForm.search"
                            placeholder="Search by name or email"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button
                        @click="applyFilters"
                        type="button"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        :disabled="filterForm.processing"
                    >
                        <svg v-if="filterForm.processing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        <span v-if="filterForm.processing">Applying...</span>
                        <span v-else>Apply Filters</span>
                    </button>
                </div>
            </div>

            <!-- Action Panel -->
            <div v-if="safeEmployees.length > 0" class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div>
                            <p class="text-sm text-gray-600">
                                {{ selectedEmployees.length }} of {{ safeEmployees.length }} employees selected
                            </p>
                        </div>

                        <!-- Target Manager Levels -->
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700">Send to:</label>
                            <div class="flex space-x-2">
                                <label class="inline-flex items-center">
                                    <input
                                        type="checkbox"
                                        value="L2"
                                        v-model="targetLevels"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">L2 Managers</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input
                                        type="checkbox"
                                        value="L3"
                                        v-model="targetLevels"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">L3 Managers</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input
                                        type="checkbox"
                                        value="L4"
                                        v-model="targetLevels"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">L4 Managers</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <button
                            @click="previewNotification"
                            :disabled="!canPreview || previewForm.processing"
                            class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Preview Notification
                        </button>
                    </div>
                </div>
            </div>

            <!-- Employee List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <!-- List Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">L1 Employees with Evaluations</h3>
                        <div class="text-sm text-gray-500">
                            {{ safeEmployees.length }} employees found
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="safeEmployees.length === 0" class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No L1 Employees Found</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ hasActiveFilters ? 'No employees match your filter criteria.' : 'There are no L1 employees with evaluations.' }}
                    </p>
                    <div v-if="hasActiveFilters" class="mt-4">
                        <button
                            @click="clearFilters"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-indigo-700"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Employee Table -->
                <div v-else>
                    <!-- Table Header -->
                    <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                :checked="allSelected"
                                :indeterminate="someSelected"
                                @change="toggleSelectAll"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <label class="ml-3 text-sm font-medium text-gray-700">
                                Select All ({{ safeEmployees.length }})
                            </label>
                        </div>
                    </div>

                    <!-- Employee Rows -->
                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="employee in safeEmployees"
                            :key="employee.id"
                            class="px-6 py-4 hover:bg-gray-50 transition-colors duration-200"
                            :class="{ 'bg-blue-50': selectedEmployees.includes(employee.id) }"
                        >
                            <div class="flex items-start space-x-4">
                                <!-- Checkbox -->
                                <input
                                    type="checkbox"
                                    :value="employee.id"
                                    v-model="selectedEmployees"
                                    class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />

                                <!-- Employee Info -->
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-900">{{ employee.name }}</h4>
                                            <p class="text-sm text-gray-600">{{ employee.email }}</p>
                                        </div>
                                        <div class="text-right">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ employee.level }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <span class="font-medium text-gray-700">Department:</span>
                                            <span class="text-gray-600">{{ employee.department }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700">Evaluations:</span>
                                            <span class="text-gray-600">{{ employee.evaluation_count }}</span>
                                        </div>
                                        <div v-if="employee.latest_evaluation">
                                            <span class="font-medium text-gray-700">Latest:</span>
                                            <span class="text-gray-600">{{ employee.latest_evaluation.created_at }}</span>
                                        </div>
                                    </div>

                                    <!-- Latest Evaluation Details -->
                                    <div v-if="employee.latest_evaluation" class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ employee.latest_evaluation.course_name }}</p>
                                                <p class="text-xs text-gray-600">Latest evaluation</p>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <div class="text-right">
                                                    <div class="text-lg font-bold text-gray-900">{{ employee.latest_evaluation.total_score }}</div>
                                                    <div class="text-xs text-gray-500">Score</div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-lg font-bold text-green-600">{{ formatCurrency(employee.latest_evaluation.incentive_amount) }}</div>
                                                    <div class="text-xs text-gray-500">Incentive</div>
                                                </div>
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border"
                                                    :class="getPerformanceLevel(employee.latest_evaluation.total_score).class"
                                                >
                                                    {{ getPerformanceLevel(employee.latest_evaluation.total_score).label }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Notifications -->
            <div v-if="safeRecentNotifications.length > 0" class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Notifications</h3>
                        <Link
                            :href="route('admin.evaluations.notifications.history')"
                            class="text-sm text-indigo-600 hover:text-indigo-500"
                        >
                            View All →
                        </Link>
                    </div>
                </div>

                <div class="divide-y divide-gray-200">
                    <div
                        v-for="notification in safeRecentNotifications"
                        :key="notification.id"
                        class="px-6 py-4 hover:bg-gray-50 transition-colors duration-200"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ notification.name }}</p>
                                <p class="text-sm text-gray-600">
                                    {{ notification.department_name }} •
                                    {{ notification.employee_count }} employees •
                                    {{ notification.managers_notified }} managers ({{ notification.target_manager_level }})
                                </p>
                                <p class="text-xs text-gray-500">
                                    Sent by {{ notification.sent_by }} on {{ notification.sent_at }}
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="notification.status_class"
                            >
                                {{ notification.status_label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal && props.preview" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closePreviewModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <!-- Modal Header -->
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Preview Notification</h3>
                            <button @click="closePreviewModal" class="text-gray-400 hover:text-gray-500">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Summary -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <h4 class="font-medium text-blue-900 mb-2">Summary</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-blue-700">Employees:</span>
                                    <span class="font-medium text-blue-900">{{ props.preview.summary.total_employees }}</span>
                                </div>
                                <div>
                                    <span class="text-blue-700">Managers:</span>
                                    <span class="font-medium text-blue-900">{{ props.preview.summary.total_managers }}</span>
                                </div>
                                <div>
                                    <span class="text-blue-700">Departments:</span>
                                    <span class="font-medium text-blue-900">{{ props.preview.summary.departments.join(', ') }}</span>
                                </div>
                                <div>
                                    <span class="text-blue-700">Levels:</span>
                                    <span class="font-medium text-blue-900">{{ props.preview.summary.target_levels.join(', ') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Managers who will receive emails -->
                        <div class="space-y-4">
                            <div v-for="(managers, level) in props.preview.managers" :key="level">
                                <div v-if="managers.length > 0">
                                    <h4 class="font-medium text-gray-900 mb-2">{{ level }} Managers ({{ managers.length }})</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div
                                            v-for="manager in managers"
                                            :key="manager.id"
                                            class="p-3 bg-gray-50 rounded-lg border border-gray-200"
                                        >
                                            <p class="font-medium text-gray-900">{{ manager.name }}</p>
                                            <p class="text-sm text-gray-600">{{ manager.email }}</p>
                                            <p class="text-xs text-gray-500">{{ manager.departments?.join(', ') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="showSendModal = true"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Send Notifications
                        </button>
                        <button
                            @click="closePreviewModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Send Modal -->
        <div v-if="showSendModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showSendModal = false"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="sendNotifications">
                        <!-- Modal Header -->
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Send Notifications</h3>
                                <button @click="showSendModal = false" type="button" class="text-gray-400 hover:text-gray-500">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Email Subject -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Subject</label>
                                <input
                                    type="text"
                                    v-model="sendForm.email_subject"
                                    :placeholder="generateEmailSubject()"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    required
                                />
                            </div>

                            <!-- Custom Message -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Custom Message (Optional)</label>
                                <textarea
                                    v-model="sendForm.custom_message"
                                    rows="3"
                                    placeholder="Add any additional message for the managers..."
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                :disabled="sendForm.processing"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                            >
                                <svg v-if="sendForm.processing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                {{ sendForm.processing ? 'Sending...' : 'Send Now' }}
                            </button>
                            <button
                                @click="showSendModal = false"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
