<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
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
import { User, Monitor, Calendar, TrendingUp, Award, DollarSign, CheckCircle, RotateCcw, Send, Loader2, Layers, Target } from 'lucide-vue-next'

const props = defineProps<{
    users?: Array<{
        id: number
        name: string
        email: string
        department?: { id: number; name: string }
        department_id?: number
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
            progress?: number
        }>
    }>
    categories?: Array<{
        id: number
        name: string
        description: string
        weight: number
        max_score: number
        applies_to: string
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
        instructor_name?: string
    }>
    incentives?: Array<{
        id: number
        user_level_id?: number
        user_level_tier_id?: number
        min_score: number
        max_score: number
        incentive_amount: number
        performance_level: number
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
    performanceLevels?: Array<{
        level: number
        label: string
        description: string
        min_score: number
        max_score: number
        color: string
        badge_class: string
    }>
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
const safePerformanceLevels = computed(() => props.performanceLevels || [])

// State management
const showSubmitModal = ref(false)
const isFormValid = ref(false)
const availableUsers = ref(safeUsers.value)
const availableCourses = ref(safeCourses.value)
const isLoadingUsers = ref(false)
const isLoadingCourses = ref(false)
const isMounted = ref(true)

// Form for online course evaluation
const form = useForm({
    user_id: null as number | null,
    course_online_id: null as number | null,
    department_id: null as number | null,
    evaluation_date: new Date().toISOString().split('T')[0],
    notes: '',
    categories: [] as Array<{
        category_id: number
        evaluation_type_id: number | null
        comments: string
    }>
})

// Initialize form categories
const initializeFormCategories = () => {
    form.categories = safeCategories.value.map(category => ({
        category_id: category.id,
        evaluation_type_id: null,
        comments: ''
    }))
}

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
    form.course_online_id = value === 'none' ? null : parseInt(value)
}

