<!--
  Evaluation Notifications Page
  Send evaluation reports to department managers
-->
<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import type { BreadcrumbItemType } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import type { AcceptableValue } from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { Badge } from '@/components/ui/badge'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { Separator } from '@/components/ui/separator'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Filter, Eye, Send, ChevronDown, X, Users, Mail, Calendar } from 'lucide-vue-next'

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
            course_type: string  // NEW: Add course_type
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
        performance_level?: string
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
const isMounted = ref(true)

// Forms
const filterForm = useForm({
    department_id: props.filters?.department_id || null,
    course_id: props.filters?.course_id || null,
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    search: props.filters?.search || '',
    performance_level: props.filters?.performance_level || ''
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
        filterForm.start_date || filterForm.end_date || filterForm.search ||
        filterForm.performance_level
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

const activeFilterCount = computed(() => {
    return [filterForm.department_id, filterForm.course_id, filterForm.start_date, filterForm.end_date, filterForm.search, filterForm.performance_level].filter(Boolean).length
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
    if (!isMounted.value) return

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
    const cleanedData: Record<string, any> = {}
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
    if (!isMounted.value) return
    filterForm.reset()
    router.get(route('admin.evaluations.notifications'))
}

function previewNotification() {
    if (!canPreview.value || !isMounted.value) {
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
    if (!props.preview || !isMounted.value) return

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
    if (score >= 13) return { label: 'Outstanding', variant: 'success', color: 'bg-green-100 text-green-800' }
    if (score >= 10) return { label: 'Reliable', variant: 'primary', color: 'bg-blue-100 text-blue-800' }
    if (score >= 7) return { label: 'Developing', variant: 'warning', color: 'bg-yellow-100 text-yellow-800' }
    if (score >= 0) return { label: 'Underperforming', variant: 'destructive', color: 'bg-red-100 text-red-800' }
    return { label: 'Not Rated', variant: 'outline', color: 'bg-gray-100 text-gray-800' }
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
    if (!isMounted.value) return

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

// Handle department filter change
const handleDepartmentChange = (value: AcceptableValue) => {
    if (!isMounted.value) return
    filterForm.department_id = value === 'none' ? null : parseInt(value as string)
}

// Handle course filter change
const handleCourseChange = (value: AcceptableValue) => {
    if (!isMounted.value) return
    filterForm.course_id = value === 'none' ? null : parseInt(value as string)
}

// Handle target level changes
const handleTargetLevelChange = (level: string, checked: boolean) => {
    if (!isMounted.value) return

    if (checked) {
        if (!targetLevels.value.includes(level)) {
            targetLevels.value.push(level)
        }
    } else {
        targetLevels.value = targetLevels.value.filter(l => l !== level)
    }
}

// Handle employee selection
const handleEmployeeSelection = (employeeId: number, checked: boolean) => {
    if (!isMounted.value) return

    if (checked) {
        if (!selectedEmployees.value.includes(employeeId)) {
            selectedEmployees.value.push(employeeId)
        }
    } else {
        selectedEmployees.value = selectedEmployees.value.filter(id => id !== employeeId)
    }
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Evaluations', href: route('admin.evaluations.index') },
    { name: 'Notifications', href: route('admin.evaluations.notifications') }
]

// Cleanup on unmount
onUnmounted(() => {
    isMounted.value = false
})
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Header -->
            <div>
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-foreground">Evaluation Notifications</h1>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Send evaluation reports to department managers
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <Button
                            @click="showFilters = !showFilters"
                            variant="outline"
                            class="relative"
                            :class="hasActiveFilters ? 'border-primary text-primary' : ''"
                        >
                            <Filter class="mr-2 h-4 w-4" />
                            Filters
                            <Badge v-if="hasActiveFilters" variant="secondary" class="ml-2">
                                {{ activeFilterCount }}
                            </Badge>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Filters Panel -->
            <Collapsible v-model:open="showFilters">
                <CollapsibleContent>
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle>Filter L1 Employees</CardTitle>
                                <div class="flex space-x-2">
                                    <Button
                                        v-if="hasActiveFilters"
                                        @click="clearFilters"
                                        variant="outline"
                                        size="sm"
                                    >
                                        Clear All
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                                <!-- Department Filter -->
                                <div class="space-y-2">
                                    <Label>Department</Label>
                                    <Select :model-value="filterForm.department_id?.toString() || 'none'" @update:model-value="handleDepartmentChange">
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Departments" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">All Departments</SelectItem>
                                            <SelectItem v-for="dept in safeDepartments" :key="dept.id" :value="dept.id.toString()">
                                                {{ dept.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <!-- Course Filter -->
                                <div class="space-y-2">
                                    <Label>Course</Label>
                                    <Select :model-value="filterForm.course_id?.toString() || 'none'" @update:model-value="handleCourseChange">
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Courses" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">All Courses</SelectItem>
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

                                <!-- Search Filter -->
                                <div class="space-y-2">
                                    <Label>Search Employee</Label>
                                    <Input
                                        type="text"
                                        v-model="filterForm.search"
                                        placeholder="Search by name or email"
                                    />
                                </div>

                                <!-- Performance Level Filter -->
                                <div class="space-y-2">
                                    <Label>Performance Level</Label>
                                    <Select v-model="filterForm.performance_level">
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Levels" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">All Levels</SelectItem>
                                            <SelectItem value="outstanding">Outstanding</SelectItem>
                                            <SelectItem value="reliable">Reliable</SelectItem>
                                            <SelectItem value="developing">Developing</SelectItem>
                                            <SelectItem value="underperforming">Underperforming</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end">
                                <Button
                                    @click="applyFilters"
                                    :disabled="filterForm.processing"
                                >
                                    <span v-if="filterForm.processing">Applying...</span>
                                    <span v-else>Apply Filters</span>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </CollapsibleContent>
            </Collapsible>

            <!-- Action Panel -->
            <Card v-if="safeEmployees.length > 0">
                <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div>
                                <p class="text-sm text-muted-foreground">
                                    {{ selectedEmployees.length }} of {{ safeEmployees.length }} employees selected
                                </p>
                            </div>

                            <!-- Target Manager Levels -->
                            <div class="flex items-center space-x-2">
                                <Label class="text-sm font-medium">Send to:</Label>
                                <div class="flex space-x-4">
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="l2"
                                            :checked="targetLevels.includes('L2')"
                                            @update:checked="(checked) => handleTargetLevelChange('L2', checked)"
                                        />
                                        <Label for="l2" class="text-sm">L2 Managers</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="l3"
                                            :checked="targetLevels.includes('L3')"
                                            @update:checked="(checked) => handleTargetLevelChange('L3', checked)"
                                        />
                                        <Label for="l3" class="text-sm">L3 Managers</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="l4"
                                            :checked="targetLevels.includes('L4')"
                                            @update:checked="(checked) => handleTargetLevelChange('L4', checked)"
                                        />
                                        <Label for="l4" class="text-sm">L4 Managers</Label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <Button
                                @click="previewNotification"
                                :disabled="!canPreview || previewForm.processing"
                                variant="outline"
                            >
                                <Eye class="mr-2 h-4 w-4" />
                                Preview Notification
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Employee List -->
            <Card>
                <!-- List Header -->
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>L1 Employees with Evaluations</CardTitle>
                        <div class="text-sm text-muted-foreground">
                            {{ safeEmployees.length }} employees found
                        </div>
                    </div>
                </CardHeader>

                <!-- Empty State -->
                <CardContent v-if="safeEmployees.length === 0" class="p-8 text-center">
                    <Users class="mx-auto h-12 w-12 text-muted-foreground" />
                    <h3 class="mt-4 text-lg font-medium text-foreground">No L1 Employees Found</h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ hasActiveFilters ? 'No employees match your filter criteria.' : 'There are no L1 employees with evaluations.' }}
                    </p>
                    <div v-if="hasActiveFilters" class="mt-4">
                        <Button @click="clearFilters">
                            Clear Filters
                        </Button>
                    </div>
                </CardContent>

                <!-- Employee Table -->
                <div v-else>
                    <!-- Table Header -->
                    <div class="px-6 py-3 bg-muted border-b">
                        <div class="flex items-center space-x-3">
                            <Checkbox
                                id="select-all"
                                :checked="allSelected"
                                :indeterminate="someSelected"
                                @update:checked="toggleSelectAll"
                            />
                            <Label for="select-all" class="text-sm font-medium">
                                Select All ({{ safeEmployees.length }})
                            </Label>
                        </div>
                    </div>

                    <!-- Employee Rows -->
                    <div class="divide-y">
                        <div
                            v-for="employee in safeEmployees"
                            :key="employee.id"
                            class="p-6 hover:bg-muted/50 transition-colors duration-200"
                            :class="{ 'bg-primary/5': selectedEmployees.includes(employee.id) }"
                        >
                            <div class="flex items-start space-x-4">
                                <!-- Checkbox -->
                                <Checkbox
                                    :id="`emp-${employee.id}`"
                                    :checked="selectedEmployees.includes(employee.id)"
                                    @update:checked="(checked) => handleEmployeeSelection(employee.id, checked)"
                                    class="mt-1"
                                />

                                <!-- Employee Info -->
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-lg font-semibold text-foreground">{{ employee.name }}</h4>
                                            <p class="text-sm text-muted-foreground">{{ employee.email }}</p>
                                        </div>
                                        <div>
                                            <Badge variant="outline">{{ employee.level }}</Badge>
                                        </div>
                                    </div>

                                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <span class="font-medium text-foreground">Department:</span>
                                            <span class="text-muted-foreground ml-1">{{ employee.department }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-foreground">Evaluations:</span>
                                            <span class="text-muted-foreground ml-1">{{ employee.evaluation_count }}</span>
                                        </div>
                                        <div v-if="employee.latest_evaluation">
                                            <span class="font-medium text-foreground">Latest:</span>
                                            <span class="text-muted-foreground ml-1">{{ employee.latest_evaluation.created_at }}</span>
                                        </div>
                                    </div>

                                    <!-- Latest Evaluation Details -->
                                    <Card v-if="employee.latest_evaluation" class="mt-3">
                                        <CardContent class="p-3">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <p class="text-sm font-medium text-foreground">
                                                            {{ employee.latest_evaluation.course_name }}
                                                        </p>
                                                        <!-- Course Type Badge -->
                                                        <Badge
                                                            :variant="employee.latest_evaluation.course_type === 'Online' ? 'default' : 'secondary'"
                                                            class="text-xs"
                                                        >
                                                            {{ employee.latest_evaluation.course_type }}
                                                        </Badge>
                                                    </div>
                                                    <p class="text-xs text-muted-foreground">Latest evaluation</p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <!-- Performance Level Badge -->
                                                    <Badge
                                                        :class="getPerformanceLevel(employee.latest_evaluation.total_score).color"
                                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                                    >
                                                        {{ getPerformanceLevel(employee.latest_evaluation.total_score).label }}
                                                    </Badge>
                                                    
                                                    <!-- Score Display -->
                                                    <span class="text-sm font-medium">
                                                        {{ employee.latest_evaluation.total_score }} points
                                                    </span>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Recent Notifications -->
            <Card v-if="safeRecentNotifications.length > 0">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Recent Notifications</CardTitle>
                        <Button :as="Link" :href="route('admin.evaluations.notifications.history')" variant="link" size="sm">
                            View All →
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="divide-y">
                        <div
                            v-for="notification in safeRecentNotifications"
                            :key="notification.id"
                            class="py-4 first:pt-0 last:pb-0 hover:bg-muted/50 -mx-6 px-6 rounded-lg transition-colors duration-200"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-foreground">{{ notification.name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ notification.department_name }} •
                                        {{ notification.employee_count }} employees •
                                        {{ notification.managers_notified }} managers ({{ notification.target_manager_level }})
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Sent by {{ notification.sent_by }} on {{ notification.sent_at }}
                                    </p>
                                </div>
                                <Badge :variant="notification.status === 'sent' ? 'default' : 'secondary'">
                                    {{ notification.status_label }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Preview Modal - FIXED: Removed DialogOverlay and improved z-index -->
        <Dialog v-model:open="showPreviewModal">
            <DialogContent v-if="props.preview" class="sm:max-w-4xl z-[100]">
                <DialogHeader>
                    <DialogTitle>Preview Notification</DialogTitle>
                    <DialogDescription>
                        Review the notification details before sending
                    </DialogDescription>
                </DialogHeader>

                <!-- Summary -->
                <Alert>
                    <Mail class="h-4 w-4" />
                    <AlertDescription>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="font-medium">Employees:</span>
                                <span class="ml-1">{{ props.preview.summary.total_employees }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Managers:</span>
                                <span class="ml-1">{{ props.preview.summary.total_managers }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Departments:</span>
                                <span class="ml-1">{{ props.preview.summary.departments.join(', ') }}</span>
                            </div>
                            <div>
                                <span class="font-medium">Levels:</span>
                                <span class="ml-1">{{ props.preview.summary.target_levels.join(', ') }}</span>
                            </div>
                        </div>
                    </AlertDescription>
                </Alert>

                <!-- Managers who will receive emails -->
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <div v-for="(managers, level) in props.preview.managers" :key="level">
                        <div v-if="managers.length > 0">
                            <h4 class="font-medium text-foreground mb-2">{{ level }} Managers ({{ managers.length }})</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <Card
                                    v-for="manager in managers"
                                    :key="manager.id"
                                    class="p-3"
                                >
                                    <div>
                                        <p class="font-medium text-foreground">{{ manager.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ manager.email }}</p>
                                        <p class="text-xs text-muted-foreground">{{ manager.departments?.join(', ') }}</p>
                                    </div>
                                </Card>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button @click="closePreviewModal" variant="outline">
                        Cancel
                    </Button>
                    <Button @click="showSendModal = true">
                        <Send class="mr-2 h-4 w-4" />
                        Send Notifications
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Send Modal - FIXED: Improved z-index -->
        <Dialog v-model:open="showSendModal">
            <DialogContent class="sm:max-w-lg z-[110]">
                <form @submit.prevent="sendNotifications">
                    <DialogHeader>
                        <DialogTitle>Send Notifications</DialogTitle>
                        <DialogDescription>
                            Customize the notification before sending
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4 py-4">
                        <!-- Email Subject -->
                        <div class="space-y-2">
                            <Label for="email-subject">Email Subject</Label>
                            <Input
                                id="email-subject"
                                v-model="sendForm.email_subject"
                                :placeholder="generateEmailSubject()"
                                required
                            />
                        </div>

                        <!-- Custom Message -->
                        <div class="space-y-2">
                            <Label for="custom-message">Custom Message (Optional)</Label>
                            <Textarea
                                id="custom-message"
                                v-model="sendForm.custom_message"
                                rows="3"
                                placeholder="Add any additional message for the managers..."
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button @click="showSendModal = false" type="button" variant="outline">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="sendForm.processing" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                            {{ sendForm.processing ? 'Sending...' : 'Send Now' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
