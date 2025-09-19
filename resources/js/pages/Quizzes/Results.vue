<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header Section -->
                <div class="text-center mb-8">
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Quiz Results</h1>
                    <p class="text-lg text-gray-600">
                        Performance review for <span class="font-semibold">"{{ attempt.quiz?.title || 'Quiz' }}"</span>
                    </p>
                </div>

                <!-- Score Overview Card -->
                <div class="bg-white rounded-xl shadow-lg p-8 mb-8 border border-gray-100">
                    <div class="text-center mb-6">
                        <!-- Score Circle -->
                        <div class="relative inline-flex items-center justify-center w-32 h-32 mb-4">
                            <svg class="transform -rotate-90 w-32 h-32">
                                <circle
                                    cx="64" cy="64" r="56"
                                    stroke="currentColor"
                                    stroke-width="8"
                                    fill="none"
                                    class="text-gray-200"
                                />
                                <circle
                                    cx="64" cy="64" r="56"
                                    stroke="currentColor"
                                    stroke-width="8"
                                    fill="none"
                                    :stroke-dasharray="circumference"
                                    :stroke-dashoffset="circumference - (scorePercentage / 100) * circumference"
                                    :class="scorePercentage >= (attempt.quiz?.pass_threshold || 70) ? 'text-green-500' : 'text-red-500'"
                                    class="transition-all duration-1000 ease-out"
                                />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-3xl font-bold text-gray-900">{{ Math.round(scorePercentage) }}%</span>
                                <span class="text-sm text-gray-500">Score</span>
                            </div>
                        </div>

                        <!-- Pass/Fail Status -->
                        <div class="mb-4">
                            <div :class="[
                                'inline-flex items-center px-6 py-3 rounded-full text-lg font-semibold',
                                attempt.passed
                                    ? 'bg-green-100 text-green-800 border-2 border-green-300'
                                    : 'bg-red-100 text-red-800 border-2 border-red-300'
                            ]">
                                <svg v-if="attempt.passed" class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <svg v-else class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                                {{ attempt.passed ? 'PASSED' : 'FAILED' }}
                            </div>
                        </div>

                        <p class="text-gray-600">
                            You scored <span class="font-bold text-gray-900">{{ attempt.total_score || 0 }}</span>
                            out of <span class="font-bold text-gray-900">{{ attempt.quiz?.total_points || 0 }}</span> points
                        </p>
                    </div>
                </div>

                <!-- Detailed Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Score Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Final Score</p>
                                <p class="text-xl font-bold text-gray-900">{{ attempt.total_score || 0 }}/{{ attempt.quiz?.total_points || 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Percentage Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex items-center">
                            <div :class="[
                                'p-3 rounded-full',
                                scorePercentage >= (attempt.quiz?.pass_threshold || 70) ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'
                            ]">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Percentage</p>
                                <p class="text-xl font-bold text-gray-900">{{ Math.round(scorePercentage) }}%</p>
                            </div>
                        </div>
                    </div>

                    <!-- Attempt Number Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Attempt</p>
                                <p class="text-xl font-bold text-gray-900">{{ attempt.attempt_number || userAttempts.length }}/3</p>
                            </div>
                        </div>
                    </div>

                    <!-- Attempts Left Card -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.001 8.001 0 01-15.356-2m15.356 2H15"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Attempts Left</p>
                                <p class="text-xl font-bold text-gray-900">{{ attemptsLeft }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Info Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Attempt Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center md:text-left">
                            <p class="text-sm text-gray-600 mb-1">Completed At</p>
                            <p class="text-sm font-medium text-gray-900">
                                {{ formatDate(attempt.completed_at) || 'Not completed' }}
                            </p>
                        </div>
                        <div class="text-center md:text-left">
                            <p class="text-sm text-gray-600 mb-1">Pass Threshold</p>
                            <p class="text-sm font-medium text-gray-900">{{ attempt.quiz?.pass_threshold || 70 }}%</p>
                        </div>
                        <div class="text-center md:text-left">
                            <p class="text-sm text-gray-600 mb-1">Time Taken</p>
                            <p class="text-sm font-medium text-gray-900">{{ attempt.time_taken || 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row justify-center gap-4 mb-8">
                    <button
                        v-if="canRetry"
                        @click="retryQuiz"
                        class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-medium transition-all duration-200 shadow-lg hover:shadow-xl"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.001 8.001 0 01-15.356-2m15.356 2H15" />
                        </svg>
                        Retry Quiz
                    </button>

                    <Link
                        :href="route('quizzes.index')"
                        class="inline-flex items-center justify-center px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300 text-sm font-medium transition-all duration-200"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Quizzes
                    </Link>
                </div>

                <!-- Questions Review Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-gray-900">Question Review</h2>
                            <div class="flex items-center space-x-2">
                                <!-- ✅ Show toggle button only when answers are available -->
                                <button
                                    v-if="showCorrectAnswersAllowed"
                                    @click="toggleCorrectAnswers"
                                    class="text-sm px-3 py-1 bg-blue-100 text-blue-800 rounded-full hover:bg-blue-200 transition-colors"
                                >
                                    {{ showAnswers ? 'Hide' : 'Show' }} Correct Answers
                                </button>
                                <div :class="[
                                    'px-3 py-1 rounded-full text-xs font-medium',
                                    showCorrectAnswersAllowed ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                ]">
                                    {{ showCorrectAnswersAllowed ? 'Answers Available' : 'Answers Locked' }}
                                </div>
                            </div>
                        </div>

                        <!-- ✅ Show explanation when answers are available -->
                        <div v-if="showCorrectAnswersAllowed" class="mt-2 text-sm text-gray-600">
                            <p class="flex items-center">
                                <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Correct answers unlocked - {{ attempt.passed ? 'Quiz passed!' : 'Maximum attempts reached' }}
                            </p>
                        </div>
                        <div v-else class="mt-2 text-sm text-gray-500">
                            <p class="flex items-center">
                                <svg class="w-4 h-4 mr-1 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Correct answers will be shown after passing or reaching maximum attempts
                            </p>
                        </div>
                    </div>

                    <div class="p-6">
                        <div v-if="attempt.responses && attempt.responses.length" class="space-y-6">
                            <div v-for="(response, index) in attempt.responses" :key="response.id || index"
                                 class="border border-gray-200 rounded-lg overflow-hidden">
                                <!-- Question Header -->
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">
                                                {{ index + 1 }}
                                            </div>
                                            <span class="font-medium text-gray-900">
                                                Question {{ index + 1 }}
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <div class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                                {{ response.question?.points || 0 }} pts
                                            </div>
                                            <div :class="[
                                                'w-3 h-3 rounded-full',
                                                getStatusColor(response)
                                            ]"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Question Content -->
                                <div class="p-4 space-y-4">
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-gray-900 font-medium">
                                            {{ response.question?.question_text || 'Question not available' }}
                                        </p>
                                    </div>

                                    <!-- Your Answer -->
                                    <div>
                                        <p class="text-sm font-medium text-gray-700 mb-2">Your Answer:</p>
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <p class="text-blue-900">{{ formatAnswer(response.answer) || 'Manually Graded' }}</p>
                                        </div>
                                    </div>

                                    <!-- ✅ Correct Answer (safely accessed) -->
                                    <div v-if="showCorrectAnswersAllowed && showAnswers && response.question?.correct_answer">
                                        <p class="text-sm font-medium text-gray-700 mb-2">Correct Answer(s):</p>
                                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                            <p class="text-green-900">
                                                {{ formatCorrectAnswer(response.question?.correct_answer) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- ✅ Answer Explanation (safely accessed) -->
                                    <div v-if="showCorrectAnswersAllowed && showAnswers && response.question?.correct_answer_explanation">
                                        <p class="text-sm font-medium text-gray-700 mb-2">Explanation:</p>
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                            <p class="text-yellow-900">
                                                {{ response.question?.correct_answer_explanation }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                                        <div class="flex items-center">
                                            <span :class="[
                                                'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                                                getStatusBadge(response)
                                            ]">
                                                <svg class="w-3 h-3 mr-1" :class="getStatusIcon(response)" fill="currentColor" viewBox="0 0 20 20">
                                                    <path v-if="response.is_correct" fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    <path v-else-if="response.is_correct === false" fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    <path v-else fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                                </svg>
                                                {{ getStatusText(response) }}
                                            </span>
                                        </div>
                                        <div v-if="response.points_earned !== undefined" class="text-sm text-gray-600">
                                            Points: {{ response.points_earned }}/{{ response.question?.points || 0 }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No responses found</h3>
                            <p class="text-gray-500">This attempt has no recorded responses.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from "@/layouts/AppLayout.vue";

export default {
    components: {
        AppLayout,
        Link,
    },
    props: {
        attempt: {
            type: Object,
            required: true,
        },
        userAttempts: {
            type: Array,
            default: () => [],
        },
    },
    setup(props) {
        const showAnswers = ref(false);
        const circumference = 2 * Math.PI * 56; // For the circular progress

        const breadcrumbs = computed(() => [
            { name: 'Quizzes', route: 'quizzes.index' },
            { name: props.attempt.quiz?.title || 'Quiz', route: null },
            { name: 'Results', route: null },
        ]);

        const scorePercentage = computed(() => {
            if (!props.attempt.quiz?.total_points || props.attempt.quiz.total_points === 0) return 0;
            return (props.attempt.total_score / props.attempt.quiz.total_points) * 100;
        });

        const attemptsLeft = computed(() => {
            const maxAttempts = 3;
            return Math.max(0, maxAttempts - props.userAttempts.length);
        });

        const canRetry = computed(() => {
            return !props.attempt.passed &&
                attemptsLeft.value > 0 &&
                (props.attempt.attempt_number || 0) < 3;
        });

        // ✅ Show correct answers if user passed OR reached maximum attempts (3)
        const showCorrectAnswersAllowed = computed(() => {
            // Show answers if user passed OR reached exactly 3 attempts
            const hasReachedMaxAttempts = (props.attempt.attempt_number >= 3) || (props.userAttempts.length >= 3);
            return props.attempt.passed || hasReachedMaxAttempts;
        });

        // Methods
        const formatDate = (dateString) => {
            if (!dateString) return null;
            return new Date(dateString).toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        };

        const formatAnswer = (answer) => {
            if (Array.isArray(answer)) {
                return answer.join(', ');
            }
            return answer;
        };

        const formatCorrectAnswer = (correctAnswer) => {
            if (Array.isArray(correctAnswer)) {
                return correctAnswer.join(', ');
            }
            return correctAnswer;
        };

        const getStatusText = (response) => {
            if (response.question?.type === 'text') {
                return response.is_correct !== null ? 'Manually Graded' : 'Pending Review';
            }
            return response.is_correct ? 'Correct' : 'Incorrect';
        };

        const getStatusColor = (response) => {
            if (response.is_correct === true) return 'bg-green-500';
            if (response.is_correct === false) return 'bg-red-500';
            return 'bg-yellow-500';
        };

        const getStatusBadge = (response) => {
            if (response.is_correct === true) return 'bg-green-100 text-green-800';
            if (response.is_correct === false) return 'bg-red-100 text-red-800';
            return 'bg-yellow-100 text-yellow-800';
        };

        const getStatusIcon = (response) => {
            if (response.is_correct === true) return 'text-green-500';
            if (response.is_correct === false) return 'text-red-500';
            return 'text-yellow-500';
        };

        const toggleCorrectAnswers = () => {
            showAnswers.value = !showAnswers.value;
        };

        const retryQuiz = () => {
            router.visit(route('quizzes.show', props.attempt.quiz.id));
        };

        return {
            breadcrumbs,
            showAnswers,
            circumference,
            scorePercentage,
            attemptsLeft,
            canRetry,
            showCorrectAnswersAllowed, // ✅ New computed property
            formatDate,
            formatAnswer,
            formatCorrectAnswer,
            getStatusText,
            getStatusColor,
            getStatusBadge,
            getStatusIcon,
            toggleCorrectAnswers,
            retryQuiz,
        };
    },
};
</script>

<style scoped>
/* Circular progress animation */
circle {
    transition: stroke-dashoffset 0.5s ease-in-out;
}

/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Hover effects */
.hover\:shadow-xl:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Print styles */
@media print {
    .bg-gradient-to-br {
        background: white !important;
    }

    button:not(.print-visible) {
        display: none !important;
    }

    .shadow-lg, .shadow-sm {
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
    }
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .text-4xl {
        font-size: 2.25rem;
        line-height: 2.5rem;
    }

    .w-32.h-32 {
        width: 6rem;
        height: 6rem;
    }

    .w-32.h-32 svg {
        width: 6rem;
        height: 6rem;
    }

    .grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
