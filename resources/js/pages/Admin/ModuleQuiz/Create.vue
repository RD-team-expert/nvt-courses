<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

// Icons
import {
    ArrowLeft,
    Plus,
    Trash2,
    GripVertical,
    Clock,
    Target,
    RefreshCw,
    Eye,
    HelpCircle,
    CheckCircle,
    AlertTriangle,
    Save
} from 'lucide-vue-next'

interface Module {
    id: number
    name: string
    order_number: number
    course_online_id: number
}

interface Course {
    id: number
    name: string
}

interface ShowCorrectAnswersOption {
    value: string
    label: string
}

interface Question {
    question_text: string
    type: 'radio' | 'checkbox' | 'text'
    points: number
    options: string[]
    correct_answer: string[]
    correct_answer_explanation: string
}

const props = defineProps<{
    module: Module
    course: Course
    showCorrectAnswersOptions: ShowCorrectAnswersOption[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Course Online", href: "/admin/course-online" },
    { title: props.course.name, href: `/admin/course-online/${props.course.id}` },
    { title: `Module ${props.module.order_number}: ${props.module.name}`, href: '#' },
    { title: "Create Quiz", href: '#' },
]

const form = useForm({
    title: '',
    description: '',
    status: 'draft',
    pass_threshold: 70,
    required_to_proceed: true,
    max_attempts: 3,
    retry_delay_hours: 0,
    show_correct_answers: 'after_pass',
    time_limit_minutes: null as number | null,
    questions: [createEmptyQuestion()] as Question[],
})

function createEmptyQuestion(): Question {
    return {
        question_text: '',
        type: 'radio',
        points: 1,
        options: ['', ''],
        correct_answer: [],
        correct_answer_explanation: '',
    }
}

const totalPoints = computed(() => {
    return form.questions
        .filter(q => q.type !== 'text')
        .reduce((sum, q) => sum + (q.points || 0), 0)
})

const passingScore = computed(() => {
    return Math.ceil((form.pass_threshold / 100) * totalPoints.value)
})

// Question management
function addQuestion() {
    if (form.questions.length < 50) {
        form.questions.push(createEmptyQuestion())
    }
}

function removeQuestion(index: number) {
    if (form.questions.length > 1) {
        form.questions.splice(index, 1)
    }
}

function addOption(questionIndex: number) {
    if (form.questions[questionIndex].options.length < 10) {
        form.questions[questionIndex].options.push('')
    }
}

function removeOption(questionIndex: number, optionIndex: number) {
    const question = form.questions[questionIndex]
    if (question.options.length > 2) {
        const removedOption = question.options[optionIndex]
        question.options.splice(optionIndex, 1)
        // Remove from correct answers if present
        const correctIndex = question.correct_answer.indexOf(removedOption)
        if (correctIndex > -1) {
            question.correct_answer.splice(correctIndex, 1)
        }
    }
}

function resetQuestionOptions(index: number) {
    const question = form.questions[index]
    if (question.type === 'text') {
        question.options = []
        question.correct_answer = []
        question.points = 0
    } else {
        if (question.options.length < 2) {
            question.options = ['', '']
        }
        question.correct_answer = []
        if (question.points === 0) {
            question.points = 1
        }
    }
}

function toggleCorrectAnswer(questionIndex: number, option: string) {
    const question = form.questions[questionIndex]
    
    if (question.type === 'radio') {
        question.correct_answer = [option]
    } else if (question.type === 'checkbox') {
        const index = question.correct_answer.indexOf(option)
        if (index > -1) {
            question.correct_answer.splice(index, 1)
        } else {
            question.correct_answer.push(option)
        }
    }
}

function isCorrectAnswer(questionIndex: number, option: string): boolean {
    return form.questions[questionIndex].correct_answer.includes(option)
}

function submitForm() {
    form.post(route('admin.module-quiz.store', {
        courseOnline: props.course.id,
        courseModule: props.module.id
    }))
}
</script>

<template>
    <Head :title="`Create Quiz - ${module.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-5xl mx-auto space-y-6 pb-12">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button as-child variant="ghost" size="sm">
                    <Link :href="`/admin/course-online/${course.id}`">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Create Module Quiz</h1>
                    <p class="text-muted-foreground">
                        {{ course.name }} → Module {{ module.order_number }}: {{ module.name }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Quiz Details -->
                <Card>
                    <CardHeader>
                        <CardTitle>Quiz Details</CardTitle>
                        <CardDescription>Basic information about the quiz</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <Label for="title">Quiz Title</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="e.g., Module 1 Assessment"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.title" class="text-sm text-destructive mt-1">{{ form.errors.title }}</p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <Label for="description">Description (Optional)</Label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Brief description of what this quiz covers..."
                                    rows="3"
                                    :disabled="form.processing"
                                />
                            </div>

                            <div>
                                <Label for="status">Status</Label>
                                <Select v-model="form.status" :disabled="form.processing">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="published">Published</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Quiz Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Target class="h-5 w-5" />
                            Quiz Settings
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <Label for="pass_threshold">Pass Threshold (%)</Label>
                                <Input
                                    id="pass_threshold"
                                    v-model.number="form.pass_threshold"
                                    type="number"
                                    min="0"
                                    max="100"
                                    :disabled="form.processing"
                                />
                                <p class="text-xs text-muted-foreground mt-1">
                                    Passing score: {{ passingScore }} / {{ totalPoints }} points
                                </p>
                            </div>

                            <div>
                                <Label for="max_attempts">Max Attempts</Label>
                                <Input
                                    id="max_attempts"
                                    v-model.number="form.max_attempts"
                                    type="number"
                                    min="1"
                                    max="100"
                                    :disabled="form.processing"
                                />
                            </div>

                            <div>
                                <Label for="time_limit">Time Limit (minutes)</Label>
                                <Input
                                    id="time_limit"
                                    v-model.number="form.time_limit_minutes"
                                    type="number"
                                    min="1"
                                    max="1440"
                                    placeholder="No limit"
                                    :disabled="form.processing"
                                />
                            </div>

                            <div>
                                <Label for="retry_delay">Retry Delay (hours)</Label>
                                <Input
                                    id="retry_delay"
                                    v-model.number="form.retry_delay_hours"
                                    type="number"
                                    min="0"
                                    max="168"
                                    :disabled="form.processing"
                                />
                                <p class="text-xs text-muted-foreground mt-1">0 = no delay</p>
                            </div>

                            <div>
                                <Label for="show_correct_answers">Show Correct Answers</Label>
                                <Select v-model="form.show_correct_answers" :disabled="form.processing">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem 
                                            v-for="option in showCorrectAnswersOptions" 
                                            :key="option.value" 
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-4 border-t">
                            <Switch
                                id="required_to_proceed"
                                :checked="form.required_to_proceed"
                                @update:checked="form.required_to_proceed = $event"
                                :disabled="form.processing"
                            />
                            <div>
                                <Label for="required_to_proceed" class="cursor-pointer">Required to Proceed</Label>
                                <p class="text-xs text-muted-foreground">
                                    Students must pass this quiz to unlock the next module
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Questions -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <HelpCircle class="h-5 w-5" />
                                    Questions ({{ form.questions.length }})
                                </CardTitle>
                                <CardDescription>
                                    Total Points: {{ totalPoints }}
                                </CardDescription>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addQuestion"
                                :disabled="form.processing || form.questions.length >= 50"
                            >
                                <Plus class="h-4 w-4 mr-2" />
                                Add Question
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div
                            v-for="(question, qIndex) in form.questions"
                            :key="qIndex"
                            class="border rounded-lg p-4 space-y-4 bg-muted/30"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline">Q{{ qIndex + 1 }}</Badge>
                                    <Badge v-if="question.type !== 'text'" variant="secondary">
                                        {{ question.points }} pts
                                    </Badge>
                                </div>
                                <Button
                                    v-if="form.questions.length > 1"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="removeQuestion(qIndex)"
                                    :disabled="form.processing"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-3">
                                    <Label>Question Text</Label>
                                    <Textarea
                                        v-model="question.question_text"
                                        placeholder="Enter your question..."
                                        rows="2"
                                        :disabled="form.processing"
                                    />
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <Label>Type</Label>
                                        <Select 
                                            v-model="question.type" 
                                            @update:model-value="resetQuestionOptions(qIndex)"
                                            :disabled="form.processing"
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="radio">Single Choice</SelectItem>
                                                <SelectItem value="checkbox">Multiple Choice</SelectItem>
                                                <SelectItem value="text">Text Answer</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div v-if="question.type !== 'text'">
                                        <Label>Points</Label>
                                        <Input
                                            v-model.number="question.points"
                                            type="number"
                                            min="0"
                                            :disabled="form.processing"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Options for radio/checkbox -->
                            <div v-if="question.type !== 'text'" class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <Label>Answer Options</Label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="addOption(qIndex)"
                                        :disabled="form.processing || question.options.length >= 10"
                                    >
                                        <Plus class="h-3 w-3 mr-1" />
                                        Add Option
                                    </Button>
                                </div>
                                
                                <div 
                                    v-for="(option, oIndex) in question.options" 
                                    :key="oIndex"
                                    class="flex items-center gap-2"
                                >
                                    <input
                                        :type="question.type === 'radio' ? 'radio' : 'checkbox'"
                                        :name="`question_${qIndex}_correct`"
                                        :checked="isCorrectAnswer(qIndex, option)"
                                        @change="toggleCorrectAnswer(qIndex, option)"
                                        :disabled="form.processing || !option.trim()"
                                        class="h-4 w-4"
                                    />
                                    <Input
                                        v-model="question.options[oIndex]"
                                        :placeholder="`Option ${oIndex + 1}`"
                                        class="flex-1"
                                        :disabled="form.processing"
                                    />
                                    <Button
                                        v-if="question.options.length > 2"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="removeOption(qIndex, oIndex)"
                                        :disabled="form.processing"
                                    >
                                        <Trash2 class="h-4 w-4 text-muted-foreground" />
                                    </Button>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ question.type === 'radio' ? 'Select one correct answer' : 'Select all correct answers' }}
                                </p>
                            </div>

                            <!-- Text question note -->
                            <div v-else class="bg-blue-50 dark:bg-blue-950 p-3 rounded-lg">
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    📝 Text questions require manual grading and don't contribute to automatic scoring.
                                </p>
                            </div>

                            <!-- Explanation -->
                            <div>
                                <Label>Explanation (Optional)</Label>
                                <Textarea
                                    v-model="question.correct_answer_explanation"
                                    placeholder="Explain why this answer is correct..."
                                    rows="2"
                                    :disabled="form.processing"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3">
                    <Button as-child variant="outline" :disabled="form.processing">
                        <Link :href="`/admin/course-online/${course.id}`">Cancel</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Save v-if="!form.processing" class="h-4 w-4 mr-2" />
                        <RefreshCw v-else class="h-4 w-4 mr-2 animate-spin" />
                        {{ form.processing ? 'Creating...' : 'Create Quiz' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
