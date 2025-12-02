<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Alert, AlertDescription } from '@/components/ui/alert'

import {
    ArrowLeft, ArrowRight, RefreshCw, CheckCircle, XCircle, Trophy, Target, Clock, History
} from 'lucide-vue-next'

interface Question {
    id: number
    question_text: string
    type: string
    points: number
    options: string[]
    user_answer: string[]
    is_correct: boolean
    points_earned: number
    correct_answer?: string[]
    correct_answer_explanation?: string
}

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
    total_points: number
    pass_threshold: number
    passing_score: number
    max_attempts: number
}

interface Attempt {
    id: number
    attempt_number: number
    score: number
    score_percentage: number
    passed: boolean
    started_at: string
    completed_at: string
    duration_minutes: number
}

interface QuizStatus {
    attempts_used: number
    passed: boolean
}

interface CanRetake {
    can_take: boolean
    message: string
}

interface NextModule {
    id: number
    name: string
    is_unlocked: boolean
}

const props = defineProps<{
    module: Module
    course: Course
    quiz: Quiz
    attempt: Attempt
    questions: Question[]
    showCorrectAnswers: boolean
    quizStatus: QuizStatus
    canRetake: CanRetake
    nextModule: NextModule | null
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { name: "My Courses", href: "/courses-online" },
    { name: props.course.name, href: `/courses-online/${props.course.id}` },
    { name: props.module.name, href: `/courses-online/${props.course.id}` },
    { name: "Quiz Result", href: "" },
]

const retryQuiz = () => {
    router.post(route('courses-online.modules.quiz.start', {
        courseOnline: props.course.id,
        courseModule: props.module.id
    }))
}
</script>

