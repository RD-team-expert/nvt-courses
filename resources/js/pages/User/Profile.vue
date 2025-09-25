<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { computed } from 'vue'
import type { BreadcrumbItemType } from '@/types'

const props = defineProps({
    user: Object,
    managerRoles: Array,
    directReports: Array,
    managers: Array,
    evaluations: Array,
    courses: Array,
    recentActivity: Array
})

// Get level color
const getLevelColor = (levelCode: string) => {
    const colors = {
        'L1': 'bg-blue-100 text-blue-800',
        'L2': 'bg-green-100 text-green-800',
        'L3': 'bg-orange-100 text-orange-800',
        'L4': 'bg-red-100 text-red-800',
    }
    return colors[levelCode] || 'bg-gray-100 text-gray-800'
}

// Get role type color
const getRoleColor = (roleType: string) => {
    const colors = {
        'direct_manager': 'bg-blue-100 text-blue-800',
        'project_manager': 'bg-green-100 text-green-800',
        'department_head': 'bg-purple-100 text-purple-800',
        'senior_manager': 'bg-red-100 text-red-800',
        'team_lead': 'bg-yellow-100 text-yellow-800',
    }
    return colors[roleType] || 'bg-gray-100 text-gray-800'
}

// Get evaluation performance level
const getPerformanceLevel = (score: number) => {
    if (score >= 20) return { label: 'Exceptional', class: 'bg-emerald-100 text-emerald-800' }
    if (score >= 15) return { label: 'Excellent', class: 'bg-green-100 text-green-800' }
    if (score >= 10) return { label: 'Good', class: 'bg-blue-100 text-blue-800' }
    if (score >= 5) return { label: 'Average', class: 'bg-yellow-100 text-yellow-800' }
    return { label: 'Needs Improvement', class: 'bg-red-100 text-red-800' }
}

// Stats
const stats = computed(() => ({
    managerRoles: props.managerRoles?.length || 0,
    directReports: props.directReports?.length || 0,
    managers: props.managers?.length || 0,
    evaluations: props.evaluations?.length || 0,
    courses: props.courses?.length || 0,
    isManager: (props.managerRoles?.length || 0) > 0
}))

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'My Profile', href: '#' }
]

// Latest evaluation
const latestEvaluation = computed(() => {
    if (!props.evaluations || props.evaluations.length === 0) return null
    return props.evaluations[0] // Assuming they're ordered by date
})

