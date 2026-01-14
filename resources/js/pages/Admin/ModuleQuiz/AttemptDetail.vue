<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

import { ArrowLeft, CheckCircle, XCircle, User, Calendar, Clock, Target, Award, Save, RotateCcw } from 'lucide-vue-next'

interface User {
    id: number
    name: string
    email: string
}

interface Attempt {
    id: number
    attempt_number: number
    score: number
    manual_score: number | null
    total_score: number
    passed: boolean
    started_at: string | null
    completed_at: string | null
    created_at: string
}

interface Quiz {
    id: number
    title: string
    total_points: number
    pass_threshold: number
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

interface Question {
    id: number
    question_text: string
    type: 'radio' | 'checkbox' | 'text'
    points: number
    options: string[]
    correct_answer: string[]
    correct_answer_explanation: string | null
    order: number
    user_answer: string[] | string | null
    is_correct: boolean | null
    points_earned: number
    answer_id: number | null
}

const props = defineProps<{
    attempt: Attempt
    user: User
    quiz: Quiz
    module: Module
    course: Course
    questions: Question[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Course Online", href: "/admin/course-online" },
    { title: props.course.name, href: `/admin/course-online/${props.course.id}` },
    { title: props.quiz.title, href: '#' },
    { title: "Attempts", href: `/admin/course-online/${props.course.id}/modules/${props.module.id}/quiz/${props.quiz.id}/attempts` },
    { title: `Attempt #${props.attempt.attempt_number}`, href: '#' },
]

const scorePercentage = props.quiz.total_points > 0 
    ? ((props.attempt.total_score / props.quiz.total_points) * 100).toFixed(1)
    : 0

// Manual grading form
const textQuestions = props.questions.filter(q => q.type === 'text')
const manualGrades = ref<Record<number, number>>({})

// Initialize manual grades with existing points
textQuestions.forEach(q => {
    if (q.answer_id) {
        manualGrades.value[q.answer_id] = q.points_earned
    }
})

const hasTextQuestions = computed(() => textQuestions.length > 0)

const form = useForm({
    grades: manualGrades.value
})

function submitGrades() {
    form.put(route('admin.module-quiz.grade-attempt', {
        courseOnline: props.course.id,
        courseModule: props.module.id,
        quiz: props.quiz.id,
        attempt: props.attempt.id
    }), {
        preserveScroll: true,
    })
}

function isAnswerCorrect(question: Question, option: string): boolean {
    return question.correct_answer.includes(option)
}

function isUserAnswer(question: Question, option: string): boolean {
    if (!question.user_answer) return false
    if (Array.isArray(question.user_answer)) {
        return question.user_answer.includes(option)
    }
    return question.user_answer === option
}

function getUserTextAnswer(question: Question): string {
    if (!question.user_answer) return 'No answer provided'
    if (Array.isArray(question.user_answer)) {
        return question.user_answer.join(', ')
    }
    return question.user_answer
}

const resetting = ref(false)

function resetUserAttempts() {
    if (!confirm(`Are you sure you want to reset ALL quiz attempts for ${props.user.name}?\n\nThis will:\n- Delete all their attempts\n- Delete all their answers\n- Allow them to retake the quiz from scratch\n\nThis action cannot be undone!`)) {
        return
    }

    resetting.value = true

    router.post(
        route('admin.module-quiz.reset-attempts', {
            courseOnline: props.course.id,
            courseModule: props.module.id,
            quiz: props.quiz.id
        }),
        { user_id: props.user.id },
        {
            onFinish: () => {
                resetting.value = false
            }
        }
    )
}
</script>

<template>
    <Head :title="`Attempt #${attempt.attempt_number} - ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto space-y-6 pb-12">
            <!-- Header -->
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="route('admin.module-quiz.attempts', { courseOnline: course.id, courseModule: module.id, quiz: quiz.id })">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Attempts
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold">Quiz Attempt Details</h1>
                        <p class="text-muted-foreground">{{ quiz.title }}</p>
                    </div>
                </div>
                <Button 
                    variant="destructive" 
                    @click="resetUserAttempts"
                    :disabled="resetting"
                >
                    <RotateCcw class="h-4 w-4 mr-2" />
                    {{ resetting ? 'Resetting...' : 'Reset All Attempts' }}
                </Button>
            </div>

            <!-- Attempt Summary -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center justify-between">
                        <span>Attempt Summary</span>
                        <Badge :variant="attempt.passed ? 'default' : 'destructive'" class="text-base px-4 py-1">
                            <CheckCircle v-if="attempt.passed" class="h-4 w-4 mr-2" />
                            <XCircle v-else class="h-4 w-4 mr-2" />
                            {{ attempt.passed ? 'Passed' : 'Failed' }}
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <User class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm text-muted-foreground">Student</p>
                                    <p class="font-medium">{{ user.name }}</p>
                                    <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Target class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm text-muted-foreground">Attempt Number</p>
                                    <p class="font-medium">#{{ attempt.attempt_number }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <Award class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm text-muted-foreground">Score</p>
                                    <p class="font-medium text-lg">
                                        {{ attempt.total_score }} / {{ quiz.total_points }} 
                                        <span class="text-muted-foreground text-base">({{ scorePercentage }}%)</span>
                                    </p>
                                    <p v-if="attempt.manual_score" class="text-xs text-muted-foreground">
                                        Auto: {{ attempt.score }} | Manual: {{ attempt.manual_score }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Clock class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p class="text-sm text-muted-foreground">Completed</p>
                                    <p class="font-medium">{{ attempt.completed_at || 'In Progress' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Questions and Answers -->
            <Card>
                <CardHeader>
                    <CardTitle>Questions & Answers</CardTitle>
                    <CardDescription>Review all questions and user responses</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div v-for="(question, index) in questions" :key="question.id" class="border rounded-lg p-5 space-y-4">
                        <!-- Question Header -->
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3 flex-1">
                                <Badge variant="outline" class="mt-1">Q{{ index + 1 }}</Badge>
                                <div class="flex-1">
                                    <p class="font-medium text-base">{{ question.question_text }}</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <Badge v-if="question.type !== 'text'" variant="secondary" class="text-xs">
                                            {{ question.points }} pts
                                        </Badge>
                                        <Badge variant="outline" class="text-xs">
                                            {{ question.type === 'radio' ? 'Single Choice' : question.type === 'checkbox' ? 'Multiple Choice' : 'Text Answer' }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <Badge 
                                    v-if="question.type !== 'text'"
                                    :variant="question.is_correct ? 'default' : 'destructive'"
                                    class="text-sm"
                                >
                                    <CheckCircle v-if="question.is_correct" class="h-3 w-3 mr-1" />
                                    <XCircle v-else class="h-3 w-3 mr-1" />
                                    {{ question.points_earned }} / {{ question.points }}
                                </Badge>
                                <Badge v-else variant="outline" class="text-sm">
                                    Manual Grading
                                </Badge>
                            </div>
                        </div>

                        <!-- Options (for radio/checkbox) -->
                        <div v-if="question.type !== 'text'" class="space-y-2 pl-12">
                            <div 
                                v-for="(option, optIndex) in question.options" 
                                :key="optIndex"
                                class="flex items-center gap-3 p-3 rounded-lg border"
                                :class="{
                                    'bg-green-50 dark:bg-green-950 border-green-300 dark:border-green-700': isAnswerCorrect(question, option),
                                    'bg-red-50 dark:bg-red-950 border-red-300 dark:border-red-700': isUserAnswer(question, option) && !isAnswerCorrect(question, option),
                                    'bg-muted/30': !isUserAnswer(question, option) && !isAnswerCorrect(question, option)
                                }"
                            >
                                <div class="flex items-center gap-2 flex-1">
                                    <input 
                                        :type="question.type === 'radio' ? 'radio' : 'checkbox'"
                                        :checked="isUserAnswer(question, option)"
                                        disabled
                                        class="h-4 w-4"
                                    />
                                    <span>{{ option }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge v-if="isUserAnswer(question, option)" variant="outline" class="text-xs">
                                        User's Answer
                                    </Badge>
                                    <CheckCircle 
                                        v-if="isAnswerCorrect(question, option)" 
                                        class="h-5 w-5 text-green-600 dark:text-green-400"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Text Answer with Manual Grading -->
                        <div v-else class="pl-12 space-y-3">
                            <div class="bg-muted/50 p-4 rounded-lg border">
                                <p class="text-sm text-muted-foreground mb-1">User's Answer:</p>
                                <p class="whitespace-pre-wrap">{{ getUserTextAnswer(question) }}</p>
                            </div>
                            
                            <!-- Manual Grading Input -->
                            <div v-if="question.answer_id" class="bg-yellow-50 dark:bg-yellow-950 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                                <div class="flex items-end gap-3">
                                    <div class="flex-1">
                                        <Label :for="`grade-${question.answer_id}`" class="text-sm font-medium text-yellow-900 dark:text-yellow-100">
                                            Assign Points (Max: {{ question.points }})
                                        </Label>
                                        <Input 
                                            :id="`grade-${question.answer_id}`"
                                            v-model.number="manualGrades[question.answer_id]"
                                            type="number" 
                                            :min="0" 
                                            :max="question.points"
                                            class="mt-1"
                                            :disabled="form.processing"
                                        />
                                    </div>
                                    <Badge variant="outline" class="mb-2">
                                        Current: {{ question.points_earned }} pts
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <!-- Explanation -->
                        <div v-if="question.correct_answer_explanation" class="pl-12 mt-4">
                            <div class="bg-blue-50 dark:bg-blue-950 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                                <p class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-1">Explanation:</p>
                                <p class="text-sm text-blue-800 dark:text-blue-200">{{ question.correct_answer_explanation }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Save Grades Button -->
                    <div v-if="hasTextQuestions" class="flex justify-end pt-4 border-t">
                        <Button @click="submitGrades" :disabled="form.processing">
                            <Save class="h-4 w-4 mr-2" />
                            {{ form.processing ? 'Saving...' : 'Save Manual Grades' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
