<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900">Quiz Attempts</h1>
                <Link
                    :href="route('admin.quizzes.index')"
                    class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition-colors duration-200 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Quizzes
                </Link>
            </div>

            <!-- Filters -->
            <div class="mb-6 rounded-xl bg-white p-6 shadow-sm">
                <form @submit.prevent="applyFilters" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="user_id" class="mb-1 block text-sm font-medium text-gray-700">User ID</label>
                        <input
                            v-model="filters.user_id"
                            id="user_id"
                            type="number"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                            placeholder="Enter User ID"
                        />
                    </div>
                    <div>
                        <label for="quiz_id" class="mb-1 block text-sm font-medium text-gray-700">Quiz ID</label>
                        <input
                            v-model="filters.quiz_id"
                            id="quiz_id"
                            type="number"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                            placeholder="Enter Quiz ID"
                        />
                    </div>
                    <div>
                        <label for="passed" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                        <select
                            v-model="filters.passed"
                            id="passed"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm transition-colors duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="">All</option>
                            <option value="1">Passed</option>
                            <option value="0">Failed</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3 sm:col-span-3">
                        <button
                            type="button"
                            @click="clearFilters"
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300"
                        >
                            Clear Filters
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Attempts Table -->
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">Attempts ({{ attempts.total }})</h2>
                    <div v-if="quiz" class="text-sm text-gray-600">
                        Pass Threshold: {{ quiz.pass_threshold }}%
                    </div>
                </div>

                <div v-if="attempts.data && attempts.data.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Quiz</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Auto Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Manual Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Completed At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="attempt in attempts.data" :key="attempt.id" class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                <div>
                                    <div class="font-medium">{{ attempt.user.name }}</div>
                                    <div class="text-gray-500">{{ attempt.user.email }}</div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                {{ attempt.quiz.title }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                {{ attempt.score }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                {{ attempt.manual_score }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                <span class="font-medium">{{ attempt.total_score }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <span
                                        :class="{
                                            'inline-flex rounded-full px-2 text-xs font-semibold leading-5': true,
                                            'bg-green-100 text-green-800': attempt.passed,
                                            'bg-red-100 text-red-800': !attempt.passed,
                                        }"
                                    >
                                        {{ attempt.passed ? 'Passed' : 'Failed' }}
                                    </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ attempt.completed_at || 'Not completed' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                <Link
                                    :href="route('admin.quiz-attempts.show', attempt.id)"
                                    class="text-indigo-600 transition-colors duration-200 hover:text-indigo-900"
                                >
                                    View Details
                                </Link>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No attempts found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            Object.keys(currentFilters).length > 0
                                ? 'Try adjusting your filters to see more results.'
                                : 'No quiz attempts have been made yet.'
                        }}
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="attempts.data && attempts.data.length && attempts.links" class="mt-6">
                    <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                        <div class="flex flex-1 justify-between sm:hidden">
                            <button
                                v-if="attempts.prev_page_url"
                                @click="router.visit(attempts.prev_page_url, { preserveState: true })"
                                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Previous
                            </button>
                            <button
                                v-if="attempts.next_page_url"
                                @click="router.visit(attempts.next_page_url, { preserveState: true })"
                                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Next
                            </button>
                        </div>
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ attempts.from }}</span>
                                    to
                                    <span class="font-medium">{{ attempts.to }}</span>
                                    of
                                    <span class="font-medium">{{ attempts.total }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                    <button
                                        v-for="link in attempts.links"
                                        :key="link.label"
                                        @click="link.url && router.visit(link.url, { preserveState: true })"
                                        :disabled="!link.url"
                                        class="relative inline-flex items-center px-3 py-2 text-sm font-medium transition-colors duration-200"
                                        :class="{
                                            'z-10 border-indigo-600 bg-indigo-600 text-white': link.active,
                                            'border-gray-300 bg-white text-gray-500 hover:bg-gray-50': !link.active && link.url,
                                            'cursor-not-allowed border-gray-300 bg-gray-100 text-gray-400': !link.url,
                                            'rounded-l-md': link.label === '&laquo; Previous',
                                            'rounded-r-md': link.label === 'Next &raquo;',
                                            'border-b border-r border-t': true,
                                            'border-l': link.label === '&laquo; Previous',
                                        }"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

