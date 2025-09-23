<script setup lang="ts">
import { useForm, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import type { BreadcrumbItemType } from '@/types'

const props = defineProps<{
    users?: Array<{
        id: number
        name: string
        email: string
        department?: { id: number; name: string }
        department_id?: number
        completed_courses: Array<{
            id: number
            title: string
            completed_at: string
        }>
    }>
    categories?: Array<{
        id: number
        name: string
        description: string
        weight: number
        max_score: number
        types: Array<{
            id: number
            type_name: string
            score_value: number
            description: string
        }>
    }>
    departments?: Array<{
        id: number
        name: string
    }>
    courses?: Array<{
        id: number
        title: string
        description: string
    }>
    incentives?: Array<{
        id: number
        min_score: number
        max_score: number
        incentive_amount: number
    }>
}>()

// Provide safe defaults
const safeUsers = computed(() => props.users || [])
const safeCategories = computed(() => props.categories || [])
const safeDepartments = computed(() => props.departments || [])
const safeCourses = computed(() => props.courses || [])
const safeIncentives = computed(() => props.incentives || [])

// State management
const showSubmitModal = ref(false)
const isFormValid = ref(false)
const availableUsers = ref(safeUsers.value)
const availableCourses = ref(safeCourses.value)
const isLoadingUsers = ref(false)
const isLoadingCourses = ref(false)

// Form for user evaluation
const form = useForm({
    user_id: null as number | null,
    course_id: null as number | null,
    department_id: null as number | null,
    evaluation_date: new Date().toISOString().split('T')[0],
    notes: '',
    categories: [] as Array<{
        category_id: number
        evaluation_type_id: number | null
        comments: string
    }>
})

// Initialize form categories when categories prop changes
const initializeFormCategories = () => {
    form.categories = safeCategories.value.map(category => ({
        category_id: category.id,
        evaluation_type_id: null,
        comments: ''
    }))
}

// Watch for categories changes and reinitialize
watch(() => props.categories, () => {
    initializeFormCategories()
}, { immediate: true })

// FIXED: Watch department selection to filter users
watch(() => form.department_id, async (newDepartmentId) => {
    if (newDepartmentId) {
        isLoadingUsers.value = true
        try {
            // FIXED: Convert route to string and create clean params object
            const routeUrl = route('admin.evaluations.users-by-department')
            const params = {
                department_id: newDepartmentId
            }

            console.log('Fetching users for department:', newDepartmentId)
            console.log('Route URL:', routeUrl)

            const response = await axios.get(routeUrl, { params })
            availableUsers.value = response.data.users || []

            console.log('Users loaded:', availableUsers.value.length)
        } catch (error) {
            console.error('Error fetching users by department:', error)
            availableUsers.value = []
        } finally {
            isLoadingUsers.value = false
        }
    } else {
        availableUsers.value = safeUsers.value
    }

    // Reset user and course selection when department changes
    form.user_id = null
    form.course_id = null
    availableCourses.value = safeCourses.value
})

// FIXED: Watch user selection to filter courses
watch(() => form.user_id, async (newUserId) => {
    if (newUserId) {
        isLoadingCourses.value = true
        try {
            // FIXED: Convert route to string and create clean params object
            const routeUrl = route('admin.evaluations.user-courses')
            const params = {
                user_id: newUserId
            }

            console.log('Fetching courses for user:', newUserId)
            console.log('Route URL:', routeUrl)

            const response = await axios.get(routeUrl, { params })
            availableCourses.value = response.data.courses || []

            console.log('Courses loaded:', availableCourses.value.length)
        } catch (error) {
            console.error('Error fetching user courses:', error)
            // Fallback to user's completed courses from props
            const selectedUser = availableUsers.value.find(u => u.id === newUserId)
            availableCourses.value = selectedUser?.completed_courses || []
        } finally {
            isLoadingCourses.value = false
        }
    } else {
        availableCourses.value = safeCourses.value
    }

    // Reset course selection when user changes
    form.course_id = null
})

// Get selected user info
const selectedUser = computed(() => {
    if (!form.user_id) return null
    return availableUsers.value.find(u => u.id === form.user_id) || null
})

// Get selected course info
const selectedCourse = computed(() => {
    if (!form.course_id) return null
    return availableCourses.value.find(c => c.id === form.course_id) || null
})

// Get selected evaluation type for a category
const getSelectedEvaluationType = (categoryId: number, typeId: number | null) => {
    if (!typeId) return null
    const category = safeCategories.value.find(cat => cat.id === categoryId)
    if (!category) return null
    return category.types.find(type => type.id === typeId) || null
}

// Calculate total score (sum of all selected type scores)
const totalScore = computed(() => {
    return form.categories.reduce((sum, evalCat) => {
        const selectedType = getSelectedEvaluationType(evalCat.category_id, evalCat.evaluation_type_id)
        return sum + (selectedType?.score_value || 0)
    }, 0)
})

// Calculate incentive amount based on total score and incentive ranges
const totalIncentiveAmount = computed(() => {
    if (totalScore.value === 0) return 0

    const applicableIncentive = safeIncentives.value.find(incentive =>
        totalScore.value >= incentive.min_score && totalScore.value <= incentive.max_score
    )

    return applicableIncentive ? parseFloat(applicableIncentive.incentive_amount.toString()) : 0
})

// Get incentive tier info for display
const getIncentiveTierInfo = computed(() => {
    if (totalScore.value === 0) return null

    const incentive = safeIncentives.value.find(inc =>
        totalScore.value >= inc.min_score && totalScore.value <= inc.max_score
    )

    if (!incentive) return null

    return {
        range: `${incentive.min_score}-${incentive.max_score}`,
        amount: parseFloat(incentive.incentive_amount.toString()),
        tier: getTierName(incentive.min_score, incentive.max_score)
    }
})

// Get tier name based on position in sorted incentives array
const getTierName = (minScore: number, maxScore: number) => {
    const sortedIncentives = [...safeIncentives.value].sort((a, b) => b.min_score - a.min_score)
    const currentIncentive = sortedIncentives.find(inc =>
        inc.min_score === minScore && inc.max_score === maxScore
    )

    if (!currentIncentive) return 'Unknown Tier'

    const tierIndex = sortedIncentives.findIndex(inc =>
        inc.min_score === minScore && inc.max_score === maxScore
    )

    const tierNames = [
        'Exceptional Tier',
        'Excellent Tier',
        'Good Tier',
        'Average Tier',
        'Below Average Tier',
        'Poor Tier',
        'Very Poor Tier'
    ]

    if (tierIndex < tierNames.length) {
        return tierNames[tierIndex]
    }

    return `Tier ${tierIndex + 1}`
}

// Get performance level based on score
const getPerformanceLevel = (score: number) => {
    if (score >= 13) return { label: 'Excellent', class: 'text-emerald-700 bg-emerald-100 border-emerald-200' }
    if (score >= 10) return { label: 'Good', class: 'text-green-700 bg-green-100 border-green-200' }
    if (score >= 7) return { label: 'Average', class: 'text-blue-700 bg-blue-100 border-blue-200' }
    if (score >= 4) return { label: 'Below Average', class: 'text-yellow-700 bg-yellow-100 border-yellow-200' }
    if (score >= 1) return { label: 'Poor', class: 'text-red-700 bg-red-100 border-red-200' }
    return { label: 'No Score', class: 'text-gray-700 bg-gray-100 border-gray-200' }
}

// Get type color based on score value
const getTypeColor = (scoreValue: number, maxScore: number) => {
    const percentage = (scoreValue / maxScore) * 100
    if (percentage >= 90) return 'bg-emerald-100 text-emerald-800 border-emerald-200'
    if (percentage >= 80) return 'bg-green-100 text-green-800 border-green-200'
    if (percentage >= 70) return 'bg-blue-100 text-blue-800 border-blue-200'
    if (percentage >= 60) return 'bg-yellow-100 text-yellow-800 border-yellow-200'
    if (percentage >= 40) return 'bg-orange-100 text-orange-800 border-orange-200'
    return 'bg-red-100 text-red-800 border-red-200'
}

// Validate form
const validateForm = () => {
    isFormValid.value = !!(
        form.user_id &&
        form.course_id &&
        form.evaluation_date &&
        form.categories.length > 0 &&
        form.categories.every(cat => cat.evaluation_type_id !== null)
    )
}

// Submit evaluation
function submit() {
    validateForm()
    if (!isFormValid.value) return

    showSubmitModal.value = true
}

function confirmSubmit() {
    form.post(route('admin.evaluations.user-evaluation.store'), {
        onSuccess: () => {
            showSubmitModal.value = false
            form.reset()
            availableUsers.value = safeUsers.value
            availableCourses.value = safeCourses.value
            initializeFormCategories()
        },
        onError: (errors) => {
            showSubmitModal.value = false
            console.error('Validation errors:', errors)
        }
    })
}

// Reset form
function resetForm() {
    form.reset()
    availableUsers.value = safeUsers.value
    availableCourses.value = safeCourses.value
    initializeFormCategories()
}

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Evaluations', href: route('admin.evaluations.index') },
    { name: 'User Evaluation', href: null }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">User Performance Evaluation</h1>
                        <p class="mt-2 text-sm text-gray-600">Assess employee performance for completed courses using predefined evaluation criteria.</p>
                    </div>
                    <Link
                        :href="route('admin.evaluations.index')"
                        class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition-colors duration-200 hover:bg-gray-200"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Evaluations
                    </Link>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- User Selection Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-blue-100">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-semibold text-gray-900">Select Department, Employee & Course</h2>
                            <p class="text-sm text-gray-600">Choose department first, then employee, and finally their completed course</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Department Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Department
                            </label>
                            <select
                                v-model="form.department_id"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                :disabled="form.processing"
                            >
                                <option :value="null">All Departments</option>
                                <option v-for="dept in safeDepartments" :key="dept.id" :value="dept.id">
                                    {{ dept.name }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                Filter employees by department
                            </p>
                        </div>

                        <!-- User Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Employee <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select
                                    v-model="form.user_id"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    :disabled="form.processing || isLoadingUsers"
                                    required
                                    @change="validateForm"
                                >
                                    <option :value="null">
                                        {{ isLoadingUsers ? 'Loading users...' : 'Choose an employee...' }}
                                    </option>
                                    <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                        {{ user.department ? `(${user.department.name})` : '' }}
                                    </option>
                                </select>
                                <div v-if="isLoadingUsers" class="absolute right-3 top-2.5">
                                    <svg class="animate-spin h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                </div>
                            </div>
                            <p v-if="!form.department_id" class="mt-1 text-xs text-gray-500">
                                Showing all employees
                            </p>
                            <p v-else-if="availableUsers.length === 0 && !isLoadingUsers" class="mt-1 text-xs text-yellow-600">
                                No employees found in this department
                            </p>
                            <span v-if="form.errors.user_id" class="mt-1 flex items-center text-sm text-red-600">
                                {{ form.errors.user_id }}
                            </span>
                        </div>

                        <!-- Course Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Completed Course <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select
                                    v-model="form.course_id"
                                    class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    :disabled="form.processing || !form.user_id || isLoadingCourses"
                                    required
                                    @change="validateForm"
                                >
                                    <option :value="null">
                                        {{
                                            !form.user_id ? 'Select employee first...' :
                                                isLoadingCourses ? 'Loading courses...' :
                                                    'Choose a completed course...'
                                        }}
                                    </option>
                                    <option v-for="course in availableCourses" :key="course.id" :value="course.id">
                                        {{ course.title }}
                                    </option>
                                </select>
                                <div v-if="isLoadingCourses" class="absolute right-3 top-2.5">
                                    <svg class="animate-spin h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                </div>
                            </div>
                            <p v-if="!form.user_id" class="mt-1 text-xs text-gray-500">
                                Select an employee to see their completed courses
                            </p>
                            <p v-else-if="availableCourses.length === 0 && !isLoadingCourses" class="mt-1 text-xs text-yellow-600">
                                This employee hasn't completed any courses yet
                            </p>
                            <span v-if="form.errors.course_id" class="mt-1 flex items-center text-sm text-red-600">
                                {{ form.errors.course_id }}
                            </span>
                        </div>

                        <!-- Evaluation Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Evaluation Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                v-model="form.evaluation_date"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                :disabled="form.processing"
                                required
                            />
                            <span v-if="form.errors.evaluation_date" class="mt-1 flex items-center text-sm text-red-600">
                                {{ form.errors.evaluation_date }}
                            </span>
                        </div>
                    </div>

                    <!-- Selected User and Course Info -->
                    <div v-if="selectedUser && selectedCourse" class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="font-medium text-blue-900 mb-3 flex items-center">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Evaluation Details
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-blue-900">Employee:</span>
                                <p class="text-blue-800">{{ selectedUser.name }}</p>
                            </div>
                            <div>
                                <span class="font-medium text-blue-900">Course:</span>
                                <p class="text-blue-800">{{ selectedCourse.title }}</p>
                            </div>
                            <div v-if="selectedCourse.completed_at">
                                <span class="font-medium text-blue-900">Completed:</span>
                                <p class="text-blue-800">{{ new Date(selectedCourse.completed_at).toLocaleDateString() }}</p>
                            </div>
                            <div v-if="selectedUser.department">
                                <span class="font-medium text-blue-900">Department:</span>
                                <p class="text-blue-800">{{ selectedUser.department.name }}</p>
                            </div>
                            <div>
                                <span class="font-medium text-blue-900">Email:</span>
                                <p class="text-blue-800">{{ selectedUser.email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Evaluation Categories -->
                <div v-if="form.user_id && form.course_id && safeCategories.length > 0" class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">Evaluation Categories</h2>
                        <span class="text-sm text-gray-500">{{ safeCategories.length }} categories</span>
                    </div>

                    <div class="grid gap-6">
                        <div
                            v-for="(evalCategory, index) in form.categories"
                            :key="evalCategory.category_id"
                            class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200"
                        >
                            <div class="flex items-start justify-between mb-6">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            {{ safeCategories.find(cat => cat.id === evalCategory.category_id)?.name || 'Category' }}
                                        </h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                            {{ safeCategories.find(cat => cat.id === evalCategory.category_id)?.weight || 0 }}% weight
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        {{ safeCategories.find(cat => cat.id === evalCategory.category_id)?.description || 'No description available' }}
                                    </p>
                                </div>

                                <!-- Score Display -->
                                <div v-if="getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)" class="text-right">
                                    <div class="text-2xl font-bold text-gray-900">
                                        {{ getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        out of {{ safeCategories.find(cat => cat.id === evalCategory.category_id)?.max_score }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Evaluation Type Selection -->
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-3">
                                            Select Performance Level
                                        </label>
                                        <div class="space-y-2">
                                            <div
                                                v-for="type in safeCategories.find(cat => cat.id === evalCategory.category_id)?.types || []"
                                                :key="type.id"
                                                class="relative"
                                            >
                                                <input
                                                    :id="`type_${evalCategory.category_id}_${type.id}`"
                                                    v-model="evalCategory.evaluation_type_id"
                                                    :value="type.id"
                                                    type="radio"
                                                    :name="`category_${evalCategory.category_id}`"
                                                    class="sr-only"
                                                    @change="validateForm"
                                                />
                                                <label
                                                    :for="`type_${evalCategory.category_id}_${type.id}`"
                                                    class="flex items-center justify-between p-4 border rounded-lg cursor-pointer transition-all duration-200 hover:bg-gray-50"
                                                    :class="{
                                                        'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200': evalCategory.evaluation_type_id === type.id,
                                                        'border-gray-200': evalCategory.evaluation_type_id !== type.id
                                                    }"
                                                >
                                                    <div class="flex items-center space-x-3">
                                                        <div class="flex-shrink-0">
                                                            <div
                                                                class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                                                                :class="{
                                                                    'border-indigo-500 bg-indigo-500': evalCategory.evaluation_type_id === type.id,
                                                                    'border-gray-300': evalCategory.evaluation_type_id !== type.id
                                                                }"
                                                            >
                                                                <div v-if="evalCategory.evaluation_type_id === type.id" class="w-2 h-2 bg-white rounded-full"></div>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <p class="font-medium text-gray-900">{{ type.type_name }}</p>
                                                            <p class="text-sm text-gray-600">{{ type.description }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        <span
                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                            :class="getTypeColor(type.score_value, safeCategories.find(cat => cat.id === evalCategory.category_id)?.max_score || 100)"
                                                        >
                                                            {{ type.score_value }} pts
                                                        </span>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        <span v-if="form.errors[`categories.${index}.evaluation_type_id`]" class="mt-2 flex items-center text-sm text-red-600">
                                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ form.errors[`categories.${index}.evaluation_type_id`] }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Selected Performance Info -->
                                <div v-if="getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)" class="space-y-4">
                                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                                            <svg class="mr-2 h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Selected Performance Level
                                        </h4>
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-gray-700">Performance:</span>
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                    :class="getPerformanceLevel(getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0).class"
                                                >
                                                    {{ getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.type_name }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-gray-700">Score:</span>
                                                <span class="text-sm font-bold text-gray-900">
                                                    {{ getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value }} /
                                                    {{ safeCategories.find(cat => cat.id === evalCategory.category_id)?.max_score }}
                                                </span>
                                            </div>

                                            <!-- Score Bar -->
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    class="h-2 rounded-full transition-all duration-300"
                                                    :class="{
                                                        'bg-emerald-500': (getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0) >= 90,
                                                        'bg-green-500': (getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0) >= 80 && (getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0) < 90,
                                                        'bg-blue-500': (getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0) >= 70 && (getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0) < 80,
                                                        'bg-yellow-500': (getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0) >= 60 && (getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0) < 70,
                                                        'bg-orange-500': (getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0) >= 40 && (getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0) < 60,
                                                        'bg-red-500': (getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0) < 40
                                                    }"
                                                    :style="`width: ${Math.round(((getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0) / (safeCategories.find(cat => cat.id === evalCategory.category_id)?.max_score || 100)) * 100)}%`"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Comments Section -->
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Detailed Comments & Feedback
                                </label>
                                <textarea
                                    v-model="evalCategory.comments"
                                    rows="3"
                                    class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 resize-none"
                                    :disabled="form.processing"
                                    placeholder="Provide specific feedback, strengths, areas for improvement, and recommendations..."
                                />
                                <span v-if="form.errors[`categories.${index}.comments`]" class="mt-1 flex items-center text-sm text-red-600">
                                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ form.errors[`categories.${index}.comments`] }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overall Assessment Summary -->
                <div v-if="form.user_id && form.course_id && form.categories.some(cat => cat.evaluation_type_id !== null)" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-green-100">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-semibold text-gray-900">Overall Assessment</h3>
                            <p class="text-sm text-gray-600">Summary of evaluation results and incentive information</p>
                        </div>
                    </div>

                    <!-- Score Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-900">Total Score</p>
                                    <p class="text-2xl font-bold"
                                       :class="{
                                        'text-emerald-600': totalScore >= 13,
                                        'text-green-600': totalScore >= 10 && totalScore < 13,
                                        'text-blue-600': totalScore >= 7 && totalScore < 10,
                                        'text-yellow-600': totalScore >= 4 && totalScore < 7,
                                        'text-red-600': totalScore < 4
                                       }">
                                        {{ totalScore }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-900">Incentive Amount</p>
                                    <p class="text-2xl font-bold text-green-600">
                                        ${{ totalIncentiveAmount.toFixed(2) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-purple-900">Performance Rating</p>
                                    <p class="text-lg font-bold text-purple-600">
                                        {{ getPerformanceLevel(totalScore).label }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Incentive Tier Info -->
                    <div v-if="getIncentiveTierInfo" class="mb-6 p-4 bg-gradient-to-r from-yellow-50 to-amber-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="h-6 w-6 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <div>
                                <h4 class="font-semibold text-yellow-900">{{ getIncentiveTierInfo.tier }}</h4>
                                <p class="text-sm text-yellow-700">
                                    Score Range: {{ getIncentiveTierInfo.range }} |
                                    Incentive: ${{ getIncentiveTierInfo.amount.toFixed(2) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Overall Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Overall Notes & Recommendations
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="4"
                            class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 resize-none"
                            :disabled="form.processing"
                            placeholder="Provide overall assessment, key achievements, development areas, goals for improvement, and recommendations for career development..."
                        />
                        <span v-if="form.errors.notes" class="mt-1 flex items-center text-sm text-red-600">
                            <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ form.errors.notes }}
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div v-if="form.user_id && form.course_id" class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4">
                    <button
                        type="button"
                        @click="resetForm"
                        class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-6 py-3 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                        :disabled="form.processing"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset Form
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-8 py-3 text-sm font-medium text-white transition-colors duration-200 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm"
                        :disabled="form.processing || !isFormValid || totalScore === 0"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing || !isFormValid || totalScore === 0 }"
                    >
                        <svg v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        <svg v-else class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span v-if="form.processing">Submitting Evaluation...</span>
                        <span v-else>Submit Evaluation</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Confirmation Modal -->
        <Modal :show="showSubmitModal" @close="showSubmitModal = false" max-width="lg">
            <div class="p-6 sm:p-8">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Confirm Evaluation Submission</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Please review the evaluation details before submitting. This action will record the performance evaluation for
                            <span class="font-semibold">{{ selectedUser?.name }}</span>.
                        </p>

                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
                            <h4 class="font-medium text-gray-900 mb-2">Evaluation Summary</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Employee:</span>
                                    <p class="font-medium">{{ selectedUser?.name }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Course:</span>
                                    <p class="font-medium">{{ selectedCourse?.title }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Date:</span>
                                    <p class="font-medium">{{ form.evaluation_date }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Total Score:</span>
                                    <p class="font-medium">{{ totalScore }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Incentive Amount:</span>
                                    <p class="font-medium">${{ totalIncentiveAmount.toFixed(2) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button
                                @click="showSubmitModal = false"
                                class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 transition-colors duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmSubmit"
                                class="inline-flex items-center rounded-lg bg-green-600 px-6 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                                :disabled="form.processing"
                            >
                                <svg v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                <span v-if="form.processing">Submitting...</span>
                                <span v-else>Confirm & Submit</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
