<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import type { BreadcrumbItemType } from '@/types'

const props = defineProps<{
    history?: Array<{
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
}>()

const safeHistory = computed(() => props.history || [])
const evaluation = computed(() => safeHistory.value[0]?.evaluation || null)

// Get performance level styling
function getPerformanceLevel(score: number) {
    if (score >= 20) return { label: 'Exceptional', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' }
    if (score >= 15) return { label: 'Excellent', class: 'bg-green-100 text-green-800 border-green-200' }
    if (score >= 10) return { label: 'Good', class: 'bg-blue-100 text-blue-800 border-blue-200' }
    if (score >= 5) return { label: 'Average', class: 'bg-yellow-100 text-yellow-800 border-yellow-200' }
    return { label: 'Below Average', class: 'bg-red-100 text-red-800 border-red-200' }
}

// Get category color
function getCategoryColor(score: number) {
    if (score >= 5) return 'bg-emerald-100 text-emerald-800 border-emerald-200'
    if (score >= 4) return 'bg-green-100 text-green-800 border-green-200'
    if (score >= 3) return 'bg-blue-100 text-blue-800 border-blue-200'
    if (score >= 2) return 'bg-yellow-100 text-yellow-800 border-yellow-200'
    return 'bg-red-100 text-red-800 border-red-200'
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Evaluations', href: route('admin.evaluations.index') },
    { name: 'History', href: route('admin.evaluations.history') },
    { name: 'Details', href: null }
]
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-8 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Evaluation Details</h1>
                        <p v-if="evaluation" class="mt-2 text-sm text-gray-600">
                            Detailed view of evaluation for {{ evaluation.user.name }}
                        </p>
                    </div>
                    <Link
                        :href="route('admin.evaluations.history')"
                        class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition-colors duration-200 hover:bg-gray-200"
                    >
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to History
                    </Link>
                </div>
            </div>

            <div v-if="!evaluation" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No Evaluation Found</h3>
                <p class="mt-2 text-sm text-gray-500">The requested evaluation details could not be found.</p>
            </div>

            <div v-else class="space-y-8">
                <!-- Evaluation Summary -->
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
                            <h2 class="text-xl font-semibold text-gray-900">Employee Information</h2>
                            <p class="text-sm text-gray-600">Basic details about the evaluated employee</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Employee Name</label>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ evaluation.user.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email Address</label>
                                <p class="mt-1 text-sm text-gray-900">{{ evaluation.user.email }}</p>
                            </div>
                            <div v-if="evaluation.department">
                                <label class="block text-sm font-medium text-gray-700">Department</label>
                                <p class="mt-1 text-sm text-gray-900">{{ evaluation.department.name }}</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div v-if="evaluation.course">
                                <label class="block text-sm font-medium text-gray-700">Course</label>
                                <p class="mt-1 text-sm text-gray-900">{{ evaluation.course.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Evaluation Date</label>
                                <p class="mt-1 text-sm text-gray-900">{{ new Date(evaluation.created_at).toLocaleDateString() }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Performance Level</label>
                                <span
                                    class="mt-1 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border"
                                    :class="getPerformanceLevel(evaluation.total_score).class"
                                >
                                    {{ getPerformanceLevel(evaluation.total_score).label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overall Results -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Overall Results</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Score Summary -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Total Score</p>
                                    <p class="text-3xl font-bold text-blue-600">{{ evaluation.total_score }}</p>
                                </div>
                                <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Incentive Summary -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg border border-green-200">
                                <div>
                                    <p class="text-sm font-medium text-green-900">Incentive Amount</p>
                                    <p class="text-3xl font-bold text-green-600">${{ parseFloat(evaluation.incentive_amount.toString()).toFixed(2) }}</p>
                                </div>
                                <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-10 w-10 rounded-lg bg-purple-100">
                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h2m2-8H9m0 0V3m0 4v6m0-6h2a2 2 0 012 2v6a2 2 0 01-2 2H9" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-semibold text-gray-900">Category Breakdown</h2>
                            <p class="text-sm text-gray-600">Detailed scores for each evaluation category</p>
                        </div>
                    </div>

                    <div class="grid gap-6">
                        <div
                            v-for="item in safeHistory"
                            :key="item.id"
                            class="border border-gray-200 rounded-lg p-6 hover:shadow-sm transition-shadow duration-200"
                        >
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ item.category_name }}</h3>
                                    <p class="text-sm text-gray-600 mb-3">Performance Level: <span class="font-medium">{{ item.type_name }}</span></p>
                                </div>
                                <div class="text-right">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-lg font-bold border"
                                        :class="getCategoryColor(item.score)"
                                    >
                                        {{ item.score }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="item.comments" class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <h4 class="font-medium text-gray-900 mb-2">Comments & Feedback</h4>
                                <p class="text-sm text-gray-700">{{ item.comments }}</p>
                            </div>

                            <div class="mt-4 text-xs text-gray-500">
                                Evaluated on {{ new Date(item.created_at).toLocaleString() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