export default {
    components: {
        AdminLayout,
        Link,
    },
    props: {
        attempts: {
            type: Object,
            required: true,
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        quiz: {
            type: Object,
            default: null, // Added to receive pass_threshold
        },
    },
    setup(props) {
        const filters = ref({
            user_id: props.filters.user_id || '',
            quiz_id: props.filters.quiz_id || '',
            passed: props.filters.passed || '',
        });

        const currentFilters = computed(() => {
            const activeFilters = {};
            Object.keys(filters.value).forEach((key) => {
                if (filters.value[key] !== '' && filters.value[key] !== null && filters.value[key] !== undefined) {
                    activeFilters[key] = filters.value[key];
                }
            });
            return activeFilters;
        });

        const applyFilters = () => {
            const queryParams = {};
            Object.keys(filters.value).forEach((key) => {
                if (filters.value[key] !== '' && filters.value[key] !== null && filters.value[key] !== undefined) {
                    queryParams[key] = filters.value[key];
                }
            });

            router.get(route('admin.quiz-attempts.index'), queryParams, {
                preserveState: true,
                preserveScroll: true,
            });
        };

        const clearFilters = () => {
            filters.value = {
                user_id: '',
                quiz_id: '',
                passed: '',
            };

            router.get(
                route('admin.quiz-attempts.index'),
                {},
                {
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        };

        // Watch for prop changes and update local filters
        watch(
            () => props.filters,
            (newFilters) => {
                filters.value = {
                    user_id: newFilters.user_id || '',
                    quiz_id: newFilters.quiz_id || '',
                    passed: newFilters.passed || '',
                };
            },
            { deep: true },
        );

        // Breadcrumbs
        const breadcrumbs = [
            { name: 'Quizzes', route: 'admin.quizzes.index' },
            { name: 'Attempts', route: null },
        ];

        return {
            filters,
            currentFilters,
            applyFilters,
            clearFilters,
            router,
            breadcrumbs,
        };
    },
};
</script>

<style scoped>
/* General Layout */
.max-w-7xl {
    @apply px-4 sm:px-6 lg:px-8;
}

/* Form Styling */
.bg-white {
    @apply transition-all duration-200;
}

.bg-gray-50 {
    @apply transition-all duration-200;
}

input,
select {
    @apply transition-colors duration-200;
}

input:focus,
select:focus {
    @apply outline-none;
}

button {
    @apply transition-colors duration-200;
}

button:disabled {
    @apply cursor-not-allowed opacity-50;
}

/* Table Styling */
table {
    @apply w-full;
}

th,
td {
    @apply transition-colors duration-200;
}

tr:hover {
    @apply transition-colors duration-200;
}

/* Status badges */
.status-badge {
    @apply inline-flex rounded-full px-2 text-xs font-semibold leading-5;
}

/* Pagination */
nav button {
    @apply transition-all duration-200;
}

nav button:hover:not(:disabled) {
    @apply scale-105 transform;
}

/* Responsive Adjustments */
@media (max-width: 640px) {
    .grid-cols-1 {
        @apply space-y-4;
    }

    .sm:grid-cols-3 {
        @apply grid-cols-1;
    }

    .px-6 {
        @apply px-4;
    }

    .py-3,
    .py-4 {
        @apply py-2;
    }

    .text-sm {
        @apply text-xs;
    }

    table {
        @apply text-xs;
    }

    .max-w-7xl {
        @apply px-2;
    }
}

@media (max-width: 480px) {
    .px-4 {
        @apply px-2;
    }

    .py-2 {
        @apply py-1;
    }

    h1 {
        @apply text-2xl;
    }

    h2 {
        @apply text-base;
    }
}

/* Loading state */
.loading {
    @apply pointer-events-none opacity-50;
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeIn 0.3s ease-out;
}
</style>
