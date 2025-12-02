<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

import { ArrowLeft, Play, CheckCircle, XCircle, Eye, Clock, Target } from 'lucide-vue-next'

interface Attempt {
    id: number
    attempt_number: number
    score: number
    score_percentage: number
    passed: boolean
    started_at: string
    completed_at: string | null
    duration_minutes: number | null
}

interface Module {
    id: number
    name: string
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
    max_attempts: number
}

interface QuizStatus {
    attempts_used: number
    passed: boolean
    best_score: number | null
    best_percentage: number | null
}

const props = defineProps<{
    module: Module
    course: Course
    quiz: Quiz
    attempts: Attempt[]
    quizStatus: QuizStatus
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { name: "My Courses", href: "/courses-online" },
    { name: props.course.name, href: `/courses-online/${props.course.id}` },
    { name: props.module.name, href: `/courses-online/${props.course.id}` },
    { name: "Quiz History", href: "" },
]

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

// Allow retaking if not passed, regardless of attempts (can continue incomplete attempts)
const hasIncompleteAttempt = computed(() => 
    props.attempts.some(a => !a.completed_at)
)

const canRetake = computed(() => 
    !props.quizStatus.passed && (props.quizStatus.attempts_used < props.quiz.max_attempts || hasIncompleteAttempt.value)
)

const startQuiz = () => {
    router.post(route('courses-online.modules.quiz.start', {
        courseOnline: props.course.id,
        courseModule: props.module.id
    }))
}
</script>