// Course completion rate
const courseCompletionRate = computed(() => {
    if (!props.courses || props.courses.length === 0) return 0
    const completed = props.courses.filter(course => course.status === 'completed').length
    return Math.round((completed / props.courses.length) * 100)
})
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 max-w-7xl mx-auto">
            <!-- Profile Header -->
            <div class="bg-linear-to-r from-blue-500 to-purple-600 rounded-lg shadow p-6 mb-6 text-white">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="shrink-0 h-20 w-20">
                            <div class="h-20 w-20 rounded-full bg-white bg-opacity-20 flex items-center justify-center border-2 border-white border-opacity-30">
                                <span class="text-2xl font-bold text-white">
                                    {{ user?.name?.charAt(0).toUpperCase() || '?' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold">{{ user?.name || 'Your Name' }}</h1>
                            <p class="text-blue-100 mt-1">{{ user?.email || 'your.email@company.com' }}</p>
                            <div class="mt-3 flex items-center space-x-4">
                                <span v-if="user?.employee_code" class="text-blue-100">
                                    <strong>Employee ID:</strong> {{ user.employee_code }}
                                </span>
                                <span v-if="user?.level"
                                      class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white bg-opacity-20 text-white border border-white border-opacity-30">
                                    {{ user.level.code }} - {{ user.level.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 w-full sm:w-auto">
                        <Link
                            :href="route('user.evaluations.index')"
                            class="bg-white bg-opacity-20 text-white px-4 py-2 rounded-lg hover:bg-opacity-30 transition w-full sm:w-auto text-center border border-white border-opacity-30"
                        >
                            🏆 My Evaluations
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Completed Courses</dt>
                                <dd class="text-2xl font-bold text-gray-900">{{ stats.courses }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Evaluations</dt>
                                <dd class="text-2xl font-bold text-gray-900">{{ stats.evaluations }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Team Members</dt>
                                <dd class="text-2xl font-bold text-gray-900">{{ stats.directReports }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Completion Rate</dt>
                                <dd class="text-2xl font-bold text-gray-900">{{ courseCompletionRate }}%</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Personal Information -->
                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Personal Information
                    </h2>

                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-700">Department</dt>
                            <dd class="mt-1">
                                <div v-if="user?.department" class="text-sm text-gray-900">
                                    {{ user.department.name }}
                                    <span v-if="user.department.code" class="text-gray-500 ml-1">({{ user.department.code }})</span>
                                </div>
                                <div v-else class="text-sm text-gray-400 italic">No department assigned</div>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-700">Position Level</dt>
                            <dd class="mt-1">
                                <span v-if="user?.level"
                                      class="inline-flex items-center px-2 py-0.5 rounded text-sm font-medium"
                                      :class="getLevelColor(user.level.code)">
                                    {{ user.level.code }} - {{ user.level.name }}
                                </span>
                                <span v-else class="text-sm text-gray-400 italic">No level assigned</span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-700">Employee ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ user?.employee_code || 'Not assigned' }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-700">Join Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ user?.created_at ? new Date(user.created_at).toLocaleDateString() : 'Unknown' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-700">Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-800': user?.status === 'active',
                                          'bg-red-100 text-red-800': user?.status === 'inactive',
                                          'bg-yellow-100 text-yellow-800': user?.status === 'on_leave'
                                      }">
                                    {{ user?.status || 'Unknown' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Latest Evaluation -->
                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Latest Evaluation
                        </h2>
                        <Link
                            :href="route('user.evaluations.index')"
                            class="text-blue-600 hover:text-blue-900 text-sm transition-colors"
                        >
                            View All →
                        </Link>
                    </div>

                    <div v-if="latestEvaluation" class="space-y-4">
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-medium text-gray-900">{{ latestEvaluation.course_name || 'Course Evaluation' }}</h3>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                    :class="getPerformanceLevel(latestEvaluation.total_score).class"
                                >
                                    {{ getPerformanceLevel(latestEvaluation.total_score).label }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-2xl font-bold text-gray-900">{{ latestEvaluation.total_score }}<span class="text-sm text-gray-500">/25</span></p>
                                    <p class="text-sm text-gray-600">Total Score</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-semibold text-green-600">${{ latestEvaluation.incentive_amount || '0.00' }}</p>
                                    <p class="text-sm text-gray-600">Incentive</p>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                Evaluated on {{ new Date(latestEvaluation.created_at).toLocaleDateString() }}
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No evaluations yet</h3>
                        <p class="mt-1 text-sm text-gray-500">Your performance evaluations will appear here.</p>
                    </div>
                </div>

                <!-- Recent Courses -->
                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="h-5 w-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            Recent Courses
                        </h2>
                        <Link
                            :href="route('courses.index')"
                            class="text-blue-600 hover:text-blue-900 text-sm transition-colors"
                        >
                            View All →
                        </Link>
                    </div>

                    <div v-if="courses && courses.length > 0" class="space-y-3">
                        <div v-for="course in courses.slice(0, 3)" :key="course.id" class="p-3 border rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-medium text-gray-900 text-sm">{{ course.name || course.title }}</h3>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                    :class="{
                                        'bg-green-100 text-green-800': course.status === 'completed',
                                        'bg-yellow-100 text-yellow-800': course.status === 'in_progress',
                                        'bg-gray-100 text-gray-800': course.status === 'not_started'
                                    }"
                                >
                                    {{ course.status === 'completed' ? 'Completed' :
                                    course.status === 'in_progress' ? 'In Progress' : 'Not Started' }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ course.progress || 0 }}% Complete
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                                <div
                                    class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                                    :style="`width: ${course.progress || 0}%`"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No courses yet</h3>
                        <p class="mt-1 text-sm text-gray-500">Your enrolled courses will appear here.</p>
                    </div>
                </div>
            </div>

            <!-- Manager Roles & Team (if applicable) -->
            <div v-if="stats.isManager" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <!-- Manager Roles -->
                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        My Management Roles
                    </h2>

                    <div v-if="managerRoles && managerRoles.length > 0" class="space-y-3">
                        <div v-for="role in managerRoles" :key="role.id"
                             class="border rounded-lg p-4"
                             :class="{ 'border-blue-300 bg-blue-50': role.is_primary }">
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                      :class="getRoleColor(role.role_type)">
                                    {{ role.role_display }}
                                </span>
                                <span v-if="role.is_primary"
                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Primary Role
                                </span>
                            </div>
                            <div class="text-sm text-gray-900 mb-1">{{ role.department }}</div>
                            <div class="text-xs text-gray-500">
                                Authority Level {{ role.authority_level }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Direct Reports -->
                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            My Team ({{ stats.directReports }})
                        </h2>
                        <Link
                            v-if="stats.directReports > 0"
                            :href="route('user.team.index')"
                            class="text-blue-600 hover:text-blue-900 text-sm transition-colors"
                        >
                            Manage Team →
                        </Link>
                    </div>

                    <div v-if="directReports && directReports.length > 0" class="space-y-3">
                        <div v-for="report in directReports.slice(0, 4)" :key="report.id" class="flex items-center space-x-3 p-3 border rounded-lg">
                            <div class="shrink-0 h-8 w-8">
                                <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-xs font-medium text-gray-600">
                                        {{ report.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900">{{ report.name }}</div>
                                <div class="text-xs text-gray-500">{{ report.level || 'Staff' }} • {{ report.department || 'No Dept' }}</div>
                            </div>
                        </div>
                        <div v-if="directReports.length > 4" class="text-center pt-2">
                            <Link
                                :href="route('user.team.index')"
                                class="text-blue-600 hover:text-blue-900 text-sm transition-colors"
                            >
                                View {{ directReports.length - 4 }} more team members →
                            </Link>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No direct reports</h3>
                        <p class="mt-1 text-sm text-gray-500">Your team members will appear here.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