// Watch department selection to filter users
watch(() => form.department_id, async (newDepartmentId) => {
    if (!isMounted.value) return

    if (newDepartmentId) {
        isLoadingUsers.value = true
        try {
            const routeUrl = route('admin.evaluations.online.users-by-department')
            const params = { department_id: newDepartmentId }

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

    form.user_id = null
    form.course_online_id = null
    availableCourses.value = safeCourses.value
})

// Watch user selection to filter online courses
watch(() => form.user_id, async (newUserId) => {
    if (!isMounted.value) return

    if (newUserId) {
        isLoadingCourses.value = true
        try {
            const routeUrl = route('admin.evaluations.online.user-courses')
            const params = { user_id: newUserId }

            const response = await axios.get(routeUrl, { params })
            availableCourses.value = response.data.courses || []
        } catch (error) {
            console.error('Error fetching user online courses:', error)
            const selectedUser = availableUsers.value.find(u => u.id === newUserId)
            availableCourses.value = selectedUser?.completed_courses || []
        } finally {
            isLoadingCourses.value = false
        }
    } else {
        availableCourses.value = safeCourses.value
    }

    form.course_online_id = null
})

// Get selected user info
const selectedUser = computed(() => {
    if (!form.user_id) return null
    return availableUsers.value.find(u => u.id === form.user_id) || null
})

// Get selected course info
const selectedCourse = computed(() => {
    if (!form.course_online_id) return null
    return availableCourses.value.find(c => c.id === form.course_online_id) || null
})

// Get selected evaluation type for a category
const getSelectedEvaluationType = (categoryId: number, typeId: number | null) => {
    if (!typeId) return null
    const category = safeCategories.value.find(cat => cat.id === categoryId)
    if (!category) return null
    return category.types.find(type => type.id === typeId) || null
}

// Calculate total score
const totalScore = computed(() => {
    return form.categories.reduce((sum, evalCat) => {
        const selectedType = getSelectedEvaluationType(evalCat.category_id, evalCat.evaluation_type_id)
        return sum + (selectedType?.score_value || 0)
    }, 0)
})

// Calculate performance level - directly from score
const performanceLevel = computed(() => {
    // Skip if no score
    if (!totalScore.value || totalScore.value === 0) return null;

    // Always use direct mapping first - this is the most reliable way
    const levelId = getPerformanceLevelByScore(totalScore.value);
    
    // If we have the performance level data, use it
    if (safePerformanceLevels.value.length > 0) {
        const levelData = safePerformanceLevels.value.find(level => level.level === levelId);
        if (levelData) return levelData;
    }
    
    // If we don't have the level data but have a valid level ID, create a synthetic one
    // This ensures we always have a performance level for a valid score
    if (levelId) {
        // Create a fallback performance level using the ID and hardcoded values
        let label, color, badge_class, min_score, max_score, description;
        
        switch(levelId) {
            case 1: // Outstanding
                label = 'Outstanding';
                color = 'green';
                badge_class = 'badge-success';
                min_score = 13;
                max_score = 15;
                description = 'Consistently exceeds expectations';
                break;
            case 2: // Reliable
                label = 'Reliable';
                color = 'blue';
                badge_class = 'badge-info';
                min_score = 10;
                max_score = 12;
                description = 'Meets expectations consistently';
                break;
            case 3: // Developing
                label = 'Developing';
                color = 'yellow';
                badge_class = 'badge-warning';
                min_score = 7;
                max_score = 9;
                description = 'Meets some expectations, needs improvement';
                break;
            case 4: // Underperforming
                label = 'Underperforming';
                color = 'red';
                badge_class = 'badge-danger';
                min_score = 0;
                max_score = 6;
                description = 'Does not meet expectations';
                break;
        }
        
        return {
            level: levelId,
            label,
            color,
            badge_class,
            min_score,
            max_score,
            description
        };
    }
    
    return null;
})

// For backward compatibility during transition
const totalIncentiveAmount = computed(() => {
    if (totalScore.value === 0 || !selectedUser.value) return 0

    const user = selectedUser.value

    // Priority 1: Level + Tier specific incentive
    if (user.user_level && user.user_level_tier) {
        const levelTierIncentive = safeIncentives.value.find(incentive =>
            incentive.user_level_id === user.user_level!.id &&
            incentive.user_level_tier_id === user.user_level_tier!.id &&
            totalScore.value >= incentive.min_score &&
            totalScore.value <= incentive.max_score
        )

        if (levelTierIncentive) {
            return parseFloat(levelTierIncentive.incentive_amount.toString())
        }
    }

    // Priority 2: Level-only incentive
    if (user.user_level) {
        const levelIncentive = safeIncentives.value.find(incentive =>
            incentive.user_level_id === user.user_level!.id &&
            !incentive.user_level_tier_id &&
            totalScore.value >= incentive.min_score &&
            totalScore.value <= incentive.max_score
        )

        if (levelIncentive) {
            return parseFloat(levelIncentive.incentive_amount.toString())
        }
    }

    // Priority 3: Global incentives
    const globalIncentive = safeIncentives.value.find(incentive =>
        !incentive.user_level_id &&
        !incentive.user_level_tier_id &&
        totalScore.value >= incentive.min_score &&
        totalScore.value <= incentive.max_score
    )

    return globalIncentive ? parseFloat(globalIncentive.incentive_amount.toString()) : 0
})

// Get incentive tier info
const getIncentiveTierInfo = computed(() => {
    if (totalScore.value === 0 || !selectedUser.value) return null

    const user = selectedUser.value
    let applicableIncentive = null

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

// Get tier name
const getTierName = (incentive: any, user: any) => {
    if (incentive.user_level && incentive.user_level_tier) {
        return `${incentive.user_level.code} - ${incentive.user_level_tier.tier_name}`
    }
    if (incentive.user_level) {
        return `${incentive.user_level.name} Level`
    }
    return 'Global Tier'
}

// Get performance level by score
const getPerformanceLevelByScore = (score: number) => {
    if (score >= 13 && score <= 15) return 1; // Outstanding
    if (score >= 10 && score <= 12) return 2; // Reliable
    if (score >= 7 && score <= 9) return 3;   // Developing
    if (score < 7) return 4;                  // Underperforming
    return null;
}

// Get performance level data with fallback
const getPerformanceLevel = (score: number) => {
    // First try to find the exact level in our performance levels data
    const levelId = getPerformanceLevelByScore(score);
    const levelData = safePerformanceLevels.value.find(level => level.level === levelId);
    
    if (levelData) {
        return {
            label: levelData.label,
            variant: levelData.badge_class.replace('badge-', '') || 'default',
            color: levelData.color
        };
    }
    
    // Fallback to hardcoded values if performance levels data is not available
    if (score >= 13) return { label: 'Outstanding', variant: 'success', color: 'green' }
    if (score >= 10) return { label: 'Reliable', variant: 'info', color: 'blue' }
    if (score >= 7) return { label: 'Developing', variant: 'warning', color: 'yellow' }
    if (score < 7) return { label: 'Underperforming', variant: 'destructive', color: 'red' }
    return { label: 'No Score', variant: 'secondary', color: 'gray' }
}

// Get type variant
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
        form.course_online_id &&
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

    form.post(route('admin.evaluations.online.store'), {
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
    { name: 'Online Course Evaluation', href: null }
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
                        <div class="flex items-center gap-3">
                            <h1 class="text-3xl font-bold text-foreground">Online Course Evaluation</h1>
                            <Badge variant="secondary" class="text-xs">
                                <Monitor class="mr-1 h-3 w-3" />
                                Online Courses
                            </Badge>
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">Assess employee performance on online courses with Level + Tier based incentive calculations.</p>
                    </div>
                    <div class="flex gap-2">
                        <Button :as="Link" :href="route('admin.evaluations.user-evaluation')" variant="outline">
                            Regular Courses
                        </Button>
                        <Button :as="Link" :href="route('admin.evaluations.index')" variant="outline">
                            <RotateCcw class="mr-2 h-4 w-4" />
                            Back
                        </Button>
                    </div>
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
                                <CardTitle>Select Department, Employee & Online Course</CardTitle>
                                <CardDescription>Choose department first, then employee, and finally their completed online course</CardDescription>
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

                            <!-- Online Course Selection -->
                            <div class="space-y-2">
                                <Label for="course">Select Completed Online Course *</Label>
                                <div class="relative">
                                    <Select
                                        :model-value="form.course_online_id?.toString() || 'none'"
                                        @update:model-value="handleCourseChange"
                                        :disabled="form.processing || !form.user_id || isLoadingCourses"
                                    >
                                        <SelectTrigger id="course">
                                            <SelectValue :placeholder="!form.user_id ? 'Select employee first...' : isLoadingCourses ? 'Loading courses...' : 'Choose a completed online course...'" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                {{
                                                    !form.user_id ? 'Select employee first...' :
                                                        isLoadingCourses ? 'Loading courses...' :
                                                            'Choose a completed online course...'
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
                                <p v-if="!form.user_id" class="text-xs text-muted-foreground">Select an employee to see their completed online courses</p>
                                <p v-else-if="availableCourses.length === 0 && !isLoadingCourses" class="text-xs text-destructive">This employee hasn't completed any online courses yet</p>
                                <span v-if="form.errors.course_online_id" class="text-sm text-destructive">{{ form.errors.course_online_id }}</span>
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

                        <!-- Selected User Info with Level + Tier -->
                        <Alert v-if="selectedUser && selectedCourse" class="mt-6">
                            <CheckCircle class="h-4 w-4" />
                            <AlertDescription>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium">Employee:</span>
                                        <p>{{ selectedUser.name }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium">Online Course:</span>
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
                                    <div v-if="selectedUser.user_level">
                                        <span class="font-medium">Level:</span>
                                        <p>
                                            <Badge variant="secondary" class="text-xs">
                                                {{ selectedUser.user_level.code }} - {{ selectedUser.user_level.name }}
                                            </Badge>
                                        </p>
                                    </div>
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
                                                Performance level calculation may fall back to default values.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </AlertDescription>
                        </Alert>
                    </CardContent>
                </Card>

                <!-- Evaluation Categories (Same as UserEvaluation but for online) -->
                <div v-if="form.user_id && form.course_online_id && safeCategories.length > 0" class="space-y-6">
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

                <!-- Overall Assessment Summary -->
                <Card v-if="form.user_id && form.course_online_id && form.categories.some(cat => cat.evaluation_type_id !== null)">
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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
                                        <Award class="h-8 w-8 text-green-600 mr-4" />
                                        <div>
                                            <p class="text-sm font-medium text-green-900">Performance Level</p>
                                            <p v-if="performanceLevel" class="text-xl font-bold" :class="`text-${performanceLevel.color}-600`">
                                                {{ performanceLevel.label }}
                                            </p>
                                            <p v-else class="text-xl font-bold text-gray-600">
                                                Not Rated
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                        </div>

                        <!-- Performance Level Details -->
                        <Alert v-if="performanceLevel" class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200">
                            <Award class="h-4 w-4" />
                            <AlertDescription>
                                <h4 class="font-semibold text-blue-900 mb-2">Performance Level Details</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm text-blue-700">
                                    <div>
                                        <span class="font-medium">Level:</span>
                                        <p class="font-bold" :class="`text-${performanceLevel.color}-600`">{{ performanceLevel.label }}</p>
                                    </div>
                                    <div>
                                        <span class="font-medium">Score Range:</span>
                                        <p>{{ performanceLevel.min_score }}-{{ performanceLevel.max_score }} points</p>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="font-medium">Description:</span>
                                        <p>{{ performanceLevel.description }}</p>
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
                <div v-if="form.user_id && form.course_online_id" class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4">
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

        <!-- Confirmation Modal -->
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
                            <DialogTitle>Confirm Online Course Evaluation</DialogTitle>
                            <DialogDescription>
                                Please review the evaluation details before submitting. This action will record the online course performance evaluation for
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
                                <span class="text-muted-foreground">Online Course:</span>
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
                                <span class="text-muted-foreground">Performance Level:</span>
                                <p class="font-medium">
                                    <Badge v-if="totalScore > 0" :class="`bg-${performanceLevel ? performanceLevel.color : 'green'}-100 text-${performanceLevel ? performanceLevel.color : 'green'}-800 border-${performanceLevel ? performanceLevel.color : 'green'}-200`">
                                        {{ performanceLevel ? performanceLevel.label : getPerformanceLevelByScore(totalScore) === 1 ? 'Outstanding' :
                                           getPerformanceLevelByScore(totalScore) === 2 ? 'Reliable' :
                                           getPerformanceLevelByScore(totalScore) === 3 ? 'Developing' :
                                           getPerformanceLevelByScore(totalScore) === 4 ? 'Underperforming' : 'Not Rated' }}
                                    </Badge>
                                    <Badge v-else variant="secondary">
                                        Not Rated
                                    </Badge>
                                </p>
                            </div>
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
                        <span v-else>Confirm & Submit</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
