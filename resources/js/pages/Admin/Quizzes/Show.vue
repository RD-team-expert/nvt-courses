<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Quiz Details</h1>
                <Button variant="outline" as-child>
                    <Link :href="route('admin.quizzes.index')">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Quizzes
                    </Link>
                </Button>
            </div>

            <!-- Quiz Details -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Quiz Information</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:gap-6">
                        <!-- NEW: Course Type Display -->
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Course Type:</p>
                            <span
                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full"
                                :class="{
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400': quiz.course_type === 'regular',
                                    'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': quiz.course_type === 'online'
                                }"
                            >
                                {{ quiz.course_type === 'online' ? 'Online Course' : 'Regular Course' }}
                            </span>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Course:</p>
                            <p class="text-sm text-foreground">{{ quiz.course ? quiz.course.name : 'N/A' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Status:</p>
                            <span
                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full"
                                :class="{
                                    'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': quiz.status === 'published',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': quiz.status === 'draft',
                                    'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': quiz.status === 'archived',
                                }"
                            >
                                {{ quiz.status.charAt(0).toUpperCase() + quiz.status.slice(1) }}
                            </span>
                        </div>

                        <div class="sm:col-span-2 space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Title:</p>
                            <p class="text-sm text-foreground">{{ quiz.title }}</p>
                        </div>

                        <div class="sm:col-span-2 space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Description:</p>
                            <p class="text-sm text-foreground">{{ quiz.description || 'N/A' }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Total Points:</p>
                            <p class="text-sm text-foreground">{{ quiz.total_points }}</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Pass Threshold:</p>
                            <p class="text-sm text-foreground">{{ quiz.pass_threshold }}%</p>
                        </div>

                        <!-- NEW: Time Limit Display -->
                        <div v-if="quiz.time_limit_minutes" class="space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Time Limit:</p>
                            <p class="text-sm text-foreground">{{ quiz.time_limit_minutes }} minutes per attempt</p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Created At:</p>
                            <p class="text-sm text-foreground">{{ quiz.created_at }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- NEW: Deadline Information Card -->
            <Card v-if="quiz.has_deadline" class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Deadline Information
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <!-- Deadline Status Banner -->
                        <div
                            class="rounded-lg p-4 border-l-4"
                            :class="{
                                'bg-green-50 border-green-400 dark:bg-green-900/20': quiz.deadline_status?.status === 'normal',
                                'bg-yellow-50 border-yellow-400 dark:bg-yellow-900/20': quiz.deadline_status?.status === 'soon',
                                'bg-red-50 border-red-400 dark:bg-red-900/20': quiz.deadline_status?.status === 'urgent',
                                'bg-gray-50 border-gray-400 dark:bg-gray-900/20': quiz.deadline_status?.status === 'expired'
                            }"
                        >
                            <div class="flex items-start gap-3">
                                <div class="shrink-0">
                                    <span class="text-2xl">
                                        {{ getDeadlineIcon(quiz.deadline_status?.status) }}
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <h4
                                        class="font-medium"
                                        :class="{
                                            'text-green-800 dark:text-green-300': quiz.deadline_status?.status === 'normal',
                                            'text-yellow-800 dark:text-yellow-300': quiz.deadline_status?.status === 'soon',
                                            'text-red-800 dark:text-red-300': quiz.deadline_status?.status === 'urgent',
                                            'text-gray-800 dark:text-gray-300': quiz.deadline_status?.status === 'expired'
                                        }"
                                    >
                                        {{ quiz.deadline_status?.message || 'No deadline information available' }}
                                    </h4>
                                    <p
                                        class="mt-1 text-sm"
                                        :class="{
                                            'text-green-700 dark:text-green-400': quiz.deadline_status?.status === 'normal',
                                            'text-yellow-700 dark:text-yellow-400': quiz.deadline_status?.status === 'soon',
                                            'text-red-700 dark:text-red-400': quiz.deadline_status?.status === 'urgent',
                                            'text-gray-700 dark:text-gray-400': quiz.deadline_status?.status === 'expired'
                                        }"
                                    >
                                        Deadline: {{ quiz.deadline_formatted || 'Not specified' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Deadline Details Grid -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-muted-foreground">Time Remaining:</p>
                                <p class="text-sm text-foreground">{{ quiz.time_until_deadline || 'N/A' }}</p>
                            </div>

                            <div class="space-y-1">
                                <p class="text-sm font-medium text-muted-foreground">Enforcement:</p>
                                <span
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full"
                                    :class="quiz.enforce_deadline
                                        ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                                        : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'"
                                >
                                    {{ quiz.enforce_deadline ? 'Hard Deadline' : 'Soft Deadline' }}
                                </span>
                            </div>

                            <div class="space-y-1">
                                <p class="text-sm font-medium text-muted-foreground">Extensions:</p>
                                <span
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full"
                                    :class="quiz.allows_extensions
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                                        : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'"
                                >
                                    {{ quiz.allows_extensions ? 'Allowed' : 'Not Allowed' }}
                                </span>
                            </div>
                        </div>

                        <!-- Deadline Help Text -->
                        <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-3">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h5 class="text-sm font-medium text-blue-800 dark:text-blue-300">Deadline Policy</h5>
                                    <p class="mt-1 text-sm text-blue-700 dark:text-blue-400">
                                        {{ getDeadlineHelpText(quiz.enforce_deadline, quiz.allows_extensions) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- No Deadline Card -->
            <Card v-else class="mb-6">
                <CardContent class="py-6">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-muted mb-4">
                            <svg class="h-6 w-6 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-medium text-foreground mb-1">No Deadline Set</h3>
                        <p class="text-sm text-muted-foreground">This quiz can be completed at any time.</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Questions (EXISTING CODE UNCHANGED) -->
            <Card>
                <CardHeader>
                    <CardTitle>Questions ({{ quiz.questions.length }})</CardTitle>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div v-for="(question, index) in quiz.questions" :key="index" class="rounded-lg border bg-muted/30 p-4 sm:p-6">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-sm font-semibold text-foreground">Question {{ index + 1 }}</h3>
                            <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md bg-primary/10 text-primary">
                                    {{ question.type.charAt(0).toUpperCase() + question.type.slice(1) }}
                                </span>
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md bg-secondary text-secondary-foreground">
                                    {{ question.points || 0 }} points
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-muted-foreground">Question Text:</p>
                                <p class="text-sm text-foreground leading-relaxed">{{ question.question_text }}</p>
                            </div>
                        </div>

                        <div v-if="question.type !== 'text'" class="mt-6 space-y-4">
                            <!-- Options Section -->
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-foreground">Options</h4>
                                <div class="space-y-1">
                                    <div
                                        v-for="(option, optIndex) in cleanOptions(question.options)"
                                        :key="optIndex"
                                        class="flex items-start gap-2 text-sm text-muted-foreground"
                                    >
                                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-medium rounded-full bg-muted text-muted-foreground shrink-0 mt-0.5">
                                            {{ String.fromCharCode(65 + optIndex) }}
                                        </span>
                                        <span>{{ option }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Correct Answers Section -->
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-foreground">Correct Answer(s)</h4>
                                <div class="space-y-1">
                                    <div
                                        v-for="(answer, ansIndex) in cleanAnswers(question.correct_answer)"
                                        :key="ansIndex"
                                        class="flex items-start gap-2 text-sm"
                                    >
                                        <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400 shrink-0 mt-0.5">
                                            ✓
                                        </span>
                                        <span class="text-green-700 dark:text-green-400">{{ answer }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Explanation Section -->
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-foreground">Explanation</h4>
                                <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-3">
                                    <p class="text-sm text-blue-700 dark:text-blue-400">
                                        {{ question.correct_answer_explanation || 'No explanation provided.' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="question.type === 'text'" class="mt-6">
                            <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-blue-800 dark:text-blue-300">Open-ended Question</h4>
                                        <p class="mt-1 text-sm text-blue-700 dark:text-blue-400">
                                            This is an open-ended text question. Students will provide their own written response.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State for No Questions -->
                    <div v-if="!quiz.questions || quiz.questions.length === 0" class="text-center py-8">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-muted mb-4">
                            <svg class="h-6 w-6 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-medium text-foreground mb-1">No Questions</h3>
                        <p class="text-sm text-muted-foreground">This quiz doesn't have any questions yet.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default {
    components: {
        AdminLayout,
        Link,
        Button,
        Card,
        CardContent,
        CardHeader,
        CardTitle,
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

        // EXISTING: Function to clean options array
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

        // EXISTING: Function to clean correct answers array
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

        // NEW: Get deadline icon based on status
        const getDeadlineIcon = (status) => {
            switch (status) {
                case 'normal':
                    return '📅'
                case 'soon':
                    return '⏰'
                case 'urgent':
                    return '🚨'
                case 'expired':
                    return '❌'
                default:
                    return '📅'
            }
        }

        // NEW: Get deadline help text
        const getDeadlineHelpText = (enforceDeadline, allowsExtensions) => {
            if (enforceDeadline) {
                return allowsExtensions
                    ? 'This is a hard deadline, but extensions may be granted upon request.'
                    : 'This is a hard deadline. No submissions will be accepted after the deadline.'
            } else {
                return allowsExtensions
                    ? 'This is a soft deadline. Late submissions are accepted and extensions are available.'
                    : 'This is a soft deadline. Late submissions may be accepted with potential penalties.'
            }
        }

        return {
            breadcrumbs,
            cleanOptions,
            cleanAnswers,
            getDeadlineIcon,
            getDeadlineHelpText
        };
    },
};
</script>

<style scoped>
/* EXISTING STYLES UNCHANGED */
.max-w-7xl {
    @apply px-4 sm:px-6 lg:px-8;
}

.bg-white {
    @apply transition-all duration-200;
}

.bg-gray-50 {
    @apply transition-all duration-200;
}

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
