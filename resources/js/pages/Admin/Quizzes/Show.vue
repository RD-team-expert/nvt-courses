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
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-muted-foreground">Created At:</p>
                            <p class="text-sm text-foreground">{{ quiz.created_at }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Questions -->
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
