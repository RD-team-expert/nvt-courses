<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-linear-to-br from-slate-50 to-blue-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header Section -->
                <div class="mb-8 text-center lg:text-left">
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Available Quizzes</h1>
                    <p class="text-lg text-gray-600">Test your knowledge and track your progress</p>
                </div>

                <!-- Stats Overview -->
                <div v-if="quizzes.data && quizzes.data.length" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Quizzes</p>
                                <p class="text-2xl font-bold text-gray-900">{{ quizzes.data.length }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Completed</p>
                                <p class="text-2xl font-bold text-gray-900">{{ completedQuizzes }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pending</p>
                                <p class="text-2xl font-bold text-gray-900">{{ pendingQuizzes }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div v-if="quizzes.data && quizzes.data.length" class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-gray-100">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search quizzes..."
                                    class="w-full sm:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>

                            <select
                                v-model="selectedCourse"
                                class="w-full sm:w-48 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="">All Courses</option>
                                <option v-for="course in uniqueCourses" :key="course.id" :value="course.id">
                                    {{ course.name }}
                                </option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="toggleView"
                                class="p-2 text-gray-400 hover:text-gray-600"
                                :title="viewMode === 'grid' ? 'Switch to list view' : 'Switch to grid view'"
                            >
                                <svg v-if="viewMode === 'grid'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quiz Cards -->
                <div v-if="filteredQuizzes.length" class="mb-8">
                    <div :class="viewMode === 'grid' ? 'grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6' : 'space-y-4'">
                        <div
                            v-for="quiz in filteredQuizzes"
                            :key="quiz.id"
                            class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-200 transition-all duration-200 overflow-hidden"
                        >
                            <!-- Quiz Status Indicator -->
                            <div class="flex">
                                <div :class="[
                                    'w-1 shrink-0',
                                    getQuizStatusColor(quiz)
                                ]"></div>

                                <div class="flex-1 p-6">
                                    <!-- Quiz Header -->
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1 pr-4">
                                            <h2 class="text-xl font-semibold text-gray-900 mb-2">
                                                {{ quiz.title }}
                                            </h2>
                                            <p class="text-sm text-gray-600 mb-3 line-clamp-3">
                                                {{ quiz.description || 'No description available' }}
                                            </p>
                                        </div>
                                        <div :class="[
        'px-3 py-1 rounded-full text-xs font-medium shrink-0',
        getQuizStatusBadge(quiz)
    ]">
                                            {{ getQuizStatus(quiz) }}
                                        </div>
                                    </div>

                                    <!-- Quiz Metrics -->
                                    <div class="grid grid-cols-2 gap-4 mb-4">
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <p class="text-xs text-gray-500 mb-1">Course</p>
                                            <p class="text-sm font-medium text-gray-900">{{ quiz.course.name }}</p>
                                        </div>
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <p class="text-xs text-gray-500 mb-1">Total Points</p>
                                            <p class="text-sm font-medium text-gray-900">{{ quiz.total_points }}</p>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="mb-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-xs text-gray-500">Progress</span>
                                            <span class="text-xs text-gray-700">{{ quiz.attempts }}/3 attempts</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div
                                                :class="[
                                                    'h-2 rounded-full transition-all duration-300',
                                                    quiz.has_passed ? 'bg-green-500' : 'bg-linear-to-r from-indigo-500 to-purple-600'
                                                ]"
                                                :style="{ width: quiz.has_passed ? '100%' : `${(quiz.attempts / 3) * 100}%` }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Quiz Details -->
                                    <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
                                        <span>Pass: {{ quiz.pass_threshold }}%</span>
                                        <span v-if="quiz.time_limit">{{ quiz.time_limit }} min</span>
                                    </div>

                                    <!-- Action Button -->
                                    <Link
                                        :href="route('quizzes.show', quiz.id)"
                                        :class="[
                                            'w-full inline-flex items-center justify-center px-4 py-3 rounded-lg font-medium text-sm transition-all duration-200 focus:outline-hidden focus:ring-2 focus:ring-offset-2',
                                            canTakeQuiz(quiz)
                                                ? 'bg-linear-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white focus:ring-indigo-500'
                                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        ]"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M19 10a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ getButtonText(quiz) }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="!quizzes.data || !quizzes.data.length" class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No quizzes available</h3>
                    <p class="text-gray-500 mb-4">There are no quizzes available for your enrolled courses at the moment.</p>
                </div>

                <!-- No Results State -->
                <div v-else class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No quizzes found</h3>
                    <p class="text-gray-500 mb-4">Try adjusting your search or filter criteria.</p>
                    <button
                        @click="clearFilters"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                    >
                        Clear Filters
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="quizzes.links && quizzes.data && quizzes.data.length" class="flex justify-center">
                    <pagination :links="quizzes.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import AppLayout from "@/layouts/AppLayout.vue";

