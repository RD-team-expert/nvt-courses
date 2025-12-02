<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Textarea } from '@/components/ui/textarea'
import { Alert, AlertDescription } from '@/components/ui/alert'

import { Clock, ChevronLeft, ChevronRight, Send, AlertTriangle, CheckCircle } from 'lucide-vue-next'

interface Question {
    id: number
    question_text: string
    type: 'radio' | 'checkbox' | 'text'
    points: number
    options: string[]
    order: number
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
    time_limit_minutes: number | null
}

interface Attempt {
    id: number
    attempt_number: number
    started_at: string
}

const props = defineProps<{
    module: Module
    course: Course
    quiz: Quiz
    attempt: Attempt
    questions: Question[]
    existingAnswers: Record<number, string[]>
    timeRemaining: number | null
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { name: "My Courses", href: "/courses-online" },
    { name: props.course.name, href: `/courses-online/${props.course.id}` },
    { name: props.module.name, href: `/courses-online/${props.course.id}` },
    { name: "Taking Quiz", href: "" },
]

// State
const currentQuestionIndex = ref(0)
const answers = ref<Record<number, string[]>>({})
const isSubmitting = ref(false)
const timeLeft = ref(props.timeRemaining)
let timerInterval: number | null = null

// Initialize answers from existing
onMounted(() => {
    if (props.existingAnswers) {
        answers.value = { ...props.existingAnswers }
    }
    
    // Start timer if time limit exists
    if (props.quiz.time_limit_minutes && timeLeft.value !== null) {
        startTimer()
    }
})

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval)
    }
})

const startTimer = () => {
    timerInterval = setInterval(() => {
        if (timeLeft.value !== null && timeLeft.value > 0) {
            timeLeft.value--
        } else if (timeLeft.value === 0) {
            // Auto-submit when time runs out
            submitQuiz()
        }
    }, 1000) as unknown as number
}

const formatTimeLeft = computed(() => {
    if (timeLeft.value === null) return null
    const minutes = Math.floor(timeLeft.value / 60)
    const seconds = timeLeft.value % 60
    return `${minutes}:${seconds.toString().padStart(2, '0')}`
})

const isTimeLow = computed(() => {
    return timeLeft.value !== null && timeLeft.value < 60
})

const currentQuestion = computed(() => props.questions[currentQuestionIndex.value])

const progress = computed(() => {
    const answered = Object.keys(answers.value).filter(k => {
        const ans = answers.value[parseInt(k)]
        return ans && ans.length > 0 && ans.some(a => a.trim() !== '')
    }).length
    return (answered / props.questions.length) * 100
})

const answeredCount = computed(() => {
    return Object.keys(answers.value).filter(k => {
        const ans = answers.value[parseInt(k)]
        return ans && ans.length > 0 && ans.some(a => a.trim() !== '')
    }).length
})

// Navigation
const goToQuestion = (index: number) => {
    if (index >= 0 && index < props.questions.length) {
        saveCurrentAnswer()
        currentQuestionIndex.value = index
    }
}

const nextQuestion = () => goToQuestion(currentQuestionIndex.value + 1)
const prevQuestion = () => goToQuestion(currentQuestionIndex.value - 1)

// Answer handling
const getCurrentAnswer = () => {
    return answers.value[currentQuestion.value.id] || []
}

const setAnswer = (questionId: number, value: string[]) => {
    answers.value[questionId] = value
}

const toggleOption = (option: string) => {
    const q = currentQuestion.value
    const current = getCurrentAnswer()
    
    if (q.type === 'radio') {
        setAnswer(q.id, [option])
    } else if (q.type === 'checkbox') {
        const index = current.indexOf(option)
        if (index > -1) {
            current.splice(index, 1)
        } else {
            current.push(option)
        }
        setAnswer(q.id, [...current])
    }
}

const setTextAnswer = (text: string) => {
    setAnswer(currentQuestion.value.id, [text])
}

const isOptionSelected = (option: string) => {
    return getCurrentAnswer().includes(option)
}

const isQuestionAnswered = (questionId: number) => {
    const ans = answers.value[questionId]
    return ans && ans.length > 0 && ans.some(a => a.trim() !== '')
}

// Save answer to server (auto-save)
const saveCurrentAnswer = () => {
    const q = currentQuestion.value
    const answer = answers.value[q.id]
    
    if (answer) {
        fetch(route('courses-online.modules.quiz.save-answer', {
            courseOnline: props.course.id,
            courseModule: props.module.id,
            attempt: props.attempt.id
        }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                question_id: q.id,
                answer: answer
            })
        }).catch(console.error)
    }
}

