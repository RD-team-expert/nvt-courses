<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900">Quiz Details</h1>
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

            <!-- Quiz Details -->
            <div class="mb-8 rounded-xl bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">Quiz Information</h2>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <p class="mb-1 text-sm text-gray-700">Course:</p>
                        <p class="text-sm text-gray-900">{{ quiz.course ? quiz.course.name : 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-sm text-gray-700">Status:</p>
                        <span
                            :class="{
                                'inline-flex rounded-full px-2 text-xs font-semibold leading-5': true,
                                'bg-green-100 text-green-800': quiz.status === 'published',
                                'bg-yellow-100 text-yellow-800': quiz.status === 'draft',
                                'bg-red-100 text-red-800': quiz.status === 'archived',
                            }"
                        >
                            {{ quiz.status.charAt(0).toUpperCase() + quiz.status.slice(1) }}
                        </span>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="mb-1 text-sm text-gray-700">Title:</p>
                        <p class="text-sm text-gray-900">{{ quiz.title }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="mb-1 text-sm text-gray-700">Description:</p>
                        <p class="text-sm text-gray-900">{{ quiz.description || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-sm text-gray-700">Total Points:</p>
                        <p class="text-sm text-gray-900">{{ quiz.total_points }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-sm text-gray-700">Pass Threshold:</p>
                        <p class="text-sm text-gray-900">{{ quiz.pass_threshold }}%</p>
                    </div>
                    <div>
                        <p class="mb-1 text-sm text-gray-700">Created At:</p>
                        <p class="text-sm text-gray-900">{{ quiz.created_at }}</p>
                    </div>
                </div>
            </div>

            <!-- Questions -->
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-800">Questions ({{ quiz.questions.length }})</h2>
                <div v-for="(question, index) in quiz.questions" :key="index" class="mb-6 rounded-lg bg-gray-50 p-4">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-700">Question {{ index + 1 }}</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <p class="mb-1 text-sm text-gray-700">Question Text:</p>
                            <p class="text-sm text-gray-900">{{ question.question_text }}</p>
                        </div>
                        <div>
                            <p class="mb-1 text-sm text-gray-700">Type:</p>
                            <p class="text-sm text-gray-900">
                                {{ question.type.charAt(0).toUpperCase() + question.type.slice(1) }}
                            </p>
                        </div>
                        <div>
                            <p class="mb-1 text-sm text-gray-700">Points:</p>
                            <p class="text-sm text-gray-900">{{ question.points || 0 }}</p>
                        </div>
                    </div>
                    <div v-if="question.type !== 'text'" class="mt-4">
                        <!-- ✅ Fixed Options Section -->
                        <h4 class="mb-2 text-sm font-medium text-gray-700">Options</h4>
                        <ul class="list-inside list-disc text-sm text-gray-600">
                            <li v-for="(option, optIndex) in cleanOptions(question.options)" :key="optIndex">
                                {{ option }}
                            </li>
                        </ul>

                        <!-- ✅ Fixed Correct Answers Section -->
                        <h4 class="mb-2 mt-4 text-sm font-medium text-gray-700">Correct Answer(s)</h4>
                        <ul class="list-inside list-disc text-sm text-gray-600">
                            <li v-for="(answer, ansIndex) in cleanAnswers(question.correct_answer)" :key="ansIndex">
                                {{ answer }}
                            </li>
                        </ul>

                        <h4 class="mb-2 mt-4 text-sm font-medium text-gray-700">Explanation</h4>
                        <p class="text-sm text-gray-600">{{ question.correct_answer_explanation || 'N/A' }}</p>
                    </div>
                    <div v-if="question.type === 'text'" class="mt-4 rounded-lg bg-blue-50 p-3">
                        <p class="text-sm text-blue-700">📝 This is an open-ended text question. Students will provide their own written response.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
<script>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

export default {
    components: {
        AdminLayout,
        Link,
    },
    props: {
        quiz: {
            type: Object,
            required: true,
        },
    },
    setup(props) {
        // Breadcrumbs
        const breadcrumbs = [
            { name: 'Quizzes', route: 'admin.quizzes.index' },
            { name: 'Details', route: null },
        ];

        // ✅ Function to clean options array
        const cleanOptions = (options) => {
            if (!options) return []

            if (Array.isArray(options)) {
                return options.map(option => {
                    // Remove surrounding quotes if they exist
                    if (typeof option === 'string') {
                        return option.replace(/^["']|["']$/g, '').trim()
                    }
                    return option
                })
            }

            // If it's a string (JSON), try to parse it
            if (typeof options === 'string') {
                try {
                    const parsed = JSON.parse(options)
                    if (Array.isArray(parsed)) {
                        return parsed.map(option =>
                            typeof option === 'string'
                                ? option.replace(/^["']|["']$/g, '').trim()
                                : option
                        )
                    }
                } catch (e) {
                    // If parsing fails, return as is
                    return [options]
                }
            }

            return []
        }

        // ✅ Function to clean correct answers array
        const cleanAnswers = (answers) => {
            if (!answers) return []

            if (Array.isArray(answers)) {
                return answers.map(answer => {
                    // Remove surrounding quotes if they exist
                    if (typeof answer === 'string') {
                        return answer.replace(/^["']|["']$/g, '').trim()
                    }
                    return answer
                })
            }

            // If it's a string (JSON), try to parse it
            if (typeof answers === 'string') {
                try {
                    const parsed = JSON.parse(answers)
                    if (Array.isArray(parsed)) {
                        return parsed.map(answer =>
                            typeof answer === 'string'
                                ? answer.replace(/^["']|["']$/g, '').trim()
                                : answer
                        )
                    }
                } catch (e) {
                    // If parsing fails, return as is
                    return [answers]
                }
            }

            return []
        }

        return {
            breadcrumbs,
            cleanOptions,
            cleanAnswers
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

/* Question Sections */
.bg-gray-50 {
    @apply transition-all duration-200;
}

/* Responsive Adjustments */
@media (max-width: 640px) {
    .grid-cols-1 {
        @apply space-y-4;
    }

    .sm:col-span-2 {
        @apply col-span-1;
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