export default {
    components: {
        AppLayout,
        Link,
        Pagination,
    },
    props: {
        quizzes: {
            type: Object,
            required: true,
        },
    },
    setup(props) {
        const searchQuery = ref('');
        const selectedCourse = ref('');
        const viewMode = ref('grid');

        const breadcrumbs = [
            { name: 'Quizzes', route: null },
        ];

        // Computed properties
        const uniqueCourses = computed(() => {
            if (!props.quizzes.data || !props.quizzes.data.length) return [];
            const courses = props.quizzes.data.map(quiz => quiz.course);
            return courses.filter((course, index, self) =>
                index === self.findIndex(c => c.id === course.id)
            );
        });

        const filteredQuizzes = computed(() => {
            if (!props.quizzes.data || !props.quizzes.data.length) return [];

            let filtered = props.quizzes.data;

            if (searchQuery.value) {
                filtered = filtered.filter(quiz =>
                    quiz.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                    (quiz.description && quiz.description.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
                    quiz.course.name.toLowerCase().includes(searchQuery.value.toLowerCase())
                );
            }

            if (selectedCourse.value) {
                filtered = filtered.filter(quiz => quiz.course.id === selectedCourse.value);
            }

            return filtered;
        });

        const completedQuizzes = computed(() => {
            if (!props.quizzes.data || !props.quizzes.data.length) return 0;
            // ✅ Count quizzes that are passed OR reached max attempts
            return props.quizzes.data.filter(quiz =>
                quiz.has_passed || quiz.attempts >= 3
            ).length;
        });

        const pendingQuizzes = computed(() => {
            if (!props.quizzes.data || !props.quizzes.data.length) return 0;
            // ✅ Count quizzes that are not passed AND under max attempts
            return props.quizzes.data.filter(quiz =>
                !quiz.has_passed && quiz.attempts < 3
            ).length;
        });

        // Methods
        const toggleView = () => {
            viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid';
        };

        const clearFilters = () => {
            searchQuery.value = '';
            selectedCourse.value = '';
        };

        const canTakeQuiz = (quiz) => {
            // ✅ Cannot retake if already passed
            if (quiz.has_passed) {
                return false;
            }
            // Can take if under 3 attempts and hasn't passed
            return quiz.attempts < 3;
        };

        const getQuizStatus = (quiz) => {
            // ✅ Show "Passed" if user has passed
            if (quiz.has_passed) return 'Passed';
            if (quiz.attempts >= 3) return 'Failed';
            if (quiz.attempts > 0) return 'In Progress';
            return 'Not Started';
        };

        const getQuizStatusColor = (quiz) => {
            // ✅ Green for passed, red for failed after max attempts
            if (quiz.has_passed) return 'bg-green-500';
            if (quiz.attempts >= 3) return 'bg-red-500';
            if (quiz.attempts > 0) return 'bg-yellow-500';
            return 'bg-blue-500';
        };

        const getQuizStatusBadge = (quiz) => {
            // ✅ Green for passed, red for failed
            if (quiz.has_passed) return 'bg-green-100 text-green-800';
            if (quiz.attempts >= 3) return 'bg-red-100 text-red-800';
            if (quiz.attempts > 0) return 'bg-yellow-100 text-yellow-800';
            return 'bg-blue-100 text-blue-800';
        };

        const getButtonText = (quiz) => {
            // ✅ Show appropriate button text
            if (quiz.has_passed) return 'View Results';
            if (quiz.attempts >= 3) return 'View Results';
            if (quiz.attempts > 0) return 'Continue Quiz';
            return 'Start Quiz';
        };

        return {
            breadcrumbs,
            searchQuery,
            selectedCourse,
            viewMode,
            uniqueCourses,
            filteredQuizzes,
            completedQuizzes,
            pendingQuizzes,
            toggleView,
            clearFilters,
            canTakeQuiz,
            getQuizStatus,
            getQuizStatusColor,
            getQuizStatusBadge,
            getButtonText,
        };
    },
};
</script>

<style scoped>
/* Ensure proper display without conflicting with existing styles */
.quiz-card {
    display: block !important;
    visibility: visible !important;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
/* Basic transition effects */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Hover effects */
.hover\:shadow-md:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
}

.hover\:border-gray-200:hover {
    border-color: rgb(229 231 235);
}

/* Focus styles */
.focus\:ring-2:focus {
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.5);
}

.focus\:border-indigo-500:focus {
    border-color: rgb(99 102 241);
}

/* Text truncation */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .text-4xl {
        font-size: 2.25rem;
        line-height: 2.5rem;
    }

    .px-6 {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .py-6 {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
}
</style>