// Submit quiz
const submitQuiz = () => {
    if (isSubmitting.value) return
    
    isSubmitting.value = true
    saveCurrentAnswer()
    
    const formattedAnswers = Object.entries(answers.value).map(([questionId, answer]) => ({
        question_id: parseInt(questionId),
        answer: answer
    }))
    
    router.post(route('courses-online.modules.quiz.submit', {
        courseOnline: props.course.id,
        courseModule: props.module.id,
        attempt: props.attempt.id
    }), {
        answers: formattedAnswers
    })
}

const confirmSubmit = () => {
    const unanswered = props.questions.length - answeredCount.value
    if (unanswered > 0) {
        if (confirm(`You have ${unanswered} unanswered question(s). Are you sure you want to submit?`)) {
            submitQuiz()
        }
    } else {
        submitQuiz()
    }
}
</script>

<template>
    <Head :title="`Quiz - ${quiz.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full  mx-auto space-y-4 sm:space-y-6 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 pb-20 sm:pb-12">
            <!-- Header with Timer -->
            <Card class="sticky top-0 z-10 shadow-md">
                <CardContent class="py-4">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex-1">
                            <h1 class="text-lg sm:text-xl font-bold">{{ quiz.title }}</h1>
                            <p class="text-xs sm:text-sm text-muted-foreground">Attempt #{{ attempt.attempt_number }} • {{ quiz.total_points }} points total</p>
                        </div>
                        <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
                            <div v-if="formatTimeLeft" :class="['flex items-center gap-2 px-3 sm:px-4 py-2 rounded-lg font-mono text-base sm:text-lg flex-1 sm:flex-initial justify-center font-bold', isTimeLow ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 animate-pulse' : 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300']">
                                <Clock class="h-4 w-4 sm:h-5 sm:w-5" />
                                {{ formatTimeLeft }}
                            </div>
                            <Button @click="confirmSubmit" :disabled="isSubmitting" size="default" class="w-full sm:w-auto">
                                <Send class="h-4 w-4 mr-2" />
                                Submit Quiz
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Progress -->
            <Card class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-950 dark:to-purple-950 border-blue-200 dark:border-blue-800">
                <CardContent class="py-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Your Progress</span>
                            <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5">Keep going! You're doing great 💪</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ answeredCount }}</span>
                            <span class="text-sm text-blue-700 dark:text-blue-300"> / {{ questions.length }}</span>
                        </div>
                    </div>
                    <Progress :model-value="progress" class="h-3" />
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-2 text-center">
                        {{ Math.round(progress) }}% Complete
                    </p>
                </CardContent>
            </Card>

            <!-- Question Navigation -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base">Quick Navigation</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="(q, index) in questions"
                            :key="q.id"
                            :variant="currentQuestionIndex === index ? 'default' : isQuestionAnswered(q.id) ? 'secondary' : 'outline'"
                            size="sm"
                            class="w-10 h-10 relative"
                            @click="goToQuestion(index)"
                        >
                            {{ index + 1 }}
                            <CheckCircle v-if="isQuestionAnswered(q.id) && currentQuestionIndex !== index" class="absolute -top-1 -right-1 h-4 w-4 text-green-500 bg-white dark:bg-gray-900 rounded-full" />
                        </Button>
                    </div>
                    <div class="flex items-center gap-4 mt-3 text-xs text-muted-foreground">
                        <div class="flex items-center gap-1">
                            <div class="w-3 h-3 rounded bg-primary"></div>
                            <span>Current</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-3 h-3 rounded bg-secondary"></div>
                            <span>Answered</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-3 h-3 rounded border-2"></div>
                            <span>Not Answered</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Current Question -->
            <Card class="border-2 border-primary/20">
                <CardHeader class="bg-gradient-to-r from-primary/5 to-purple-500/5">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <Badge variant="default" class="text-sm">Question {{ currentQuestionIndex + 1 }} of {{ questions.length }}</Badge>
                        <div class="flex items-center gap-2">
                            <Badge v-if="currentQuestion.type === 'radio'" variant="outline" class="text-xs">
                                Single Choice
                            </Badge>
                            <Badge v-else-if="currentQuestion.type === 'checkbox'" variant="outline" class="text-xs">
                                Multiple Choice
                            </Badge>
                            <Badge v-else variant="outline" class="text-xs">
                                Text Answer
                            </Badge>
                            <Badge variant="secondary" class="text-sm">
                                {{ currentQuestion.points }} point{{ currentQuestion.points !== 1 ? 's' : '' }}
                            </Badge>
                        </div>
                    </div>
                    <CardTitle class="text-lg sm:text-xl mt-4 leading-relaxed">{{ currentQuestion.question_text }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Radio/Checkbox Options -->
                    <div v-if="currentQuestion.type !== 'text'" class="space-y-3">
                        <Alert class="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
                            <AlertDescription class="text-sm text-blue-800 dark:text-blue-200">
                                {{ currentQuestion.type === 'radio' ? '📌 Select ONE answer' : '📌 Select ALL that apply' }}
                            </AlertDescription>
                        </Alert>
                        <div
                            v-for="(option, index) in currentQuestion.options"
                            :key="index"
                            @click="toggleOption(option)"
                            :class="[
                                'flex items-start gap-3 p-4 rounded-lg border-2 cursor-pointer transition-all duration-200',
                                isOptionSelected(option) 
                                    ? 'border-primary bg-primary/10 shadow-md' 
                                    : 'border-border hover:border-primary/50 hover:bg-muted/50 hover:shadow-sm'
                            ]"
                        >
                            <div class="flex items-center justify-center mt-0.5">
                                <div :class="[
                                    'w-6 h-6 rounded flex items-center justify-center border-2 shrink-0 transition-all',
                                    currentQuestion.type === 'radio' ? 'rounded-full' : 'rounded',
                                    isOptionSelected(option) ? 'border-primary bg-primary scale-110' : 'border-gray-400'
                                ]">
                                    <CheckCircle v-if="isOptionSelected(option)" class="h-4 w-4 text-primary-foreground" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start gap-2">
                                    <span class="font-medium text-primary text-sm">{{ String.fromCharCode(65 + index) }}.</span>
                                    <span :class="['text-sm sm:text-base break-words', isOptionSelected(option) ? 'font-medium' : '']">{{ option }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Text Answer -->
                    <div v-else class="space-y-3">
                        <Alert class="bg-purple-50 dark:bg-purple-950 border-purple-200 dark:border-purple-800">
                            <AlertDescription class="text-sm text-purple-800 dark:text-purple-200">
                                ✍️ Write your answer below. This will be manually graded by your instructor.
                            </AlertDescription>
                        </Alert>
                        <Textarea
                            :model-value="getCurrentAnswer()[0] || ''"
                            @update:model-value="setTextAnswer($event)"
                            placeholder="Type your detailed answer here..."
                            rows="8"
                            class="text-base"
                        />
                        <p class="text-xs text-muted-foreground">
                            💡 Tip: Be clear and detailed in your response for better evaluation.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Navigation Buttons -->
            <Card class="bg-muted/50">
                <CardContent class="py-4">
                    <div class="flex items-center justify-between gap-3">
                        <Button
                            variant="outline"
                            size="lg"
                            @click="prevQuestion"
                            :disabled="currentQuestionIndex === 0"
                            class="flex-1 sm:flex-initial"
                        >
                            <ChevronLeft class="h-4 w-4 mr-2" />
                            Previous
                        </Button>
                        
                        <div class="hidden sm:block text-sm text-muted-foreground">
                            Question {{ currentQuestionIndex + 1 }} of {{ questions.length }}
                        </div>
                        
                        <Button
                            v-if="currentQuestionIndex < questions.length - 1"
                            size="lg"
                            @click="nextQuestion"
                            class="flex-1 sm:flex-initial"
                        >
                            Next
                            <ChevronRight class="h-4 w-4 ml-2" />
                        </Button>
                        <Button
                            v-else
                            size="lg"
                            @click="confirmSubmit"
                            :disabled="isSubmitting"
                            class="flex-1 sm:flex-initial bg-green-600 hover:bg-green-700"
                        >
                            <Send class="h-4 w-4 mr-2" />
                            Submit Quiz
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Warning -->
            <Alert v-if="isTimeLow" variant="destructive">
                <AlertTriangle class="h-4 w-4" />
                <AlertDescription>
                    Less than 1 minute remaining! Your quiz will be automatically submitted when time runs out.
                </AlertDescription>
            </Alert>
        </div>
    </AppLayout>
</template>
