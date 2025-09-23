<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { computed } from 'vue'
import type { BreadcrumbItemType } from '@/types'

const props = defineProps({
    evaluation: Object, // Single evaluation object
    user: Object,
})

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'My Profile', href: route('user.profile.index') },
    { name: 'My Evaluations', href: route('user.evaluations.index') },
    { name: 'Evaluation Details', href: '#' }
]

// 🎯 DYNAMIC: Calculate max score from the current evaluation
const maxPossibleScore = computed(() => {
    const currentScore = props.evaluation?.total_score || 0;

    // Smart detection based on current score
    if (currentScore <= 5) return 5;     // 1-5 scale
    if (currentScore <= 10) return 10;   // 1-10 scale
    if (currentScore <= 20) return 20;   // 1-20 scale
    if (currentScore <= 25) return 25;   // 1-25 scale (current)
    if (currentScore <= 50) return 50;   // 1-50 scale
    if (currentScore <= 100) return 100; // 1-100 scale

    // For edge cases, round up to nearest 10
    return Math.ceil(currentScore / 10) * 10;
});

// 🎯 DYNAMIC: Performance level based on flexible max score
const performance = computed(() => {
    const score = props.evaluation?.total_score || 0;
    const percentage = (score / maxPossibleScore.value) * 100;

    if (percentage >= 80) return {
        label: 'Exceptional',
        class: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        icon: '🏆',
        description: 'Outstanding performance that exceeds expectations'
    }
    if (percentage >= 60) return {
        label: 'Excellent',
        class: 'bg-green-100 text-green-800 border-green-200',
        icon: '⭐',
        description: 'Consistently high performance'
    }
    if (percentage >= 40) return {
        label: 'Good',
        class: 'bg-blue-100 text-blue-800 border-blue-200',
        icon: '👍',
        description: 'Solid performance meeting expectations'
    }
    if (percentage >= 20) return {
        label: 'Average',
        class: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        icon: '📊',
        description: 'Performance meets basic requirements'
    }
    return {
        label: 'Needs Improvement',
        class: 'bg-red-100 text-red-800 border-red-200',
        icon: '📈',
        description: 'Performance requires development'
    }
});

