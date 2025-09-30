<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl py-12 sm:px-6 lg:px-8 bg-white dark:bg-gray-900 text-gray-900 dark:text-white">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Attempt Details</h1>
                <Link
                    :href="route('admin.quiz-attempts.index')"
                    class="inline-flex items-center rounded-lg bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 transition-colors duration-200 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-hidden focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-500"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Attempts
                </Link>
            </div>

            <!-- Attempt Summary -->
            <div class="mb-6 rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm dark:shadow-lg dark:shadow-gray-900/50 border border-gray-200 dark:border-gray-700">
                <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-gray-200">Summary</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">User:</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ attempt.user.name }} ({{ attempt.user.email }})</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">Quiz:</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ attempt.quiz.title }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">Auto Score:</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ attempt.score }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">Manual Score:</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ attempt.manual_score ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">Total Score:</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ attempt.total_score }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">Pass Threshold:</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ attempt.quiz.pass_threshold }}%</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">Status:</p>
                        <span
                            :class="{
                                'inline-flex rounded-full px-2 text-xs font-semibold leading-5': true,
                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': attempt.passed,
                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': !attempt.passed,
                            }"
                        >
                            {{ attempt.passed ? 'Passed' : 'Failed' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">Completed At:</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ attempt.completed_at }}</p>
                    </div>
                </div>
            </div>

            <!-- Questions Review -->
            <div class="rounded-xl bg-white dark:bg-gray-800 p-6 shadow-sm dark:shadow-lg dark:shadow-gray-900/50 border border-gray-200 dark:border-gray-700">
                <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-gray-200">Question Review</h2>
                <div v-for="(response, index) in attempt.responses" :key="index" class="mb-6 rounded-lg bg-gray-50 dark:bg-gray-700 p-4 border border-gray-200 dark:border-gray-600">
                    <h3 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Question {{ index + 1 }} ({{ response.question.points || 'N/A' }} points)</h3>
                    <p class="mb-2 text-sm text-gray-900 dark:text-gray-100">{{ response.question.question_text }}</p>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-700 dark:text-gray-300">Your Answer:</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ response.answer || 'No answer' }}</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300">Correct Answer(s):</p>
                        <!-- ✅ Fixed: Safe rendering of correct_answer -->
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ formatCorrectAnswer(response.question.correct_answer) }}</p>
                        <p v-if="response.question.correct_answer_explanation" class="text-sm text-gray-600 dark:text-gray-400">
                            Explanation: {{ response.question.correct_answer_explanation }}
                        </p>
                        <p
                            class="text-sm"
                            :class="{
                                'text-green-700 dark:text-green-400': response.is_correct,
                                'text-red-700 dark:text-red-400': !response.is_correct && response.is_correct !== null,
                            }"
                        >
                            Status: {{ response.is_correct !== null ? (response.is_correct ? 'Correct' : 'Incorrect') : 'Pending (Text)' }}
                        </p>
                        <div v-if="response.question.type === 'text' && !response.is_correct">
                            <label for="manualScore" class="mt-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Manual Score (0-100):</label>
                            <input
                                v-model.number="manualScores[response.id]"
                                type="number"
                                id="manualScore"
                                min="0"
                                max="100"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 sm:text-sm"
                                placeholder="Enter points"
                                @change="updateManualScore(response.id, $event.target.value)"
                            />
                            <p v-if="manualScoreErrors[response.id]" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ manualScoreErrors[response.id] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div v-if="hasManualScores" class="mt-6">
                    <button
                        @click="saveManualScores"
                        :disabled="saving || hasErrors"
                        class="w-full rounded-lg bg-indigo-600 dark:bg-indigo-500 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 disabled:bg-gray-400 dark:disabled:bg-gray-600 disabled:cursor-not-allowed"
                    >
                        <span v-if="saving" class="flex items-center justify-center">
                            <svg class="mr-2 h-5 w-5 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8h-8z" />
                            </svg>
                            Saving...
                        </span>
                        <span v-else>Save Manual Scores</span>
                    </button>
                    <div v-if="success" class="mt-2 text-sm text-green-600 dark:text-green-400">Scores saved successfully!</div>
                    <div v-if="hasErrors" class="mt-2 text-sm text-red-600 dark:text-red-400">Please fix the errors before saving.</div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

