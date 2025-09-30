<!-- Quiz Taking Page -->
<script setup>
import { Link, router } from '@inertiajs/vue3'
import { ref, onMounted, computed, onUnmounted } from 'vue'
import AppLayout from "@/layouts/AppLayout.vue"

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Progress } from '@/components/ui/progress'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Separator } from '@/components/ui/separator'

// Icons
import {
    Clock,
    CheckCircle,
    AlertTriangle,
    FileText,
    Save,
    Send,
    Brain,
    Timer,
    Target,
    HelpCircle,
    ArrowLeft
} from 'lucide-vue-next'

const props = defineProps({
    quiz: {
        type: Object,
        required: true,
    },
    questions: {
        type: Array,
        default: () => [],
    },
    hasExistingAttempt: {
        type: Boolean,
        default: false,
    },
})

const form = ref({
    answers: {},
})
const submitting = ref(false)
const savingDraft = ref(false)
const timeRemaining = ref(props.quiz.time_limit ? props.quiz.time_limit * 60 : 0)
const timerInterval = ref(null)

// Initialize form answers based on question types
onMounted(() => {
    props.questions.forEach(question => {
        if (question.type === 'checkbox') {
            form.value.answers[question.id] = []
        } else {
            form.value.answers[question.id] = ''
        }
    })

    // Start timer if time limit exists
    if (props.quiz.time_limit && timeRemaining.value > 0) {
        timerInterval.value = setInterval(() => {
            timeRemaining.value--
            if (timeRemaining.value <= 0) {
                clearInterval(timerInterval.value)
                submitAttempt() // Auto-submit when time runs out
            }
        }, 1000)
    }
})

onUnmounted(() => {
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
    }
})

// Computed properties
const answeredQuestions = computed(() => {
    return Object.values(form.value.answers).filter(answer => {
        if (Array.isArray(answer)) {
            return answer.length > 0
        }
        return answer && answer.trim() !== ''
    }).length
})

const progressPercentage = computed(() => {
    if (props.questions.length === 0) return 0
    return (answeredQuestions.value / props.questions.length) * 100
})

const allQuestionsAnswered = computed(() => {
    return answeredQuestions.value === props.questions.length
})

const breadcrumbs = computed(() => [
    { name: 'Quizzes', route: 'quizzes.index' },
    { name: props.quiz?.title || 'Quiz', route: null },
])

// Methods
const isQuestionAnswered = (questionId) => {
    const answer = form.value.answers[questionId]
    if (Array.isArray(answer)) {
        return answer.length > 0
    }
    return answer && answer.trim() !== ''
}