const formatCurrency = (amount: number) => {
    return `$${parseFloat(amount?.toString() || '0').toFixed(2)}`
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow p-6 mb-6 border border-gray-200">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ evaluation?.course.name }}</h1>
                        <p class="mt-2 text-gray-600">Performance Evaluation Details</p>
                        <div class="mt-4 flex items-center space-x-4">
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border"
                                :class="performance.class"
                            >
                                {{ performance.icon }} {{ performance.label }}
                            </span>
                            <span class="text-sm text-gray-500">
                                Evaluated on {{ formatDate(evaluation?.evaluation_date || evaluation?.created_at) }}
                            </span>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <Link
                            :href="route('user.evaluations.index')"
                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition"
                        >
                            ← Back to Evaluations
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Overall Performance -->
                    <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Overall Performance</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="text-center p-6 bg-gray-50 rounded-lg">
                                <div class="text-4xl font-bold text-blue-600">{{ evaluation?.total_score || 0 }}</div>
                                <div class="text-sm text-gray-500 mt-1">Total Score (out of 25)</div>
                                <div class="mt-3 w-full bg-gray-200 rounded-full h-3">
                                    <div
                                        class="h-3 rounded-full transition-all duration-500"
                                        :class="{
                                            'bg-emerald-500': (evaluation?.total_score || 0) >= 20,
                                            'bg-green-500': (evaluation?.total_score || 0) >= 15 && (evaluation?.total_score || 0) < 20,
                                            'bg-blue-500': (evaluation?.total_score || 0) >= 10 && (evaluation?.total_score || 0) < 15,
                                            'bg-yellow-500': (evaluation?.total_score || 0) >= 5 && (evaluation?.total_score || 0) < 10,
                                            'bg-red-500': (evaluation?.total_score || 0) < 5
                                        }"
                                        :style="`width: ${Math.round(((evaluation?.total_score || 0) / 25) * 100)}%`"
                                    ></div>
                                </div>
                            </div>

                            <div class="text-center p-6 bg-green-50 rounded-lg">
                                <div class="text-4xl font-bold text-green-600">{{ formatCurrency(evaluation?.incentive_amount || 0) }}</div>
                                <div class="text-sm text-gray-500 mt-1">Incentive Earned</div>
                                <div class="mt-2 text-xs text-green-700">
                                    Based on your performance score
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                            <h3 class="font-medium text-gray-900 mb-2">Performance Level: {{ performance.label }}</h3>
                            <p class="text-sm text-gray-600">{{ performance.description }}</p>
                        </div>
                    </div>

                    <!-- Category Breakdown -->
                    <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Category Breakdown</h2>

                        <div v-if="evaluation?.categories && evaluation.categories.length > 0" class="space-y-4">
                            <div
                                v-for="category in evaluation.categories"
                                :key="category.id"
                                class="border border-gray-200 rounded-lg p-4"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="font-medium text-gray-900">{{ category.category_name }}</h3>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-lg text-blue-600">{{ category.score }}</span>
                                        <span class="text-sm text-gray-500">points</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                                        <span>{{ category.evaluation_type }}</span>
                                        <span>{{ category.score }} points</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            class="h-2 rounded-full bg-blue-500 transition-all duration-300"
                                            :style="`width: ${Math.min((category.score / 5) * 100, 100)}%`"
                                        ></div>
                                    </div>
                                </div>

                                <div v-if="category.comments" class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                    <p class="text-sm text-blue-800">
                                        <strong>Feedback:</strong> {{ category.comments }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-gray-500">
                            <p>No category details available for this evaluation.</p>
                        </div>
                    </div>

                    <!-- Manager Notes -->
                    <div v-if="evaluation?.notes" class="bg-white rounded-lg shadow p-6 border border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Manager's Overall Notes</h2>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-gray-800 whitespace-pre-line">{{ evaluation.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Evaluation Info -->
                    <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Evaluation Information</h3>

                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-700">Course</dt>
                                <dd class="text-sm text-gray-900">{{ evaluation?.course.name }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-700">Evaluation Date</dt>
                                <dd class="text-sm text-gray-900">{{ formatDate(evaluation?.evaluation_date || evaluation?.created_at) }}</dd>
                            </div>

                            <div v-if="evaluation?.evaluated_by">
                                <dt class="text-sm font-medium text-gray-700">Evaluated By</dt>
                                <dd class="text-sm text-gray-900">{{ evaluation.evaluated_by.name }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-700">Performance Level</dt>
                                <dd>
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border mt-1"
                                        :class="performance.class"
                                    >
                                        {{ performance.icon }} {{ performance.label }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-700">Score</dt>
                                <dd class="text-lg font-bold text-blue-600">{{ evaluation?.total_score || 0 }}/25</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-700">Incentive Earned</dt>
                                <dd class="text-lg font-bold text-green-600">{{ formatCurrency(evaluation?.incentive_amount || 0) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Performance Insights -->
                    <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Insights</h3>

                        <div class="space-y-4">
                            <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-blue-900">Score Percentage</p>
                                    <p class="text-xs text-blue-700">{{ Math.round(((evaluation?.total_score || 0) / 25) * 100) }}% of maximum possible score</p>
                                </div>
                            </div>

                            <div class="flex items-center p-3 bg-green-50 rounded-lg">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-900">Incentive Rate</p>
                                    <p class="text-xs text-green-700">{{ formatCurrency((evaluation?.incentive_amount || 0) / (evaluation?.total_score || 1)) }} per point</p>
                                </div>
                            </div>

                            <div class="flex items-center p-3 bg-purple-50 rounded-lg">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-purple-900">Categories Evaluated</p>
                                    <p class="text-xs text-purple-700">{{ evaluation?.categories?.length || 0 }} performance areas</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>

                        <div class="space-y-3">
                            <Link
                                :href="route('user.evaluations.index')"
                                class="block w-full text-center bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition"
                            >
                                View All Evaluations
                            </Link>

                            <Link
                                :href="route('user.profile.index')"
                                class="block w-full text-center bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition"
                            >
                                Back to Profile
                            </Link>

                            <Link
                                :href="route('courses.index')"
                                class="block w-full text-center bg-green-100 text-green-700 px-4 py-2 rounded-lg hover:bg-green-200 transition"
                            >
                                Browse Courses
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
