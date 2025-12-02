<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Progress } from '@/components/ui/progress'

import {
    ArrowLeft, Play, Clock, Target, RefreshCw, CheckCircle, XCircle, AlertTriangle, Lock, History
} from 'lucide-vue-next'

interface Module {
    id: number
    name: string
    order_number: number
}

interface Course {
    id: number
    name: string
}

interface Quiz {
    id: number
    title: string
    description: string | null
    total_points: number
    pass_threshold: number
    passing_score: number
    time_limit_minutes: number | null
    max_attempts: number
    questions_count: number
    required_to_proceed: boolean
}

interface QuizStatus {
    has_quiz: boolean
    passed: boolean
    attempts_used: number
    best_score: number | null
    best_percentage: number | null
    last_attempt_at: string | null
    can_retry_at: string | null
}

interface CanTakeQuiz {
    can_take: boolean
    message: string
    reason?: string
}

const props = defineProps<{
    module: Module
    course: Course
    quiz: Quiz
    quizStatus: QuizStatus
    canTakeQuiz: CanTakeQuiz
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { name: "My Courses", href: "/courses-online" },
    { name: props.course.name, href: `/courses-online/${props.course.id}` },
    { name: props.module.name, href: `/courses-online/${props.course.id}` },
    { name: "Quiz", href: "" },
]

const formatTime = (minutes: number | null) => {
    if (!minutes) return 'No time limit'
    if (minutes < 60) return `${minutes} minutes`
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    return mins > 0 ? `${hours}h ${mins}m` : `${hours} hour${hours > 1 ? 's' : ''}`
}

const startQuiz = () => {
    router.post(route('courses-online.modules.quiz.start', {
        courseOnline: props.course.id,
        courseModule: props.module.id
    }), {}, {
        onSuccess: (page) => {
            // The controller will redirect to the take page
        }
    })
}
</script>