<template>
    <Head :title="`Quiz Result - ${module.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-4 sm:space-y-6 px-4 sm:px-6 py-4 sm:py-0">
            <!-- Result Header -->
            <Card :class="attempt.passed ? 'border-green-500 bg-green-50 dark:bg-green-950' : 'border-red-500 bg-red-50 dark:bg-red-950'">
                <CardContent class="pt-4 sm:pt-6">
                    <div class="text-center space-y-3 sm:space-y-4">
                        <div :class="['w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-full flex items-center justify-center', attempt.passed ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900']">
                            <Trophy v-if="attempt.passed" class="h-8 w-8 sm:h-10 sm:w-10 text-green-600 dark:text-green-400" />
                            <XCircle v-else class="h-8 w-8 sm:h-10 sm:w-10 text-red-600 dark:text-red-400" />
                        </div>
                        
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold break-words" :class="attempt.passed ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
                                {{ attempt.passed ? 'Congratulations!' : 'Not Quite There' }}
                            </h1>
                            <p class="text-sm sm:text-base text-muted-foreground mt-1 break-words">
                                {{ attempt.passed ? 'You passed the quiz!' : `You need ${quiz.pass_threshold}% to pass` }}
                            </p>
                        </div>

                        <div class="flex items-center justify-center gap-4 sm:gap-8 pt-3 sm:pt-4">
                            <div class="text-center">
                                <p class="text-2xl sm:text-4xl font-bold">{{ attempt.score }}</p>
                                <p class="text-xs sm:text-sm text-muted-foreground">out of {{ quiz.total_points }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl sm:text-4xl font-bold">{{ attempt.score_percentage }}%</p>
                                <p class="text-xs sm:text-sm text-muted-foreground">Score</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl sm:text-4xl font-bold">{{ Math.floor(attempt.duration_minutes) }}</p>
                                <p class="text-xs sm:text-sm text-muted-foreground">{{ attempt.duration_minutes === 1 ? 'Minute' : 'Minutes' }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                <Button as-child variant="outline" class="flex-1 w-full sm:w-auto">
                    <Link :href="`/courses-online/${course.id}`" class="flex items-center justify-center">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Course
                    </Link>
                </Button>
                
                <Button 
                    v-if="canRetake.can_take && !attempt.passed"
                    @click="retryQuiz"
                    class="flex-1 w-full sm:w-auto"
                >
                    <RefreshCw class="h-4 w-4 mr-2" />
                    <span class="hidden sm:inline">Try Again ({{ quizStatus.attempts_used }}/{{ quiz.max_attempts }})</span>
                    <span class="sm:hidden">Try Again</span>
                </Button>

                <Button 
                    v-if="attempt.passed && nextModule?.is_unlocked"
                    as-child
                    class="flex-1 w-full sm:w-auto"
                >
                    <Link :href="`/courses-online/${course.id}`" class="flex items-center justify-center">
                        <span class="truncate">Continue to {{ nextModule.name }}</span>
                        <ArrowRight class="h-4 w-4 ml-2 flex-shrink-0" />
                    </Link>
                </Button>

                <Button as-child variant="outline" class="w-full sm:w-auto">
                    <Link :href="route('courses-online.modules.quiz.history', { courseOnline: course.id, courseModule: module.id })" class="flex items-center justify-center">
                        <History class="h-4 w-4 mr-2" />
                        History
                    </Link>
                </Button>
            </div>

            <!-- Attempt Info -->
            <Alert v-if="!canRetake.can_take && !attempt.passed">
                <XCircle class="h-4 w-4" />
                <AlertDescription>
                    {{ canRetake.message }}
                </AlertDescription>
            </Alert>

            <!-- Questions Review -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base sm:text-lg">Question Review</CardTitle>
                    <CardDescription class="text-sm">
                        {{ showCorrectAnswers ? 'Review your answers and see the correct solutions' : 'Review your submitted answers' }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4 sm:space-y-6">
                    <div 
                        v-for="(question, index) in questions" 
                        :key="question.id"
                        class="border rounded-lg p-3 sm:p-4 space-y-3"
                    >
                        <div class="flex items-start justify-between gap-2 sm:gap-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge variant="outline" class="text-xs">Q{{ index + 1 }}</Badge>
                                <Badge v-if="question.type !== 'text'" :variant="question.is_correct ? 'default' : 'destructive'" class="text-xs">
                                    {{ question.points_earned }}/{{ question.points }} pts
                                </Badge>
                            </div>
                            <div class="flex-shrink-0">
                                <CheckCircle v-if="question.is_correct" class="h-4 w-4 sm:h-5 sm:w-5 text-green-500" />
                                <XCircle v-else class="h-4 w-4 sm:h-5 sm:w-5 text-red-500" />
                            </div>
                        </div>

                        <p class="font-medium text-sm sm:text-base break-words">{{ question.question_text }}</p>

                        <!-- Options display -->
                        <div v-if="question.type !== 'text' && question.options?.length" class="space-y-2">
                            <div 
                                v-for="(option, oIndex) in question.options" 
                                :key="oIndex"
                                :class="[
                                    'flex items-center gap-2 p-2 sm:p-3 rounded text-sm sm:text-base',
                                    question.user_answer?.includes(option) && showCorrectAnswers && question.correct_answer?.includes(option) ? 'bg-green-100 dark:bg-green-900' :
                                    question.user_answer?.includes(option) && showCorrectAnswers && !question.correct_answer?.includes(option) ? 'bg-red-100 dark:bg-red-900' :
                                    question.user_answer?.includes(option) ? 'bg-blue-100 dark:bg-blue-900' :
                                    showCorrectAnswers && question.correct_answer?.includes(option) ? 'bg-green-50 dark:bg-green-950' : ''
                                ]"
                            >
                                <div :class="[
                                    'w-5 h-5 rounded flex items-center justify-center text-xs shrink-0',
                                    question.user_answer?.includes(option) ? 'bg-primary text-primary-foreground' : 'bg-muted'
                                ]">
                                    {{ String.fromCharCode(65 + oIndex) }}
                                </div>
                                <span :class="['break-words flex-1', { 'font-medium': question.user_answer?.includes(option) }]">{{ option }}</span>
                                <CheckCircle v-if="showCorrectAnswers && question.correct_answer?.includes(option)" class="h-4 w-4 text-green-500 flex-shrink-0" />
                            </div>
                        </div>

                        <!-- Text answer display -->
                        <div v-else-if="question.type === 'text'" class="bg-muted p-3 rounded">
                            <p class="text-xs sm:text-sm text-muted-foreground mb-1">Your answer:</p>
                            <p class="text-sm sm:text-base break-words">{{ question.user_answer?.[0] || 'No answer provided' }}</p>
                        </div>

                        <!-- Explanation -->
                        <div v-if="showCorrectAnswers && question.correct_answer_explanation" class="bg-blue-50 dark:bg-blue-950 p-3 rounded">
                            <p class="text-xs sm:text-sm font-medium text-blue-700 dark:text-blue-300 mb-1">Explanation:</p>
                            <p class="text-xs sm:text-sm text-blue-600 dark:text-blue-400 break-words">{{ question.correct_answer_explanation }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