export default {
    components: {
        AdminLayout,
        Link,
    },
    props: {
        attempt: {
            type: Object,
            required: true,
        },
    },
    setup(props) {
        const manualScores = ref({});
        const saving = ref(false);
        const success = ref(false);
        const manualScoreErrors = ref({});

        // ✅ Safe function to format correct answers
        const formatCorrectAnswer = (answer) => {
            if (Array.isArray(answer)) {
                return answer.join(', ');
            } else if (typeof answer === 'string') {
                return answer;
            } else if (answer === null || answer === undefined) {
                return 'N/A';
            } else {
                return String(answer); // Convert other types to string
            }
        };

        // Initialize manual scores with existing points_earned
        watch(
            () => props.attempt.responses,
            (responses) => {
                responses.forEach((response) => {
                    if (response.question.type === 'text' && !response.is_correct) {
                        manualScores.value[response.id] = response.points_earned ?? 0;
                    }
                });
            },
            { immediate: true },
        );

        const hasManualScores = computed(() => {
            return Object.values(manualScores.value).some((score) => score !== null && score !== undefined && score !== 0);
        });

        const hasErrors = computed(() => {
            return Object.values(manualScoreErrors.value).some((error) => error !== null);
        });

        const updateManualScore = (questionId, value) => {
            const score = parseInt(value) || 0;
            if (score < 0 || score > 100) {
                manualScoreErrors.value[questionId] = 'Score must be between 0 and 100.';
            } else {
                manualScoreErrors.value[questionId] = null;
            }
            manualScores.value[questionId] = score;
        };

        const saveManualScores = async () => {
            if (hasErrors.value) return;

            saving.value = true;
            success.value = false;
            try {
                const response = await router.put(
                    route('admin.quiz-attempts.update', props.attempt.id),
                    {
                        manual_scores: Object.fromEntries(Object.entries(manualScores.value).map(([questionId, score]) => [questionId, score])),
                    },
                    {
                        preserveScroll: true,
                        onSuccess: () => (success.value = true),
                    },
                );
            } catch (error) {
                console.error('Error saving scores:', error);
            } finally {
                saving.value = false;
                setTimeout(() => (success.value = false), 3000);
            }
        };

        // Breadcrumbs
        const breadcrumbs = [
            { name: 'Quizzes', route: 'admin.quizzes.index' },
            { name: 'Attempts', route: 'admin.quiz-attempts.index' },
            { name: 'Details', route: null },
        ];

        return {
            manualScores,
            saving,
            success,
            manualScoreErrors,
            hasManualScores,
            hasErrors,
            updateManualScore,
            saveManualScores,
            formatCorrectAnswer, // ✅ Export the helper function
            breadcrumbs,
        };
    },
};
</script>

<style scoped>
/* General Layout */
.max-w-3xl {
    @apply px-4 sm:px-6 lg:px-8;
}

/* Form Styling */
.bg-white {
    @apply transition-all duration-200;
}

.bg-gray-50 {
    @apply transition-all duration-200;
}

input {
    @apply transition-colors duration-200;
}

button {
    @apply transition-colors duration-200;
}

/* Responsive Adjustments */
@media (max-width: 640px) {
    .grid-cols-1 {
        @apply space-y-4;
    }

    .sm:grid-cols-2 {
        @apply grid-cols-1;
    }

    .px-4 {
        @apply px-2;
    }

    .py-2 {
        @apply py-1;
    }

    .text-sm {
        @apply text-xs;
    }
}
</style>