const formatTime = (seconds) => {
    const minutes = Math.floor(seconds / 60)
    const remainingSeconds = seconds % 60
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const saveDraft = async () => {
    savingDraft.value = true
    try {
        await router.post(route('quizzes.draft', props.quiz.id), {
            answers: Object.entries(form.value.answers).map(([questionId, answer]) => ({
                question_id: parseInt(questionId),
                answer: answer,
            })),
        })
    } catch (error) {
        console.error('Draft save error:', error)
    } finally {
        savingDraft.value = false
    }
}

const submitAttempt = async () => {
    submitting.value = true
    try {
        await router.post(route('quizzes.store', props.quiz.id), {
            answers: Object.entries(form.value.answers).map(([questionId, answer]) => ({
                question_id: parseInt(questionId),
                answer: answer,
            })),
        })
    } catch (error) {
        console.error('Submission error:', error)
    } finally {
        submitting.value = false
    }
}

// Handle checkbox changes
const handleCheckboxChange = (questionId, option, checked) => {
    const currentAnswers = form.value.answers[questionId] || []
    if (checked) {
        if (!currentAnswers.includes(option)) {
            form.value.answers[questionId] = [...currentAnswers, option]
        }
    } else {
        form.value.answers[questionId] = currentAnswers.filter(answer => answer !== option)
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-background to-secondary/20">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Quiz Header -->
                <Card class="mb-6">
                    <CardContent class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h1 class="text-3xl font-bold mb-2">{{ quiz.title }}</h1>
                                <p class="text-muted-foreground mb-4">{{ quiz.description || 'No description available' }}</p>

                                <!-- Quiz Info Grid -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <Card class="bg-accent/50">
                                        <CardContent class="p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <Target class="h-4 w-4 text-primary" />
                                                <p class="text-muted-foreground">Pass Threshold</p>
                                            </div>
                                            <p class="font-semibold">{{ quiz.pass_threshold }}%</p>
                                        </CardContent>
                                    </Card>
                                    <Card class="bg-accent/50">
                                        <CardContent class="p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <FileText class="h-4 w-4 text-green-600" />
                                                <p class="text-muted-foreground">Total Points</p>
                                            </div>
                                            <p class="font-semibold">{{ quiz.total_points || 'N/A' }}</p>
                                        </CardContent>
                                    </Card>
                                    <Card class="bg-accent/50">
                                        <CardContent class="p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <HelpCircle class="h-4 w-4 text-blue-600" />
                                                <p class="text-muted-foreground">Questions</p>
                                            </div>
                                            <p class="font-semibold">{{ questions.length }}</p>
                                        </CardContent>
                                    </Card>
                                    <Card class="bg-accent/50">
                                        <CardContent class="p-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <Timer class="h-4 w-4 text-orange-600" />
                                                <p class="text-muted-foreground">Time Limit</p>
                                            </div>
                                            <p class="font-semibold">{{ quiz.time_limit ? `${quiz.time_limit} min` : 'No limit' }}</p>
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>

                            <!-- Quiz Status Badge -->
                            <div class="ml-6">
                                <Badge :variant="hasExistingAttempt ? 'secondary' : 'default'">
                                    {{ hasExistingAttempt ? 'Continuing Attempt' : 'New Attempt' }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Timer (if time limit exists) -->
                <Card v-if="quiz.time_limit && timeRemaining > 0" class="mb-6">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <Clock :class="['h-5 w-5 mr-2', timeRemaining < 300 ? 'text-destructive' : 'text-orange-500']" />
                                <span class="text-sm font-medium">Time Remaining</span>
                            </div>
                            <div class="flex items-center">
                                <span :class="[
                                    'text-lg font-bold',
                                    timeRemaining < 300 ? 'text-destructive' : 'text-foreground'
                                ]">
                                    {{ formatTime(timeRemaining) }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <Progress
                                :value="(timeRemaining / (quiz.time_limit * 60)) * 100"
                                :class="timeRemaining < 300 ? 'text-destructive' : 'text-primary'"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Warning for existing attempt -->
                <Alert v-if="hasExistingAttempt" class="mb-6">
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                        <div>
                            <h3 class="font-semibold mb-1">Continuing Previous Attempt</h3>
                            <p>You have an incomplete attempt for this quiz. Your previous answers have been saved. You can modify them before final submission.</p>
                        </div>
                    </AlertDescription>
                </Alert>

                <!-- Progress Indicator -->
                <Card class="mb-6">
                    <CardContent class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium">Progress</span>
                            <span class="text-sm text-muted-foreground">{{ answeredQuestions }}/{{ questions.length }} answered</span>
                        </div>
                        <Progress :value="progressPercentage" class="w-full" />
                    </CardContent>
                </Card>

                <!-- Quiz Questions -->
                <div v-if="questions.length" class="space-y-6">
                    <form @submit.prevent="submitAttempt">
                        <Card v-for="(question, index) in questions" :key="question.id" class="overflow-hidden">
                            <!-- Question Header -->
                            <CardHeader class="bg-gradient-to-r from-accent to-accent/50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-primary text-primary-foreground rounded-full flex items-center justify-center text-sm font-bold mr-3">
                                            {{ index + 1 }}
                                        </div>
                                        <CardTitle class="text-lg">
                                            Question {{ index + 1 }}
                                        </CardTitle>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Badge variant="secondary">
                                            {{ question.points || 'N/A' }} points
                                        </Badge>
                                        <div :class="[
                                            'w-3 h-3 rounded-full',
                                            isQuestionAnswered(question.id) ? 'bg-green-500' : 'bg-muted'
                                        ]"></div>
                                    </div>
                                </div>
                            </CardHeader>

                            <!-- Question Content -->
                            <CardContent class="p-6">
                                <p class="text-foreground mb-6 text-base leading-relaxed">{{ question.question_text }}</p>

                                <!-- Radio buttons (Single Choice) -->
                                <div v-if="question.type === 'radio'" class="space-y-3">
                                    <Label class="text-sm font-medium">Select one answer:</Label>
                                    <RadioGroup v-model="form.answers[question.id]" class="space-y-2">
                                        <div v-for="(option, optIndex) in question.options" :key="optIndex"
                                             class="flex items-start space-x-3 p-3 rounded-lg border hover:border-primary hover:bg-accent/50 transition-colors cursor-pointer">
                                            <RadioGroupItem
                                                :id="`answer-${question.id}-${optIndex}`"
                                                :value="option"
                                                class="mt-1"
                                            />
                                            <Label :for="`answer-${question.id}-${optIndex}`"
                                                   class="text-sm cursor-pointer flex-1">
                                                {{ option }}
                                            </Label>
                                        </div>
                                    </RadioGroup>
                                </div>

                                <!-- Checkboxes (Multiple Choice) -->
                                <div v-if="question.type === 'checkbox'" class="space-y-3">
                                    <Label class="text-sm font-medium">Select all that apply:</Label>
                                    <div v-for="(option, optIndex) in question.options" :key="optIndex"
                                         class="flex items-start space-x-3 p-3 rounded-lg border hover:border-primary hover:bg-accent/50 transition-colors cursor-pointer">
                                        <Checkbox
                                            :id="`answer-${question.id}-${optIndex}`"
                                            :checked="(form.answers[question.id] || []).includes(option)"
                                            @update:checked="(checked) => handleCheckboxChange(question.id, option, checked)"
                                            class="mt-1"
                                        />
                                        <Label :for="`answer-${question.id}-${optIndex}`"
                                               class="text-sm cursor-pointer flex-1">
                                            {{ option }}
                                        </Label>
                                    </div>
                                </div>

                                <!-- Text area (Essay/Short Answer) -->
                                <div v-if="question.type === 'text'">
                                    <Label class="text-sm font-medium mb-3">Your answer:</Label>
                                    <Textarea
                                        v-model="form.answers[question.id]"
                                        rows="5"
                                        placeholder="Type your answer here..."
                                        required
                                    />
                                    <div class="mt-2 text-xs text-muted-foreground">
                                        Character count: {{ (form.answers[question.id] || '').length }}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Navigation & Submit -->
                        <Card class="mt-8">
                            <CardContent class="p-6">
                                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                    <!-- Quiz Summary -->
                                    <div class="flex items-center space-x-6 text-sm text-muted-foreground">
                                        <div class="flex items-center">
                                            <CheckCircle class="w-4 h-4 mr-1 text-green-500" />
                                            <span>{{ answeredQuestions }} answered</span>
                                        </div>
                                        <div class="flex items-center">
                                            <Clock class="w-4 h-4 mr-1 text-muted-foreground" />
                                            <span>{{ questions.length - answeredQuestions }} remaining</span>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center space-x-3">
                                        <!-- Save Draft Button (if applicable) -->
                                        <Button
                                            v-if="!hasExistingAttempt"
                                            type="button"
                                            @click="saveDraft"
                                            :disabled="submitting || savingDraft"
                                            variant="outline"
                                        >
                                            <Save v-if="!savingDraft" class="w-4 h-4 mr-2" />
                                            <div v-else class="w-4 h-4 mr-2 animate-spin rounded-full border-b-2 border-current"></div>
                                            {{ savingDraft ? 'Saving...' : 'Save Draft' }}
                                        </Button>

                                        <!-- Submit Button -->
                                        <Button
                                            type="submit"
                                            :disabled="submitting || !allQuestionsAnswered"
                                        >
                                            <Send v-if="!submitting" class="w-4 h-4 mr-2" />
                                            <div v-else class="w-4 h-4 mr-2 animate-spin rounded-full border-b-2 border-current"></div>
                                            {{ submitting ? 'Submitting...' : (hasExistingAttempt ? 'Complete Quiz' : 'Submit Quiz') }}
                                        </Button>
                                    </div>
                                </div>

                                <!-- Warning if not all questions answered -->
                                <Alert v-if="!allQuestionsAnswered" class="mt-4" variant="destructive">
                                    <AlertTriangle class="h-4 w-4" />
                                    <AlertDescription>
                                        <p>
                                            Please answer all questions before submitting.
                                            <span class="font-medium">{{ questions.length - answeredQuestions }} question(s) remaining.</span>
                                        </p>
                                    </AlertDescription>
                                </Alert>
                            </CardContent>
                        </Card>
                    </form>
                </div>

                <!-- Empty State -->
                <Card v-else class="text-center py-16">
                    <CardContent>
                        <div class="mx-auto w-24 h-24 bg-accent rounded-full flex items-center justify-center mb-4">
                            <HelpCircle class="w-12 h-12 text-muted-foreground" />
                        </div>
                        <CardTitle class="mb-2">No Questions Available</CardTitle>
                        <p class="text-muted-foreground mb-4">This quiz doesn't have any questions yet.</p>
                        <Button asChild>
                            <Link :href="route('quizzes.index')">
                                <ArrowLeft class="w-4 h-4 mr-2" />
                                Back to Quizzes
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