<template>
    <Head :title="`Quiz History - ${module.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-4 sm:space-y-6 px-4 sm:px-6 py-4 sm:py-0">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center sm:justify-between gap-3 sm:gap-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4 w-full sm:w-auto">
                    <Button as-child variant="ghost" size="sm" class="w-full sm:w-auto">
                        <Link :href="`/courses-online/${course.id}`" class="flex items-center justify-center">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back
                        </Link>
                    </Button>
                    <div class="flex-1 min-w-0">
                        <h1 class="text-xl sm:text-2xl font-bold break-words">Quiz History</h1>
                        <p class="text-sm sm:text-base text-muted-foreground break-words">{{ quiz.title }}</p>
                    </div>
                </div>
                <Button v-if="canRetake" @click="startQuiz" class="w-full sm:w-auto flex-shrink-0">
                    <Play class="h-4 w-4 mr-2" />
                    {{ hasIncompleteAttempt ? 'Continue' : 'Try Again' }}
                </Button>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                <Card>
                    <CardContent class="pt-4 sm:pt-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center flex-shrink-0">
                                <Clock class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xl sm:text-2xl font-bold">{{ quizStatus.attempts_used }}</p>
                                <p class="text-xs sm:text-sm text-muted-foreground break-words">of {{ quiz.max_attempts }} attempts</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="pt-4 sm:pt-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center flex-shrink-0">
                                <Target class="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xl sm:text-2xl font-bold">{{ quizStatus.best_percentage ?? 0 }}%</p>
                                <p class="text-xs sm:text-sm text-muted-foreground">Best Score</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-4 sm:pt-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center flex-shrink-0">
                                <Target class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xl sm:text-2xl font-bold">{{ quiz.pass_threshold }}%</p>
                                <p class="text-xs sm:text-sm text-muted-foreground">Pass Threshold</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-4 sm:pt-6">
                        <div class="flex items-center gap-3">
                            <div :class="['w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0', quizStatus.passed ? 'bg-green-100 dark:bg-green-900' : 'bg-orange-100 dark:bg-orange-900']">
                                <CheckCircle v-if="quizStatus.passed" class="h-5 w-5 text-green-600 dark:text-green-400" />
                                <XCircle v-else class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-base sm:text-lg font-bold break-words">{{ quizStatus.passed ? 'Passed' : 'Not Passed' }}</p>
                                <p class="text-xs sm:text-sm text-muted-foreground">Status</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Attempts Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base sm:text-lg">All Attempts</CardTitle>
                    <CardDescription class="text-sm">Your quiz attempt history</CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Mobile: Card View -->
                    <div v-if="attempts.length > 0" class="block sm:hidden space-y-3">
                        <Card v-for="attempt in attempts" :key="attempt.id" class="border">
                            <CardContent class="p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <Badge variant="outline">#{{ attempt.attempt_number }}</Badge>
                                    <Badge :variant="attempt.passed ? 'default' : 'destructive'">
                                        <CheckCircle v-if="attempt.passed" class="h-3 w-3 mr-1" />
                                        <XCircle v-else class="h-3 w-3 mr-1" />
                                        {{ attempt.passed ? 'Passed' : 'Failed' }}
                                    </Badge>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Score:</span>
                                        <span class="font-medium">{{ attempt.score }} / {{ quiz.total_points }} ({{ attempt.score_percentage }}%)</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Duration:</span>
                                        <span>{{ attempt.duration_minutes ? `${Math.floor(attempt.duration_minutes)} min` : '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Date:</span>
                                        <span class="text-right">{{ attempt.completed_at ? formatDate(attempt.completed_at) : 'In Progress' }}</span>
                                    </div>
                                </div>
                                <Button 
                                    v-if="attempt.completed_at"
                                    as-child 
                                    variant="outline" 
                                    size="sm"
                                    class="w-full"
                                >
                                    <Link :href="route('courses-online.modules.quiz.result', { courseOnline: course.id, courseModule: module.id, attempt: attempt.id })" class="flex items-center justify-center">
                                        <Eye class="h-4 w-4 mr-2" />
                                        View Result
                                    </Link>
                                </Button>
                                <Button 
                                    v-else
                                    as-child 
                                    variant="default" 
                                    size="sm"
                                    class="w-full"
                                >
                                    <Link :href="route('courses-online.modules.quiz.take', { courseOnline: course.id, courseModule: module.id, attempt: attempt.id })" class="flex items-center justify-center">
                                        <Play class="h-4 w-4 mr-2" />
                                        Continue Quiz
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Desktop: Table View -->
                    <div class="hidden sm:block overflow-x-auto">
                        <Table v-if="attempts.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Attempt</TableHead>
                                    <TableHead>Score</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Duration</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead class="text-right">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="attempt in attempts" :key="attempt.id">
                                    <TableCell>
                                        <Badge variant="outline">#{{ attempt.attempt_number }}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div>
                                            <p class="font-medium">{{ attempt.score }} / {{ quiz.total_points }}</p>
                                            <p class="text-sm text-muted-foreground">{{ attempt.score_percentage }}%</p>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="attempt.passed ? 'default' : 'destructive'">
                                            <CheckCircle v-if="attempt.passed" class="h-3 w-3 mr-1" />
                                            <XCircle v-else class="h-3 w-3 mr-1" />
                                            {{ attempt.passed ? 'Passed' : 'Failed' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {{ attempt.duration_minutes ? `${Math.floor(attempt.duration_minutes)} min` : '-' }}
                                    </TableCell>
                                    <TableCell>
                                        {{ attempt.completed_at ? formatDate(attempt.completed_at) : 'In Progress' }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button 
                                            v-if="attempt.completed_at"
                                            as-child 
                                            variant="ghost" 
                                            size="sm"
                                        >
                                            <Link :href="route('courses-online.modules.quiz.result', { courseOnline: course.id, courseModule: module.id, attempt: attempt.id })">
                                                <Eye class="h-4 w-4 mr-1" />
                                                View
                                            </Link>
                                        </Button>
                                        <Button 
                                            v-else
                                            as-child 
                                            variant="default" 
                                            size="sm"
                                        >
                                            <Link :href="route('courses-online.modules.quiz.take', { courseOnline: course.id, courseModule: module.id, attempt: attempt.id })">
                                                <Play class="h-4 w-4 mr-1" />
                                                Continue
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div v-if="attempts.length === 0" class="text-center py-8 sm:py-12 text-muted-foreground text-sm sm:text-base">
                        No attempts yet. Start your first quiz attempt!
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