<template>
    <Head :title="`Quiz - ${module.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl mx-auto space-y-4 sm:space-y-6 px-4 sm:px-6 py-4 sm:py-0">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button as-child variant="ghost" size="sm" class="w-full sm:w-auto">
                    <Link :href="`/courses-online/${course.id}`" class="flex items-center justify-center">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Course
                    </Link>
                </Button>
            </div>

            <!-- Quiz Info Card -->
            <Card>
                <CardHeader>
                    <div class="flex flex-col sm:flex-row items-start sm:justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <CardTitle class="text-xl sm:text-2xl break-words">{{ quiz.title }}</CardTitle>
                            <CardDescription class="break-words">{{ module.name }}</CardDescription>
                        </div>
                        <Badge v-if="quizStatus.passed" variant="default" class="bg-green-500 flex-shrink-0">
                            <CheckCircle class="h-3 w-3 mr-1" />
                            Passed
                        </Badge>
                        <Badge v-else-if="quizStatus.attempts_used > 0" variant="secondary" class="flex-shrink-0">
                            In Progress
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6">
                    <p v-if="quiz.description" class="text-muted-foreground">
                        {{ quiz.description }}
                    </p>

                    <!-- Quiz Details -->
                    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                        <div class="text-center p-3 sm:p-4 bg-muted rounded-lg">
                            <p class="text-xl sm:text-2xl font-bold">{{ quiz.questions_count }}</p>
                            <p class="text-xs sm:text-sm text-muted-foreground">Questions</p>
                        </div>
                        <div class="text-center p-3 sm:p-4 bg-muted rounded-lg">
                            <p class="text-xl sm:text-2xl font-bold">{{ quiz.total_points }}</p>
                            <p class="text-xs sm:text-sm text-muted-foreground">Total Points</p>
                        </div>
                        <div class="text-center p-3 sm:p-4 bg-muted rounded-lg">
                            <p class="text-xl sm:text-2xl font-bold">{{ quiz.pass_threshold }}%</p>
                            <p class="text-xs sm:text-sm text-muted-foreground">To Pass</p>
                        </div>
                        <div class="text-center p-3 sm:p-4 bg-muted rounded-lg">
                            <Clock class="h-4 w-4 sm:h-5 sm:w-5 mx-auto mb-1 text-muted-foreground" />
                            <p class="text-xs sm:text-sm font-medium break-words">{{ formatTime(quiz.time_limit_minutes) }}</p>
                        </div>
                    </div>

                    <!-- Attempts Info -->
                    <div class="border rounded-lg p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Attempts Used</span>
                            <span class="font-medium">{{ quizStatus.attempts_used }} / {{ quiz.max_attempts }}</span>
                        </div>
                        <Progress :model-value="(quizStatus.attempts_used / quiz.max_attempts) * 100" />
                        
                        <div v-if="quizStatus.best_score !== null" class="flex items-center justify-between pt-2 border-t">
                            <span class="text-sm text-muted-foreground">Best Score</span>
                            <span class="font-medium">
                                {{ quizStatus.best_score }} / {{ quiz.total_points }}
                                ({{ quizStatus.best_percentage }}%)
                            </span>
                        </div>
                    </div>

                    <!-- Status Messages -->
                    <Alert v-if="quizStatus.passed" class="bg-green-50 dark:bg-green-950 border-green-200">
                        <CheckCircle class="h-4 w-4 text-green-600" />
                        <AlertDescription class="text-green-700 dark:text-green-300">
                            Congratulations! You've passed this quiz. 
                            <span v-if="quiz.required_to_proceed">The next module is now unlocked.</span>
                        </AlertDescription>
                    </Alert>

                    <Alert v-else-if="!canTakeQuiz.can_take" variant="destructive">
                        <Lock class="h-4 w-4" />
                        <AlertDescription>
                            {{ canTakeQuiz.message }}
                            <span v-if="quizStatus.can_retry_at" class="block mt-1">
                                You can retry at: {{ quizStatus.can_retry_at }}
                            </span>
                        </AlertDescription>
                    </Alert>

                    <Alert v-else-if="quiz.required_to_proceed">
                        <AlertTriangle class="h-4 w-4" />
                        <AlertDescription>
                            You must pass this quiz to unlock the next module.
                        </AlertDescription>
                    </Alert>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <Button 
                            v-if="canTakeQuiz.can_take"
                            @click="startQuiz"
                            class="flex-1"
                            size="lg"
                        >
                            <Play class="h-4 w-4 mr-2" />
                            {{ quizStatus.attempts_used > 0 ? 'Retry Quiz' : 'Start Quiz' }}
                        </Button>
                        
                        <Button 
                            v-if="quizStatus.attempts_used > 0"
                            as-child
                            variant="outline"
                            size="lg"
                        >
                            <Link :href="route('courses-online.modules.quiz.history', { courseOnline: course.id, courseModule: module.id })">
                                <History class="h-4 w-4 mr-2" />
                                View History
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Instructions -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Instructions</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-2 text-sm text-muted-foreground">
                        <li class="flex items-start gap-2">
                            <CheckCircle class="h-4 w-4 mt-0.5 text-green-500 shrink-0" />
                            Answer all questions to the best of your ability
                        </li>
                        <li v-if="quiz.time_limit_minutes" class="flex items-start gap-2">
                            <Clock class="h-4 w-4 mt-0.5 text-orange-500 shrink-0" />
                            You have {{ formatTime(quiz.time_limit_minutes) }} to complete the quiz
                        </li>
                        <li class="flex items-start gap-2">
                            <Target class="h-4 w-4 mt-0.5 text-blue-500 shrink-0" />
                            You need {{ quiz.passing_score }} points ({{ quiz.pass_threshold }}%) to pass
                        </li>
                        <li class="flex items-start gap-2">
                            <RefreshCw class="h-4 w-4 mt-0.5 text-purple-500 shrink-0" />
                            You have {{ quiz.max_attempts }} total attempts
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
