<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { computed } from 'vue'
import type { BreadcrumbItemType } from '@/types'

const props = defineProps({
    user: Object,
    evaluations: Object,
    stats: Object,
})

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'My Profile', href: route('user.profile.index') },
    { name: 'My Evaluations', href: '#' }
]

// 🎯 DYNAMIC: Calculate max score from actual data
const maxPossibleScore = computed(() => {
    if (!props.evaluations?.data || props.evaluations.data.length === 0) {
        return 25; // Default fallback
    }

    // ✅ FIXED: Changed 'eval' to 'evaluation'
    const allScores = props.evaluations.data.map(evaluation => evaluation.total_score || 0);
    const highestScore = Math.max(...allScores);

    // Smart detection based on score patterns
    if (highestScore <= 5) return 5;     // 1-5 scale
    if (highestScore <= 10) return 10;   // 1-10 scale
    if (highestScore <= 20) return 20;   // 1-20 scale
    if (highestScore <= 25) return 25;   // 1-25 scale (current)
    if (highestScore <= 50) return 50;   // 1-50 scale
    if (highestScore <= 100) return 100; // 1-100 scale

    // For edge cases, round up to nearest 10
    return Math.ceil(highestScore / 10) * 10;
});

// 🎯 DYNAMIC: Performance level based on flexible max score
const getPerformanceLevel = (score: number) => {
    const percentage = (score / maxPossibleScore.value) * 100;

    if (percentage >= 80) return {
        label: 'Exceptional',
        class: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        icon: '🏆'
    }
    if (percentage >= 60) return {
        label: 'Excellent',
        class: 'bg-green-100 text-green-800 border-green-200',
        icon: '⭐'
    }
    if (percentage >= 40) return {
        label: 'Good',
        class: 'bg-blue-100 text-blue-800 border-blue-200',
        icon: '👍'
    }
    if (percentage >= 20) return {
        label: 'Average',
        class: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        icon: '📊'
    }
    return {
        label: 'Needs Improvement',
        class: 'bg-red-100 text-red-800 border-red-200',
        icon: '📈'
    }
}

// 🎯 DYNAMIC: Score color based on flexible max score
const getScoreColor = (score: number) => {
    const percentage = (score / maxPossibleScore.value) * 100;
    if (percentage >= 80) return 'text-emerald-600'
    if (percentage >= 60) return 'text-green-600'
    if (percentage >= 40) return 'text-yellow-600'
    return 'text-red-600'
}

// Format currency
const formatCurrency = (amount: number) => {
    return `$${parseFloat(amount?.toString() || '0').toFixed(2)}`
}

// Format date
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

