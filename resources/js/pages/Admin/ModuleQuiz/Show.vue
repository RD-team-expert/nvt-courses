<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

import {
    ArrowLeft, Edit, Trash2, BarChart3, Clock, Target, Users, CheckCircle, XCircle, Eye, HelpCircle
} from 'lucide-vue-next'

interface Question {
    id: number
    question_text: string
    type: string
    points: number
    options: string[]
    correct_answer: string[]
    correct_answer_explanation: string
    order: number
}

interface Quiz {
    id: number
    title: string
    description: string | null
    status: string
    total_points: number
    pass_threshold: number
    max_attempts: number
    retry_delay_hours: number
    show_correct_answers: string
    time_limit_minutes: number | null
    required_to_proceed: boolean
    created_at: string
    questions: Question[]
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

const props = defineProps<{
    quiz: Quiz
    module: Module
    course: Course
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Course Online", href: "/admin/course-online" },
    { title: props.course.name, href: `/admin/course-online/${props.course.id}` },
    { title: `Module ${props.module.order_number}`, href: '#' },
    { title: props.quiz.title, href: '#' },
]

const showCorrectAnswersLabels: Record<string, string> = {
    'never': 'Never',
    'after_pass': 'After Passing',
    'after_max_attempts': 'After Max Attempts',
    'always': 'Always'
}

const formatTime = (minutes: number | null) => {
    if (!minutes) return 'No limit'
    if (minutes < 60) return `${minutes} min`
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`
}

const deleteQuiz = () => {
    if (confirm('Are you sure you want to delete this quiz?')) {
        router.delete(route('admin.module-quiz.destroy', {
            courseOnline: props.course.id,
            courseModule: props.module.id,
            quiz: props.quiz.id
        }))
    }
}

const getTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        'radio': 'Single Choice',
        'checkbox': 'Multiple Choice',
        'text': 'Text Answer'
    }
    return labels[type] || type
}
</script>

<template>
    <Head :title="quiz.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="`/admin/course-online/${course.id}`">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back
                        </Link>
                    </Button>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold">{{ quiz.title }}</h1>
                            <Badge :variant="quiz.status === 'published' ? 'default' : 'secondary'">
                                {{ quiz.status }}
                            </Badge>
                        </div>
                        <p class="text-muted-foreground">{{ module.name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="route('admin.module-quiz.attempts', { courseOnline: course.id, courseModule: module.id, quiz: quiz.id })">
                            <BarChart3 class="h-4 w-4 mr-2" />
                            Attempts
                        </Link>
                    </Button>
                    <Button as-child variant="outline" size="sm">
                        <Link :href="route('admin.module-quiz.edit', { courseOnline: course.id, courseModule: module.id, quiz: quiz.id })">
                            <Edit class="h-4 w-4 mr-2" />
                            Edit
                        </Link>
                    </Button>
                    <Button variant="destructive" size="sm" @click="deleteQuiz">
                        <Trash2 class="h-4 w-4 mr-2" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Quiz Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Quiz Settings</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center gap-2">
                                <Target class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p class="text-sm text-muted-foreground">Pass Threshold</p>
                                    <p class="font-medium">{{ quiz.pass_threshold }}%</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Users class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p class="text-sm text-muted-foreground">Max Attempts</p>
                                    <p class="font-medium">{{ quiz.max_attempts }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Clock class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p class="text-sm text-muted-foreground">Time Limit</p>
                                    <p class="font-medium">{{ formatTime(quiz.time_limit_minutes) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Eye class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p class="text-sm text-muted-foreground">Show Answers</p>
                                    <p class="font-medium">{{ showCorrectAnswersLabels[quiz.show_correct_answers] }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 border-t">
                            <div class="flex items-center gap-2">
                                <CheckCircle v-if="quiz.required_to_proceed" class="h-4 w-4 text-green-500" />
                                <XCircle v-else class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm">
                                    {{ quiz.required_to_proceed ? 'Required to proceed to next module' : 'Optional quiz' }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Summary</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-muted rounded-lg">
                                <p class="text-3xl font-bold">{{ quiz.questions.length }}</p>
                                <p class="text-sm text-muted-foreground">Questions</p>
                            </div>
                            <div class="text-center p-4 bg-muted rounded-lg">
                                <p class="text-3xl font-bold">{{ quiz.total_points }}</p>
                                <p class="text-sm text-muted-foreground">Total Points</p>
                            </div>
                        </div>
                        <p v-if="quiz.description" class="mt-4 text-sm text-muted-foreground">
                            {{ quiz.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Questions -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <HelpCircle class="h-5 w-5" />
                        Questions
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-for="(question, index) in quiz.questions" :key="question.id" class="border rounded-lg p-4">
                        <div class="flex items-start justify-between gap-4 mb-3">
                            <div class="flex items-center gap-2">
                                <Badge variant="outline">Q{{ index + 1 }}</Badge>
                                <Badge variant="secondary">{{ getTypeLabel(question.type) }}</Badge>
                                <Badge v-if="question.type !== 'text'">{{ question.points }} pts</Badge>
                            </div>
                        </div>
                        
                        <p class="font-medium mb-3">{{ question.question_text }}</p>
                        
                        <div v-if="question.type !== 'text' && question.options?.length" class="space-y-2 mb-3">
                            <div v-for="(option, oIndex) in question.options" :key="oIndex" class="flex items-center gap-2">
                                <div :class="[
                                    'w-5 h-5 rounded flex items-center justify-center text-xs',
                                    question.correct_answer?.includes(option) 
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' 
                                        : 'bg-muted'
                                ]">
                                    <CheckCircle v-if="question.correct_answer?.includes(option)" class="h-3 w-3" />
                                    <span v-else>{{ String.fromCharCode(65 + oIndex) }}</span>
                                </div>
                                <span :class="{ 'font-medium text-green-700 dark:text-green-300': question.correct_answer?.includes(option) }">
                                    {{ option }}
                                </span>
                            </div>
                        </div>

                        <div v-if="question.correct_answer_explanation" class="bg-blue-50 dark:bg-blue-950 p-3 rounded text-sm">
                            <p class="font-medium text-blue-700 dark:text-blue-300 mb-1">Explanation:</p>
                            <p class="text-blue-600 dark:text-blue-400">{{ question.correct_answer_explanation }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
