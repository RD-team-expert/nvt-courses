<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/layouts/AdminLayout.vue'
import { type BreadcrumbItemType } from '@/types'
import { debounce } from 'lodash'
import Pagination from '@/components/Pagination.vue'

const props = defineProps({
    attempts: Object,
    quizzes: Array,
    filters: Object,
})

// Define breadcrumbs
const breadcrumbs: BreadcrumbItemType[] = [
    { name: 'Dashboard', href: route('dashboard') },
    { name: 'Reports & Analytics', href: route('admin.reports.index') },
    { name: 'Quiz Attempts', href: route('admin.reports.quiz-attempts') },
]

// Filter state
const filters = ref({
    quiz_id: props.filters?.quiz_id || '',
    status: props.filters?.status || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
})

// Apply filters with debounce
const applyFilters = debounce(() => {
    router.get(route('admin.reports.quiz-attempts'), filters.value, {
        preserveState: true,
        replace: true,
    })
}, 500)

// Watch for filter changes
watch(filters, () => {
    applyFilters()
}, { deep: true })

// Reset filters
const resetFilters = () => {
    filters.value = {
        quiz_id: '',
        status: '',
        date_from: '',
        date_to: '',
    }
    applyFilters()
}

// Export to CSV
const exportToCsv = () => {
    const queryParams = new URLSearchParams(filters.value).toString()
    window.location.href = route('admin.reports.export.quiz-attempts') + '?' + queryParams
}

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) return '—'
    const date = new Date(dateString)
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

// Handle pagination
const handlePageChange = (page) => {
    router.get(route('admin.reports.quiz-attempts'), {
        ...filters.value,
        page,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
        onSuccess: () => {
            document.querySelector('.bg-white.rounded-lg.shadow')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
        },
    })
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 sm:px-0">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 gap-4 sm:gap-0">
                <h1 class="text-xl sm:text-2xl font-bold">Quiz Attempts Report</h1>
                <button
                    @click="exportToCsv"
                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition flex items-center w-full sm:w-auto justify-center sm:justify-start"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 mr-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    Export to CSV
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow mb-4 sm:mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Filter Attempts</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 sm:gap-6">
                    <div>
                        <label for="quiz_filter" class="block text-sm font-medium text-gray-700 mb-1">Quiz</label>
                        <select
                            id="quiz_filter"
                            v-model="filters.quiz_id"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">All Quizzes</option>
                            <option v-for="quiz in quizzes" :key="quiz.id" :value="quiz.id">{{ quiz.title }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="status_filter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            id="status_filter"
                            v-model="filters.status"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">All Statuses</option>
                            <option value="passed">Passed</option>
                            <option value="failed">Failed</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>

                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input
                            id="date_from"
                            type="date"
                            v-model="filters.date_from"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input
                            id="date_to"
                            type="date"
                            v-model="filters.date_to"
                            class="border px-3 py-2 rounded w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div class="flex items-end md:col-span-4">
                        <button
                            @click="resetFilters"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition"
                        >
                            Reset Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Attempts Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            User
                        </th>
                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                            Quiz
                        </th>
                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                            Score
                        </th>
                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                            Attempt
                        </th>
                        <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Completed
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="attempts.data.length === 0">
                        <td colspan="6" class="px-4 sm:px-6 py-4 text-center text-gray-500">No attempt records found</td>
                    </tr>
                    <tr v-else v-for="(attempt, i) in attempts.data" :key="i" class="hover:bg-gray-50">
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ attempt.user_name }}</div>
                                    <div class="text-xs text-gray-500 hidden sm:block">{{ attempt.user_email }}</div>
                                    <div class="text-xs text-gray-500 sm:hidden mt-1">{{ attempt.quiz_title }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden sm:table-cell">{{ attempt.quiz_title }}</td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden md:table-cell">{{ attempt.total_score }} / {{ attempt.quiz_total_points }} ({{ Math.round((attempt.total_score / attempt.quiz_total_points) * 100) }}%)</td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                <span
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="{
                    'bg-green-100 text-green-800': attempt.passed,
                    'bg-red-100 text-red-800': !attempt.passed && attempt.completed_at,
                    'bg-yellow-100 text-yellow-800': !attempt.completed_at,
                  }"
                >
                  {{ attempt.completed_at ? (attempt.passed ? 'Passed' : 'Failed') : 'Pending' }}
                </span>
                        </td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900 hidden md:table-cell">{{ attempt.attempt_number }}</td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(attempt.completed_at) }}</td>
                    </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-4 sm:px-6 py-3 bg-white border-t border-gray-200">
                    <Pagination
                        v-if="attempts.data && attempts.data.length > 0 && attempts.last_page > 1"
                        :links="attempts.links"
                        @page-changed="handlePageChange"
                        class="mt-4"
                    />
                    <div v-if="attempts.data && attempts.data.length > 0" class="text-sm text-gray-600 mt-2">
                        Showing {{ attempts.from }} to {{ attempts.to }} of {{ attempts.total }} results
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