// Calculate trend
const calculateTrend = computed(() => {
    if (!props.evaluations?.data || props.evaluations.data.length < 2) return null

    const latest = props.evaluations.data[0].total_score
    const previous = props.evaluations.data[1].total_score
    const diff = latest - previous

    return {
        value: diff,
        isPositive: diff > 0,
        isNeutral: diff === 0,
        percentage: previous > 0 ? Math.abs((diff / previous) * 100) : 0
    }
})
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-linear-to-r from-purple-500 to-indigo-600 rounded-lg shadow p-6 mb-6 text-white">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold">My Performance Evaluations</h1>
                        <p class="mt-2 text-purple-100">Track your performance progress and achievements</p>
                    </div>
                    <div class="flex space-x-3">
                        <Link
                            :href="route('user.profile.index')"
                            class="bg-white bg-opacity-20 text-white px-4 py-2 rounded-lg hover:bg-opacity-30 transition border border-white border-opacity-30"
                        >
                            ← Back to Profile
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Performance Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Evaluations</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats?.total_evaluations || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Average Score</p>
                            <p class="text-2xl font-bold" :class="getScoreColor(stats?.average_score || 0)">
                                {{ stats?.average_score || '0.0' }}<span class="text-sm text-gray-500">/{{ maxPossibleScore }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Incentives</p>
                            <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats?.total_incentives || 0) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="shrink-0">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Best Score</p>
                            <div class="flex items-center space-x-2">
                                <p class="text-2xl font-bold" :class="getScoreColor(stats?.best_score || 0)">
                                    {{ stats?.best_score || '0' }}
                                </p>
                                <div v-if="calculateTrend && !calculateTrend.isNeutral" class="flex items-center text-xs">
                                    <svg v-if="calculateTrend.isPositive" class="h-3 w-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7m0 10H7" />
                                    </svg>
                                    <svg v-else class="h-3 w-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10m0-10h10" />
                                    </svg>
                                    <span :class="calculateTrend.isPositive ? 'text-green-500' : 'text-red-500'">
                                        {{ calculateTrend.percentage.toFixed(1) }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Evaluations List -->
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Evaluation History</h2>
                    <p class="text-sm text-gray-600 mt-1">Your performance evaluations and feedback</p>
                </div>

                <div v-if="evaluations && evaluations.data && evaluations.data.length > 0" class="divide-y divide-gray-200">
                    <div
                        v-for="(evaluation, index) in evaluations.data"
                        :key="evaluation.id"
                        class="p-6 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <h3 class="text-lg font-medium text-gray-900">{{ evaluation.course.name }}</h3>
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border"
                                        :class="getPerformanceLevel(evaluation.total_score).class"
                                    >
                                        {{ getPerformanceLevel(evaluation.total_score).icon }} {{ getPerformanceLevel(evaluation.total_score).label }}
                                    </span>
                                    <span v-if="index === 0" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                        Latest
                                    </span>
                                </div>

                                <div class="flex items-center space-x-6 mb-3">
                                    <div>
                                        <p class="text-2xl font-bold" :class="getScoreColor(evaluation.total_score)">
                                            {{ evaluation.total_score }}
                                        </p>
                                        <p class="text-xs text-gray-500">Total Score</p>
                                    </div>
                                    <div>
                                        <p class="text-xl font-semibold text-green-600">{{ formatCurrency(evaluation.incentive_amount) }}</p>
                                        <p class="text-xs text-gray-500">Incentive Earned</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ formatDate(evaluation.evaluation_date) }}</p>
                                        <p class="text-xs text-gray-500">Evaluation Date</p>
                                    </div>
                                    <div v-if="evaluation.evaluated_by">
                                        <p class="text-sm font-medium text-gray-900">{{ evaluation.evaluated_by.name }}</p>
                                        <p class="text-xs text-gray-500">Evaluated By</p>
                                    </div>
                                </div>

                                <!-- 🎯 DYNAMIC: Score Progress Bar -->
                                <div class="mb-3">
                                    <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                                        <span>Performance Score</span>
                                        <span>{{ evaluation.total_score }}/{{ maxPossibleScore }} ({{ Math.round((evaluation.total_score / maxPossibleScore) * 100) }}%)</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            class="h-2 rounded-full transition-all duration-500"
                                            :class="{
                                                'bg-emerald-500': (evaluation.total_score / maxPossibleScore) >= 0.8,
                                                'bg-green-500': (evaluation.total_score / maxPossibleScore) >= 0.6 && (evaluation.total_score / maxPossibleScore) < 0.8,
                                                'bg-blue-500': (evaluation.total_score / maxPossibleScore) >= 0.4 && (evaluation.total_score / maxPossibleScore) < 0.6,
                                                'bg-yellow-500': (evaluation.total_score / maxPossibleScore) >= 0.2 && (evaluation.total_score / maxPossibleScore) < 0.4,
                                                'bg-red-500': (evaluation.total_score / maxPossibleScore) < 0.2
                                            }"
                                            :style="`width: ${Math.round((evaluation.total_score / maxPossibleScore) * 100)}%`"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Notes Preview -->
                                <div v-if="evaluation.notes" class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
                                    <p class="text-sm text-blue-800">
                                        <strong>Manager Notes:</strong>
                                        <span v-if="evaluation.notes.length > 150">
                                            {{ evaluation.notes.substring(0, 150) }}...
                                        </span>
                                        <span v-else>{{ evaluation.notes }}</span>
                                    </p>
                                </div>

                                <!-- Categories Preview -->
                                <div v-if="evaluation.categories && evaluation.categories.length > 0" class="flex flex-wrap gap-2">
                                    <div
                                        v-for="category in evaluation.categories.slice(0, 4)"
                                        :key="category.id"
                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200"
                                    >
                                        {{ category.category_name }}: {{ category.score }}
                                    </div>
                                    <span v-if="evaluation.categories.length > 4" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500">
                                        +{{ evaluation.categories.length - 4 }} more
                                    </span>
                                </div>
                            </div>

                            <div class="ml-6">
                                <Link
                                    :href="route('user.evaluations.show', evaluation.id)"
                                    class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition text-sm font-medium"
                                >
                                    View Details
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-12 text-center text-gray-500">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No evaluations yet</h3>
                    <p class="mt-2 text-sm text-gray-500">Your performance evaluations will appear here once they're completed by your manager.</p>
                    <div class="mt-6">
                        <Link
                            :href="route('user.profile.index')"
                            class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition"
                        >
                            Back to Profile
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="evaluations && evaluations.links && evaluations.data.length > 0" class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing {{ evaluations.from }} to {{ evaluations.to }} of {{ evaluations.total }} evaluations
                        </div>
                        <div class="flex space-x-1">
                            <Link
                                v-for="link in evaluations.links"
                                :key="link.label"
                                :href="link.url"
                                class="px-3 py-2 text-sm rounded transition-colors"
                                :class="{
                                    'bg-blue-500 text-white': link.active,
                                    'text-blue-500 hover:bg-blue-50': !link.active && link.url,
                                    'text-gray-400 cursor-not-allowed': !link.url
                                }"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
