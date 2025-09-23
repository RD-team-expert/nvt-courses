<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { computed } from 'vue'
import type { BreadcrumbItemType } from '@/types'

const props = defineProps({
    user: Object,
    managerRoles: Array,
    directReports: Array,
    stats: Object,
})

const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'My Team', href: '#' }
]

const getPerformanceLevel = (score: number) => {
    if (score >= 20) return { label: 'Exceptional', class: 'bg-emerald-100 text-emerald-800' }
    if (score >= 15) return { label: 'Excellent', class: 'bg-green-100 text-green-800' }
    if (score >= 10) return { label: 'Good', class: 'bg-blue-100 text-blue-800' }
    if (score >= 5) return { label: 'Average', class: 'bg-yellow-100 text-yellow-800' }
    return { label: 'Needs Improvement', class: 'bg-red-100 text-red-800' }
}

const formatCurrency = (amount: number) => {
    return `$${parseFloat(amount?.toString() || '0').toFixed(2)}`
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow p-6 mb-6 border border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">My Team</h1>
                        <p class="mt-2 text-gray-600">Manage and track your direct reports' performance</p>
                    </div>
                    <Link
                        :href="route('user.profile.index')"
                        class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition"
                    >
                        ← Back to Profile
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Team Members</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats?.total_reports || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Active Members</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats?.active_reports || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Departments</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats?.departments || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Avg Completion</p>
                            <p class="text-2xl font-bold text-gray-900">{{ Math.round(stats?.avg_course_completion || 0) }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Members List -->
            <div class="bg-white rounded-lg shadow border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Team Members</h2>
                </div>

                <div v-if="directReports && directReports.length > 0" class="divide-y divide-gray-200">
                    <div
                        v-for="report in directReports"
                        :key="report.id"
                        class="p-6 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0 h-12 w-12">
                                    <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-lg font-medium text-blue-600">
                                            {{ report.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ report.name }}</h3>
                                    <p class="text-sm text-gray-600">{{ report.email }}</p>
                                    <div class="flex items-center space-x-4 mt-1">
                                        <span v-if="report.level" class="text-xs text-gray-500">{{ report.level.code }} - {{ report.level.name }}</span>
                                        <span v-if="report.department" class="text-xs text-gray-500">{{ report.department.name }}</span>
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full"
                                              :class="{
                                                  'bg-green-100 text-green-800': report.status === 'active',
                                                  'bg-red-100 text-red-800': report.status === 'inactive'
                                              }">
                                            {{ report.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-6">
                                <!-- Course Stats -->
                                <div class="text-center">
                                    <div class="text-lg font-bold text-blue-600">{{ report.active_courses }}</div>
                                    <div class="text-xs text-gray-500">Active</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-green-600">{{ report.completed_courses }}</div>
                                    <div class="text-xs text-gray-500">Completed</div>
                                </div>

                                <!-- Latest Evaluation -->
                                <div v-if="report.latest_evaluation" class="text-center">
                                    <div class="text-lg font-bold text-purple-600">{{ report.latest_evaluation.total_score }}</div>
                                    <div class="text-xs text-gray-500">Latest Score</div>
                                    <div class="text-xs font-medium text-green-600">
                                        {{ formatCurrency(report.latest_evaluation.incentive_amount) }}
                                    </div>
                                </div>
                                <div v-else class="text-center text-gray-400">
                                    <div class="text-sm">No evaluations</div>
                                </div>

                                <!-- Actions -->
                                <div>
                                    <Link
                                        :href="route('user.team.show', report.id)"
                                        class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition text-sm"
                                    >
                                        View Details
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="p-8 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No team members</h3>
                    <p class="mt-1 text-sm text-gray-500">You don't have any direct reports assigned yet.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
