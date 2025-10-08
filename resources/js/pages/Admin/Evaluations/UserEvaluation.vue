<!--
  Enhanced User Performance Evaluation Page with Level + Tier Support
  Assess employee performance with Level + Tier based incentive calculations
-->
<script setup lang="ts">
import { useForm, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { ref, computed, watch, onUnmounted } from 'vue'
import axios from 'axios'
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
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Badge } from '@/components/ui/badge'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Progress } from '@/components/ui/progress'
import { Separator } from '@/components/ui/separator'
import { User, BookOpen, Calendar, TrendingUp, Award, DollarSign, CheckCircle, RotateCcw, Send, Loader2, Layers, Target } from 'lucide-vue-next'

const props = defineProps<{
    users?: Array<{
        id: number
        name: string
        email: string
        department?: { id: number; name: string }
        department_id?: number
        // NEW: Level and Tier information
        user_level?: {
            id: number
            name: string
            code: string
        }
        user_level_tier?: {
            id: number
            tier_name: string
            tier_order: number
        }
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
    // NEW: Enhanced incentives with Level + Tier data
    incentives?: Array<{
        id: number
        user_level_id?: number
        user_level_tier_id?: number
        min_score: number
        max_score: number
        incentive_amount: number
        user_level?: {
            id: number
            name: string
            code: string
        }
        user_level_tier?: {
            id: number
            tier_name: string
            tier_order: number
        }
    }>
    // NEW: User levels with tiers for reference
    userLevels?: Array<{
        id: number
        code: string
        name: string
        hierarchy_level: number
        tiers: Array<{
            id: number
            tier_name: string
            tier_order: number
            description?: string
        }>
    }>
}>()

// Provide safe defaults
const safeUsers = computed(() => props.users || [])
const safeCategories = computed(() => props.categories || [])
const safeDepartments = computed(() => props.departments || [])
const safeCourses = computed(() => props.courses || [])
const safeIncentives = computed(() => props.incentives || [])
const safeUserLevels = computed(() => props.userLevels || [])

// State management
const showSubmitModal = ref(false)
const isFormValid = ref(false)
const availableUsers = ref(safeUsers.value)
const availableCourses = ref(safeCourses.value)
const isLoadingUsers = ref(false)
const isLoadingCourses = ref(false)
const isMounted = ref(true)

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

// Handle department change
const handleDepartmentChange = (value: string) => {
    if (!isMounted.value) return
    form.department_id = value === 'none' ? null : parseInt(value)
}

// Handle user change
const handleUserChange = (value: string) => {
    if (!isMounted.value) return
    form.user_id = value === 'none' ? null : parseInt(value)
}

// Handle course change
const handleCourseChange = (value: string) => {
    if (!isMounted.value) return
    form.course_id = value === 'none' ? null : parseInt(value)
}

// Watch department selection to filter users
watch(() => form.department_id, async (newDepartmentId) => {
    if (!isMounted.value) return

    if (newDepartmentId) {
        isLoadingUsers.value = true
        try {
            const routeUrl = route('admin.evaluations.users-by-department')
            const params = {
                department_id: newDepartmentId
            }

            console.log('Fetching users for department:', newDepartmentId)
            const response = await axios.get(routeUrl, { params })
            availableUsers.value = response.data.users || []
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

// Watch user selection to filter courses
watch(() => form.user_id, async (newUserId) => {
    if (!isMounted.value) return

    if (newUserId) {
        isLoadingCourses.value = true
        try {
            const routeUrl = route('admin.evaluations.user-courses')
            const params = {
                user_id: newUserId
            }

            const response = await axios.get(routeUrl, { params })
            availableCourses.value = response.data.courses || []
        } catch (error) {
            console.error('Error fetching user courses:', error)
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

// NEW: Enhanced incentive calculation based on user's level and tier
const totalIncentiveAmount = computed(() => {
    if (totalScore.value === 0 || !selectedUser.value) return 0

    const user = selectedUser.value

    // Priority 1: Try to find Level + Tier specific incentive
    if (user.user_level && user.user_level_tier) {
        const levelTierIncentive = safeIncentives.value.find(incentive =>
            incentive.user_level_id === user.user_level!.id &&
            incentive.user_level_tier_id === user.user_level_tier!.id &&
            totalScore.value >= incentive.min_score &&
            totalScore.value <= incentive.max_score
        )

        if (levelTierIncentive) {
            console.log('Found Level+Tier specific incentive:', levelTierIncentive.incentive_amount)
            return parseFloat(levelTierIncentive.incentive_amount.toString())
        }
    }

    // Priority 2: Try to find Level-only incentive
    if (user.user_level) {
        const levelIncentive = safeIncentives.value.find(incentive =>
            incentive.user_level_id === user.user_level!.id &&
            !incentive.user_level_tier_id &&
            totalScore.value >= incentive.min_score &&
            totalScore.value <= incentive.max_score
        )

        if (levelIncentive) {
            console.log('Found Level-only incentive:', levelIncentive.incentive_amount)
            return parseFloat(levelIncentive.incentive_amount.toString())
        }
    }

    // Priority 3: Fallback to global incentives
    const globalIncentive = safeIncentives.value.find(incentive =>
        !incentive.user_level_id &&
        !incentive.user_level_tier_id &&
        totalScore.value >= incentive.min_score &&
        totalScore.value <= incentive.max_score
    )

    return globalIncentive ? parseFloat(globalIncentive.incentive_amount.toString()) : 0
})

// NEW: Get incentive tier info with level/tier context
const getIncentiveTierInfo = computed(() => {
    if (totalScore.value === 0 || !selectedUser.value) return null

    const user = selectedUser.value
    let applicableIncentive = null

    // Find the applicable incentive (same logic as totalIncentiveAmount)
    if (user.user_level && user.user_level_tier) {
        applicableIncentive = safeIncentives.value.find(incentive =>
            incentive.user_level_id === user.user_level!.id &&
            incentive.user_level_tier_id === user.user_level_tier!.id &&
            totalScore.value >= incentive.min_score &&
            totalScore.value <= incentive.max_score
        )
    }

    if (!applicableIncentive && user.user_level) {
        applicableIncentive = safeIncentives.value.find(incentive =>
            incentive.user_level_id === user.user_level!.id &&
            !incentive.user_level_tier_id &&
            totalScore.value >= incentive.min_score &&
            totalScore.value <= incentive.max_score
        )
    }

    if (!applicableIncentive) {
        applicableIncentive = safeIncentives.value.find(incentive =>
            !incentive.user_level_id &&
            !incentive.user_level_tier_id &&
            totalScore.value >= incentive.min_score &&
            totalScore.value <= incentive.max_score
        )
    }

    if (!applicableIncentive) return null

    return {
        range: `${applicableIncentive.min_score}-${applicableIncentive.max_score}`,
        amount: parseFloat(applicableIncentive.incentive_amount.toString()),
        tier: getTierName(applicableIncentive, user),
        level_info: applicableIncentive.user_level ? applicableIncentive.user_level.name : 'Global',
        tier_info: applicableIncentive.user_level_tier ? applicableIncentive.user_level_tier.tier_name : null
    }
})

// NEW: Enhanced tier name with level/tier context
const getTierName = (incentive: any, user: any) => {
    if (incentive.user_level && incentive.user_level_tier) {
        return `${incentive.user_level.code} - ${incentive.user_level_tier.tier_name}`
    }
    if (incentive.user_level) {
        return `${incentive.user_level.name} Level`
    }

    // Fallback to old naming system for global incentives
    const sortedIncentives = [...safeIncentives.value]
        .filter(inc => !inc.user_level_id && !inc.user_level_tier_id)
        .sort((a, b) => b.min_score - a.min_score)

    const tierIndex = sortedIncentives.findIndex(inc =>
        inc.min_score === incentive.min_score && inc.max_score === incentive.max_score
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

    return tierIndex < tierNames.length ? tierNames[tierIndex] : `Tier ${tierIndex + 1}`
}

// Get performance level based on score
const getPerformanceLevel = (score: number) => {
    if (score >= 13) return { label: 'Excellent', variant: 'default' }
    if (score >= 10) return { label: 'Good', variant: 'secondary' }
    if (score >= 7) return { label: 'Average', variant: 'outline' }
    if (score >= 4) return { label: 'Below Average', variant: 'secondary' }
    if (score >= 1) return { label: 'Poor', variant: 'destructive' }
    return { label: 'No Score', variant: 'secondary' }
}

// Get type color based on score value
const getTypeVariant = (scoreValue: number, maxScore: number) => {
    const percentage = (scoreValue / maxScore) * 100
    if (percentage >= 90) return 'default'
    if (percentage >= 80) return 'secondary'
    if (percentage >= 70) return 'outline'
    if (percentage >= 60) return 'secondary'
    return 'destructive'
}

// Handle evaluation type change
const handleEvaluationTypeChange = (categoryId: number, typeId: string) => {
    if (!isMounted.value) return

    const categoryIndex = form.categories.findIndex(cat => cat.category_id === categoryId)
    if (categoryIndex !== -1) {
        form.categories[categoryIndex].evaluation_type_id = parseInt(typeId)
        validateForm()
    }
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
    if (!isMounted.value) return

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
    if (!isMounted.value) return

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
                        <h1 class="text-3xl font-bold text-foreground">User Performance Evaluation</h1>
                        <p class="mt-2 text-sm text-muted-foreground">Assess employee performance with Level + Tier based incentive calculations.</p>
                    </div>
                    <Button :as="Link" :href="route('admin.evaluations.index')" variant="outline">
                        <RotateCcw class="mr-2 h-4 w-4" />
                        Back to Evaluations
                    </Button>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- User Selection Section -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center">
                            <div class="shrink-0 mr-4">
                                <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-primary/10">
                                    <User class="h-6 w-6 text-primary" />
                                </div>
                            </div>
                            <div>
                                <CardTitle>Select Department, Employee & Course</CardTitle>
                                <CardDescription>Choose department first, then employee, and finally their completed course</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <!-- Department Selection -->
                            <div class="space-y-2">
                                <Label for="department">Select Department</Label>
                                <Select
                                    :model-value="form.department_id?.toString() || 'none'"
                                    @update:model-value="handleDepartmentChange"
                                    :disabled="form.processing"
                                >
                                    <SelectTrigger id="department">
                                        <SelectValue placeholder="All Departments" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">All Departments</SelectItem>
                                        <SelectItem v-for="dept in safeDepartments" :key="dept.id" :value="dept.id.toString()">
                                            {{ dept.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p class="text-xs text-muted-foreground">Filter employees by department</p>
                            </div>

                            <!-- User Selection -->
                            <div class="space-y-2">
                                <Label for="user">Select Employee *</Label>
                                <div class="relative">
                                    <Select
                                        :model-value="form.user_id?.toString() || 'none'"
                                        @update:model-value="handleUserChange"
                                        :disabled="form.processing || isLoadingUsers"
                                    >
                                        <SelectTrigger id="user">
                                            <SelectValue :placeholder="isLoadingUsers ? 'Loading users...' : 'Choose an employee...'" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                {{ isLoadingUsers ? 'Loading users...' : 'Choose an employee...' }}
                                            </SelectItem>
                                            <SelectItem v-for="user in availableUsers" :key="user.id" :value="user.id.toString()">
                                                {{ user.name }}
                                                {{ user.department ? `(${user.department.name})` : '' }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="isLoadingUsers" class="absolute right-8 top-2.5">
                                        <Loader2 class="h-4 w-4 animate-spin text-primary" />
                                    </div>
                                </div>
                                <p v-if="!form.department_id" class="text-xs text-muted-foreground">Showing all employees</p>
                                <p v-else-if="availableUsers.length === 0 && !isLoadingUsers" class="text-xs text-destructive">No employees found in this department</p>
                                <span v-if="form.errors.user_id" class="text-sm text-destructive">{{ form.errors.user_id }}</span>
                            </div>

                            <!-- Course Selection -->
                            <div class="space-y-2">
                                <Label for="course">Select Completed Course *</Label>
                                <div class="relative">
                                    <Select
                                        :model-value="form.course_id?.toString() || 'none'"
                                        @update:model-value="handleCourseChange"
                                        :disabled="form.processing || !form.user_id || isLoadingCourses"
                                    >
                                        <SelectTrigger id="course">
                                            <SelectValue :placeholder="!form.user_id ? 'Select employee first...' : isLoadingCourses ? 'Loading courses...' : 'Choose a completed course...'" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                {{
                                                    !form.user_id ? 'Select employee first...' :
                                                        isLoadingCourses ? 'Loading courses...' :
                                                            'Choose a completed course...'
                                                }}
                                            </SelectItem>
                                            <SelectItem v-for="course in availableCourses" :key="course.id" :value="course.id.toString()">
                                                {{ course.title }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <div v-if="isLoadingCourses" class="absolute right-8 top-2.5">
                                        <Loader2 class="h-4 w-4 animate-spin text-primary" />
                                    </div>
                                </div>
                                <p v-if="!form.user_id" class="text-xs text-muted-foreground">Select an employee to see their completed courses</p>
                                <p v-else-if="availableCourses.length === 0 && !isLoadingCourses" class="text-xs text-destructive">This employee hasn't completed any courses yet</p>
                                <span v-if="form.errors.course_id" class="text-sm text-destructive">{{ form.errors.course_id }}</span>
                            </div>

                            <!-- Evaluation Date -->
                            <div class="space-y-2">
                                <Label for="date">Evaluation Date *</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    v-model="form.evaluation_date"
                                    :disabled="form.processing"
                                    required
                                />
                                <span v-if="form.errors.evaluation_date" class="text-sm text-destructive">{{ form.errors.evaluation_date }}</span>
                            </div>
                        </div>

                        <!-- NEW: Enhanced Selected User Info with Level + Tier -->
                        <Alert v-if="selectedUser && selectedCourse" class="mt-6">
                            <CheckCircle class="h-4 w-4" />
                            <AlertDescription>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium">Employee:</span>
                                        <p>{{ selectedUser.name }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium">Course:</span>
                                        <p>{{ selectedCourse.title }}</p>
                                    </div>
                                    <div v-if="selectedCourse.completed_at">
                                        <span class="font-medium">Completed:</span>
                                        <p>{{ new Date(selectedCourse.completed_at).toLocaleDateString() }}</p>
                                    </div>
                                    <div v-if="selectedUser.department">
                                        <span class="font-medium">Department:</span>
                                        <p>{{ selectedUser.department.name }}</p>
                                    </div>
                                    <!-- NEW: Level Info -->
                                    <div v-if="selectedUser.user_level">
                                        <span class="font-medium">Level:</span>
                                        <p>
                                            <Badge variant="secondary" class="text-xs">
                                                {{ selectedUser.user_level.code }} - {{ selectedUser.user_level.name }}
                                            </Badge>
                                        </p>
                                    </div>
                                    <!-- NEW: Tier Info -->
                                    <div v-if="selectedUser.user_level_tier">
                                        <span class="font-medium">Tier:</span>
                                        <p>
                                            <Badge variant="outline" class="text-xs">
                                                {{ selectedUser.user_level_tier.tier_name }} (T{{ selectedUser.user_level_tier.tier_order }})
                                            </Badge>
                                        </p>
                                    </div>
                                </div>

                                <!-- Warning if no level/tier assigned -->
                                <div v-if="!selectedUser.user_level || !selectedUser.user_level_tier" class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                    <div class="flex items-center">
                                        <Target class="h-4 w-4 text-yellow-600 mr-2" />
                                        <div class="text-yellow-800 text-sm">
                                            <p class="font-medium">Incomplete Assignment</p>
                                            <p>
                                                This employee is missing
                                                {{ !selectedUser.user_level ? 'level assignment' : '' }}
                                                {{ !selectedUser.user_level && !selectedUser.user_level_tier ? ' and ' : '' }}
                                                {{ !selectedUser.user_level_tier ? 'tier assignment' : '' }}.
                                                Incentive calculation may fall back to global rates.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </AlertDescription>
                        </Alert>
                    </CardContent>
                </Card>

                <!-- Evaluation Categories -->
                <div v-if="form.user_id && form.course_id && safeCategories.length > 0" class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-foreground">Evaluation Categories</h2>
                        <Badge variant="secondary">{{ safeCategories.length }} categories</Badge>
                    </div>

                    <div class="grid gap-6">
                        <Card
                            v-for="(evalCategory, index) in form.categories"
                            :key="evalCategory.category_id"
                            class="hover:shadow-md transition-shadow duration-200"
                        >
                            <CardHeader>
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <CardTitle class="text-lg">
                                                {{ safeCategories.find(cat => cat.id === evalCategory.category_id)?.name || 'Category' }}
                                            </CardTitle>
                                            <Badge variant="outline">
                                                {{ safeCategories.find(cat => cat.id === evalCategory.category_id)?.weight || 0 }}% weight
                                            </Badge>
                                        </div>
                                        <CardDescription>
                                            {{ safeCategories.find(cat => cat.id === evalCategory.category_id)?.description || 'No description available' }}
                                        </CardDescription>
                                    </div>

                                    <!-- Score Display -->
                                    <div v-if="getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)" class="text-right">
                                        <div class="text-2xl font-bold text-foreground">
                                            {{ getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            out of {{ safeCategories.find(cat => cat.id === evalCategory.category_id)?.max_score }}
                                        </div>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Evaluation Type Selection -->
                                    <div class="space-y-4">
                                        <Label class="text-sm font-medium">Select Performance Level</Label>
                                        <RadioGroup
                                            :model-value="evalCategory.evaluation_type_id?.toString() || ''"
                                            @update:model-value="(value) => handleEvaluationTypeChange(evalCategory.category_id, value)"
                                            class="space-y-2"
                                        >
                                            <div
                                                v-for="type in safeCategories.find(cat => cat.id === evalCategory.category_id)?.types || []"
                                                :key="type.id"
                                                class="flex items-center space-x-3 p-4 border rounded-lg hover:bg-accent/50 transition-colors"
                                                :class="{ 'border-primary bg-primary/5': evalCategory.evaluation_type_id === type.id }"
                                            >
                                                <RadioGroupItem :value="type.id.toString()" :id="`type_${evalCategory.category_id}_${type.id}`" />
                                                <div class="flex-1 flex items-center justify-between">
                                                    <div>
                                                        <Label :for="`type_${evalCategory.category_id}_${type.id}`" class="font-medium cursor-pointer">
                                                            {{ type.type_name }}
                                                        </Label>
                                                        <p class="text-sm text-muted-foreground">{{ type.description }}</p>
                                                    </div>
                                                    <Badge :variant="getTypeVariant(type.score_value, safeCategories.find(cat => cat.id === evalCategory.category_id)?.max_score || 100)">
                                                        {{ type.score_value }} pts
                                                    </Badge>
                                                </div>
                                            </div>
                                        </RadioGroup>
                                        <span v-if="form.errors[`categories.${index}.evaluation_type_id`]" class="text-sm text-destructive">
                                            {{ form.errors[`categories.${index}.evaluation_type_id`] }}
                                        </span>
                                    </div>

                                    <!-- Selected Performance Info -->
                                    <div v-if="getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)" class="space-y-4">
                                        <Alert>
                                            <TrendingUp class="h-4 w-4" />
                                            <AlertDescription>
                                                <h4 class="font-medium mb-3">Selected Performance Level</h4>
                                                <div class="space-y-3">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-sm font-medium">Performance:</span>
                                                        <Badge :variant="getPerformanceLevel(getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0).variant">
                                                            {{ getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.type_name }}
                                                        </Badge>
                                                    </div>
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-sm font-medium">Score:</span>
                                                        <span class="text-sm font-bold">
                                                            {{ getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value }} /
                                                            {{ safeCategories.find(cat => cat.id === evalCategory.category_id)?.max_score }}
                                                        </span>
                                                    </div>
                                                    <!-- Score Progress Bar -->
                                                    <Progress
                                                        :value="Math.round(((getSelectedEvaluationType(evalCategory.category_id, evalCategory.evaluation_type_id)?.score_value || 0) / (safeCategories.find(cat => cat.id === evalCategory.category_id)?.max_score || 100)) * 100)"
                                                        class="h-2"
                                                    />
                                                </div>
                                            </AlertDescription>
                                        </Alert>
                                    </div>
                                </div>

                                <!-- Comments Section -->
                                <div class="mt-6 space-y-2">
                                    <Label :for="`comments_${evalCategory.category_id}`">Detailed Comments & Feedback</Label>
                                    <Textarea
                                        :id="`comments_${evalCategory.category_id}`"
                                        v-model="evalCategory.comments"
                                        rows="3"
                                        :disabled="form.processing"
                                        placeholder="Provide specific feedback, strengths, areas for improvement, and recommendations..."
                                    />
                                    <span v-if="form.errors[`categories.${index}.comments`]" class="text-sm text-destructive">
                                        {{ form.errors[`categories.${index}.comments`] }}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Enhanced Overall Assessment Summary -->
                <Card v-if="form.user_id && form.course_id && form.categories.some(cat => cat.evaluation_type_id !== null)">
                    <CardHeader>
                        <div class="flex items-center">
                            <div class="shrink-0 mr-4">
                                <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-green-100">
                                    <CheckCircle class="h-6 w-6 text-green-600" />
                                </div>
                            </div>
                            <div>
                                <CardTitle>Overall Assessment</CardTitle>
                                <CardDescription>Summary with Level + Tier based incentive calculation</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- Score Summary Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <Card class="bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200">
                                <CardContent class="p-4">
                                    <div class="flex items-center">
                                        <TrendingUp class="h-8 w-8 text-blue-600 mr-4" />
                                        <div>
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
                                </CardContent>
                            </Card>

                            <Card class="bg-gradient-to-r from-green-50 to-emerald-50 border-green-200">
                                <CardContent class="p-4">
                                    <div class="flex items-center">
                                        <DollarSign class="h-8 w-8 text-green-600 mr-4" />
                                        <div>
                                            <p class="text-sm font-medium text-green-900">Incentive Amount</p>
                                            <p class="text-2xl font-bold text-green-600">
                                                ${{ totalIncentiveAmount.toFixed(2) }}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card class="bg-gradient-to-r from-purple-50 to-pink-50 border-purple-200">
                                <CardContent class="p-4">
                                    <div class="flex items-center">
                                        <Award class="h-8 w-8 text-purple-600 mr-4" />
                                        <div>
                                            <p class="text-sm font-medium text-purple-900">Performance Rating</p>
                                            <p class="text-lg font-bold text-purple-600">
                                                {{ getPerformanceLevel(totalScore).label }}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- NEW: Enhanced Incentive Tier Info with Level + Tier Context -->
                        <Alert v-if="getIncentiveTierInfo" class="mb-6 bg-gradient-to-r from-yellow-50 to-amber-50 border-yellow-200">
                            <Layers class="h-4 w-4" />
                            <AlertDescription>
                                <h4 class="font-semibold text-yellow-900 mb-2">{{ getIncentiveTierInfo.tier }}</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm text-yellow-700">
                                    <div>
                                        <span class="font-medium">Incentive Level:</span>
                                        <p>{{ getIncentiveTierInfo.level_info }}</p>
                                    </div>
                                    <div v-if="getIncentiveTierInfo.tier_info">
                                        <span class="font-medium">Performance Tier:</span>
                                        <p>{{ getIncentiveTierInfo.tier_info }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium">Score Range:</span>
                                        <p>{{ getIncentiveTierInfo.range }} points</p>
                                    </div>
                                    <div>
                                        <span class="font-medium">Reward Amount:</span>
                                        <p class="font-bold">${{ getIncentiveTierInfo.amount.toFixed(2) }}</p>
                                    </div>
                                </div>
                            </AlertDescription>
                        </Alert>

                        <!-- Overall Notes -->
                        <div class="space-y-2">
                            <Label for="notes">Overall Notes & Recommendations</Label>
                            <Textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                :disabled="form.processing"
                                placeholder="Provide overall assessment, key achievements, development areas, goals for improvement, and recommendations for career development..."
                            />
                            <span v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Action Buttons -->
                <div v-if="form.user_id && form.course_id" class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4">
                    <Button
                        type="button"
                        @click="resetForm"
                        variant="outline"
                        :disabled="form.processing"
                    >
                        <RotateCcw class="mr-2 h-4 w-4" />
                        Reset Form
                    </Button>

                    <Button
                        type="submit"
                        :disabled="form.processing || !isFormValid || totalScore === 0"
                    >
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <Send v-else class="mr-2 h-4 w-4" />
                        <span v-if="form.processing">Submitting Evaluation...</span>
                        <span v-else>Submit Evaluation</span>
                    </Button>
                </div>
            </form>
        </div>

        <!-- Enhanced Confirmation Modal -->
        <Dialog v-model:open="showSubmitModal">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <div class="flex items-start">
                        <div class="shrink-0 mr-4">
                            <div class="flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                                <CheckCircle class="h-6 w-6 text-green-600" />
                            </div>
                        </div>
                        <div class="flex-1">
                            <DialogTitle>Confirm Evaluation Submission</DialogTitle>
                            <DialogDescription>
                                Please review the evaluation details before submitting. This action will record the performance evaluation for
                                <span class="font-semibold">{{ selectedUser?.name }}</span>.
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <Alert class="my-4">
                    <AlertDescription>
                        <h4 class="font-medium mb-2">Evaluation Summary</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-muted-foreground">Employee:</span>
                                <p class="font-medium">{{ selectedUser?.name }}</p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Course:</span>
                                <p class="font-medium">{{ selectedCourse?.title }}</p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Date:</span>
                                <p class="font-medium">{{ form.evaluation_date }}</p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Total Score:</span>
                                <p class="font-medium">{{ totalScore }}</p>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Incentive Amount:</span>
                                <p class="font-medium">${{ totalIncentiveAmount.toFixed(2) }}</p>
                            </div>
                            <!-- NEW: Level + Tier info in confirmation -->
                            <div v-if="selectedUser?.user_level && selectedUser?.user_level_tier">
                                <span class="text-muted-foreground">Level + Tier:</span>
                                <p class="font-medium">{{ selectedUser.user_level.code }} - {{ selectedUser.user_level_tier.tier_name }}</p>
                            </div>
                        </div>
                    </AlertDescription>
                </Alert>

                <DialogFooter>
                    <Button @click="showSubmitModal = false" variant="outline">
                        Cancel
                    </Button>
                    <Button @click="confirmSubmit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <span v-if="form.processing">Submitting...</span>
                        <span v-else">Confirm & Submit</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
