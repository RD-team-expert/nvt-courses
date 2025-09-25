<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-linear-to-br from-slate-50 to-blue-50">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Quiz Header -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-100">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ quiz.title }}</h1>
                            <p class="text-gray-600 mb-4">{{ quiz.description || 'No description available' }}</p>

                            <!-- Quiz Info Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-gray-500 mb-1">Pass Threshold</p>
                                    <p class="font-semibold text-gray-900">{{ quiz.pass_threshold }}%</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-gray-500 mb-1">Total Points</p>
                                    <p class="font-semibold text-gray-900">{{ quiz.total_points || 'N/A' }}</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-gray-500 mb-1">Questions</p>
                                    <p class="font-semibold text-gray-900">{{ questions.length }}</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-gray-500 mb-1">Time Limit</p>
                                    <p class="font-semibold text-gray-900">{{ quiz.time_limit ? `${quiz.time_limit} min` : 'No limit' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quiz Status Badge -->
                        <div class="ml-6">
                            <div :class="[
                                'px-4 py-2 rounded-full text-sm font-medium',
                                hasExistingAttempt ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'
                            ]">
                                {{ hasExistingAttempt ? 'Continuing Attempt' : 'New Attempt' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timer (if time limit exists) -->
                <div v-if="quiz.time_limit && timeRemaining > 0" class="bg-white rounded-xl shadow-sm p-4 mb-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Time Remaining</span>
                        </div>
                        <div class="flex items-center">
                            <span :class="[
                                'text-lg font-bold',
                                timeRemaining < 300 ? 'text-red-600' : 'text-gray-900'
                            ]">
                                {{ formatTime(timeRemaining) }}
                            </span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div
                            class="h-2 rounded-full transition-all duration-1000"
                            :class="timeRemaining < 300 ? 'bg-red-500' : 'bg-blue-500'"
                            :style="{ width: `${(timeRemaining / (quiz.time_limit * 60)) * 100}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Warning for existing attempt -->
                <div v-if="hasExistingAttempt" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-amber-500 mr-3 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <h3 class="text-sm font-semibold text-amber-800 mb-1">
                                Continuing Previous Attempt
                            </h3>
                            <p class="text-sm text-amber-700">
                                You have an incomplete attempt for this quiz. Your previous answers have been saved. You can modify them before final submission.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Progress Indicator -->
                <div class="bg-white rounded-xl shadow-sm p-4 mb-6 border border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Progress</span>
                        <span class="text-sm text-gray-500">{{ answeredQuestions }}/{{ questions.length }} answered</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="bg-linear-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-300"
                            :style="{ width: `${progressPercentage}%` }"
                        ></div>
                    </div>
                </div>

                <!-- Quiz Questions -->
                <div v-if="questions.length" class="space-y-6">
                    <form @submit.prevent="submitAttempt">
                        <div v-for="(question, index) in questions" :key="question.id" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <!-- Question Header -->
                            <div class="bg-linear-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">
                                            {{ index + 1 }}
                                        </div>
                                        <span class="text-lg font-semibold text-gray-900">
                                            Question {{ index + 1 }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <div class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                            {{ question.points || 'N/A' }} points
                                        </div>
                                        <div :class="[
                                            'w-3 h-3 rounded-full',
                                            isQuestionAnswered(question.id) ? 'bg-green-500' : 'bg-gray-300'
                                        ]"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Question Content -->
                            <div class="p-6">
                                <p class="text-gray-900 mb-6 text-base leading-relaxed">{{ question.question_text }}</p>

                                <!-- Radio buttons (Single Choice) -->
                                <div v-if="question.type === 'radio'" class="space-y-3">
                                    <div class="text-sm text-gray-600 mb-3 font-medium">Select one answer:</div>
                                    <div v-for="(option, optIndex) in question.options" :key="optIndex"
                                         class="flex items-start p-3 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-colors cursor-pointer">
                                        <input
                                            :id="`answer-${question.id}-${optIndex}`"
                                            v-model="form.answers[question.id]"
                                            type="radio"
                                            :value="option"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 mt-1"
                                            required
                                        >
                                        <label :for="`answer-${question.id}-${optIndex}`"
                                               class="ml-3 text-sm text-gray-700 cursor-pointer flex-1">
                                            {{ option }}
                                        </label>
                                    </div>
                                </div>

                                <!-- Checkboxes (Multiple Choice) -->
                                <div v-if="question.type === 'checkbox'" class="space-y-3">
                                    <div class="text-sm text-gray-600 mb-3 font-medium">Select all that apply:</div>
                                    <div v-for="(option, optIndex) in question.options" :key="optIndex"
                                         class="flex items-start p-3 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-colors cursor-pointer">
                                        <input
                                            :id="`answer-${question.id}-${optIndex}`"
                                            v-model="form.answers[question.id]"
                                            type="checkbox"
                                            :value="option"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mt-1"
                                        >
                                        <label :for="`answer-${question.id}-${optIndex}`"
                                               class="ml-3 text-sm text-gray-700 cursor-pointer flex-1">
                                            {{ option }}
                                        </label>
                                    </div>
                                </div>

                                <!-- Text area (Essay/Short Answer) -->
                                <div v-if="question.type === 'text'">
                                    <div class="text-sm text-gray-600 mb-3 font-medium">Your answer:</div>
                                    <textarea
                                        v-model="form.answers[question.id]"
                                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                        rows="5"
                                        placeholder="Type your answer here..."
                                        required
                                    ></textarea>
                                    <div class="mt-2 text-xs text-gray-500">
                                        Character count: {{ (form.answers[question.id] || '').length }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation & Submit -->
                        <div class="bg-white rounded-xl shadow-sm p-6 mt-8 border border-gray-100">
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <!-- Quiz Summary -->
                                <div class="flex items-center space-x-6 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ answeredQuestions }} answered</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ questions.length - answeredQuestions }} remaining</span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center space-x-3">
                                    <!-- Save Draft Button (if applicable) -->
                                    <button
                                        v-if="!hasExistingAttempt"
                                        type="button"
                                        @click="saveDraft"
                                        :disabled="submitting || savingDraft"
                                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span v-if="savingDraft" class="flex items-center">
                                            <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h-8z" />
                                            </svg>
                                            Saving...
                                        </span>
                                        <span v-else>Save Draft</span>
                                    </button>

                                    <!-- Submit Button -->
                                    <button
                                        type="submit"
                                        :disabled="submitting || !allQuestionsAnswered"
                                        class="px-6 py-2 bg-linear-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 text-sm font-medium transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                                    >
                                        <span v-if="submitting" class="flex items-center">
                                            <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h-8z" />
                                            </svg>
                                            Submitting...
                                        </span>
                                        <span v-else class="flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ hasExistingAttempt ? 'Complete Quiz' : 'Submit Quiz' }}
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Warning if not all questions answered -->
                            <div v-if="!allQuestionsAnswered" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-yellow-500 mr-2 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p class="text-sm text-yellow-800">
                                            Please answer all questions before submitting.
                                            <span class="font-medium">{{ questions.length - answeredQuestions }} question(s) remaining.</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Questions Available</h3>
                    <p class="text-gray-500 mb-4">This quiz doesn't have any questions yet.</p>
                    <Link :href="route('quizzes.index')" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Back to Quizzes
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { Link, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, onUnmounted } from 'vue';
import AppLayout from "@/layouts/AppLayout.vue";

export default {
    components: {
        AppLayout,
        Link,
    },
    props: {
        quiz: {
            type: Object,
            required: true,
        },
        questions: {
            type: Array,
            default: () => [],
        },
        hasExistingAttempt: {
            type: Boolean,
            default: false,
        },
    },
    setup(props) {
        const form = ref({
            answers: {},
        });
        const submitting = ref(false);
        const savingDraft = ref(false);
        const timeRemaining = ref(props.quiz.time_limit ? props.quiz.time_limit * 60 : 0);
        const timerInterval = ref(null);

        // Initialize form answers based on question types
        onMounted(() => {
            props.questions.forEach(question => {
                if (question.type === 'checkbox') {
                    form.value.answers[question.id] = [];
                } else {
                    form.value.answers[question.id] = '';
                }
            });

            // Start timer if time limit exists
            if (props.quiz.time_limit && timeRemaining.value > 0) {
                timerInterval.value = setInterval(() => {
                    timeRemaining.value--;
                    if (timeRemaining.value <= 0) {
                        clearInterval(timerInterval.value);
                        submitAttempt(); // Auto-submit when time runs out
                    }
                }, 1000);
            }
        });

        onUnmounted(() => {
            if (timerInterval.value) {
                clearInterval(timerInterval.value);
            }
        });

        // Computed properties
        const answeredQuestions = computed(() => {
            return Object.values(form.value.answers).filter(answer => {
                if (Array.isArray(answer)) {
                    return answer.length > 0;
                }
                return answer && answer.trim() !== '';
            }).length;
        });

        const progressPercentage = computed(() => {
            if (props.questions.length === 0) return 0;
            return (answeredQuestions.value / props.questions.length) * 100;
        });

        const allQuestionsAnswered = computed(() => {
            return answeredQuestions.value === props.questions.length;
        });

        const breadcrumbs = computed(() => [
            { name: 'Quizzes', route: 'quizzes.index' },
            { name: props.quiz?.title || 'Quiz', route: null },
        ]);

        // Methods
        const isQuestionAnswered = (questionId) => {
            const answer = form.value.answers[questionId];
            if (Array.isArray(answer)) {
                return answer.length > 0;
            }
            return answer && answer.trim() !== '';
        };

        const formatTime = (seconds) => {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        };

        const saveDraft = async () => {
            savingDraft.value = true;
            try {
                await router.post(route('quizzes.draft', props.quiz.id), {
                    answers: Object.entries(form.value.answers).map(([questionId, answer]) => ({
                        question_id: parseInt(questionId),
                        answer: answer,
                    })),
                });
            } catch (error) {
                console.error('Draft save error:', error);
            } finally {
                savingDraft.value = false;
            }
        };

        const submitAttempt = async () => {
            submitting.value = true;
            try {
                await router.post(route('quizzes.store', props.quiz.id), {
                    answers: Object.entries(form.value.answers).map(([questionId, answer]) => ({
                        question_id: parseInt(questionId),
                        answer: answer,
                    })),
                });
            } catch (error) {
                console.error('Submission error:', error);
            } finally {
                submitting.value = false;
            }
        };

        return {
            form,
            submitting,
            savingDraft,
            timeRemaining,
            answeredQuestions,
            progressPercentage,
            allQuestionsAnswered,
            breadcrumbs,
            isQuestionAnswered,
            formatTime,
            saveDraft,
            submitAttempt,
        };
    },
};
</script>

<style scoped>
/* Smooth transitions */
* {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Custom focus styles */
input[type="radio"]:focus,
input[type="checkbox"]:focus {
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

/* Loading animation */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Hover effects for options */
.cursor-pointer:hover {
    transform: translateY(-1px);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .text-3xl {
        font-size: 1.875rem;
        line-height: 2.25rem;
    }

    .px-6 {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .py-6 {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }

    .grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* Print styles */
@media print {
    .bg-linear-to-br {
        background: white !important;
    }

    button {
        display: none !important;
    }
}
</style>
