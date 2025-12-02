<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

// Icons
import {
    ArrowLeft,
    Plus,
    Edit,
    Trash2,
    Eye,
    BarChart3,
    Clock,
    CheckCircle,
    XCircle,
    AlertTriangle,
    ClipboardList,
    Users,
    Target,
    BookOpen
} from 'lucide-vue-next'

interface Quiz {
    id: number
    title: string
    description: string | null
    time_limit: number | null
    pass_threshold: number
    max_attempts: number | null
    retry_delay_hours: number
    show_correct_answers: string
    is_active: boolean
    questions_count: number
    attempts_count: number
    pass_rate: number
    created_at: string
}

interface Module {
    id: number
    name: string
    order_number: number
    has_quiz: boolean
    quiz_required: boolean
}

interface Course {
    id: number
    name: string
}

const props = defineProps<{
    course: Course
    module: Module
    quizzes: Quiz[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Course Online", href: "/admin/course-online" },
    { title: props.course.name, href: `/admin/course-online/${props.course.id}` },
    { title: `Module ${props.module.order_number}: ${props.module.name}`, href: '#' },
    { title: "Quiz Management", href: '#' },
]

const deleteQuiz = (quizId: number) => {
    if (confirm('Are you sure you want to delete this quiz? This will also delete all questions and attempts.')) {
        router.delete(route('admin.module-quiz.destroy', {
            courseOnline: props.course.id,
            courseModule: props.module.id,
            quiz: quizId
        }))
    }
}

const toggleQuizStatus = (quizId: number) => {
    router.patch(route('admin.module-quiz.toggle-status', {
        courseOnline: props.course.id,
        courseModule: props.module.id,
        quiz: quizId
    }))
}

const getShowCorrectAnswersLabel = (value: string) => {
    const labels: Record<string, string> = {
        'never': 'Never',
        'after_pass': 'After Passing',
        'after_max_attempts': 'After Max Attempts',
        'always': 'Always'
    }
    return labels[value] || value
}

const formatTime = (minutes: number | null) => {
    if (!minutes) return 'No limit'
    if (minutes < 60) return `${minutes} min`
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`
}
</script>

<template>
    <Head :title="`Quiz Management - ${module.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Button as-child variant="ghost">
                        <Link :href="`/admin/course-online/${course.id}`">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Course
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold flex items-center gap-2">
                            <ClipboardList class="h-6 w-6 text-primary" />
                            Module Quiz Management
                        </h1>
                        <p class="text-muted-foreground">
                            {{ course.name }} → Module {{ module.order_number }}: {{ module.name }}
                        </p>
                    </div>
                </div>

                <Button as-child>
                    <Link :href="route('admin.module-quiz.create', { courseOnline: course.id, courseModule: module.id })">
                        <Plus class="h-4 w-4 mr-2" />
                        Create Quiz
                    </Link>
                </Button>
            </div>

            <!-- Module Info Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <BookOpen class="h-5 w-5 text-primary" />
                        Module Information
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                                <Target class="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Quiz Required</p>
                                <p class="font-medium">
                                    <Badge :variant="module.quiz_required ? 'destructive' : 'secondary'">
                                        {{ module.quiz_required ? 'Yes - Must Pass' : 'No' }}
                                    </Badge>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                <ClipboardList class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Total Quizzes</p>
                                <p class="font-medium">{{ quizzes.length }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                <CheckCircle class="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p class="text-sm text-muted-foreground">Active Quizzes</p>
                                <p class="font-medium">{{ quizzes.filter(q => q.is_active).length }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Empty State -->
            <Card v-if="quizzes.length === 0">
                <CardContent class="py-16">
                    <div class="text-center">
                        <ClipboardList class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium mb-2">No Quizzes Yet</h3>
                        <p class="text-muted-foreground mb-6 max-w-md mx-auto">
                            Create a quiz for this module to test students' understanding before they can proceed to the next module.
                        </p>
                        <Button as-child>
                            <Link :href="route('admin.module-quiz.create', { courseOnline: course.id, courseModule: module.id })">
                                <Plus class="h-4 w-4 mr-2" />
                                Create First Quiz
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Quizzes Table -->
            <Card v-else>
                <CardHeader>
                    <CardTitle>Module Quizzes</CardTitle>
                    <CardDescription>
                        Manage quizzes for this module. Students must pass the quiz to unlock the next module.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Quiz</TableHead>
                                <TableHead>Settings</TableHead>
                                <TableHead>Statistics</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="quiz in quizzes" :key="quiz.id">
                                <TableCell>
                                    <div>
                                        <p class="font-medium">{{ quiz.title }}</p>
                                        <p v-if="quiz.description" class="text-sm text-muted-foreground line-clamp-1">
                                            {{ quiz.description }}
                                        </p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <Badge variant="outline" class="text-xs">
                                                {{ quiz.questions_count }} questions
                                            </Badge>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-1 text-sm">
                                        <div class="flex items-center gap-1">
                                            <Clock class="h-3 w-3 text-muted-foreground" />
                                            {{ formatTime(quiz.time_limit) }}
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <Target class="h-3 w-3 text-muted-foreground" />
                                            Pass: {{ quiz.pass_threshold }}%
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <Users class="h-3 w-3 text-muted-foreground" />
                                            {{ quiz.max_attempts ? `${quiz.max_attempts} attempts` : 'Unlimited' }}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-1 text-sm">
                                        <div class="flex items-center gap-1">
                                            <Users class="h-3 w-3 text-muted-foreground" />
                                            {{ quiz.attempts_count }} attempts
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <BarChart3 class="h-3 w-3 text-muted-foreground" />
                                            {{ quiz.pass_rate.toFixed(1) }}% pass rate
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge 
                                        :variant="quiz.is_active ? 'default' : 'secondary'"
                                        class="cursor-pointer"
                                        @click="toggleQuizStatus(quiz.id)"
                                    >
                                        <CheckCircle v-if="quiz.is_active" class="h-3 w-3 mr-1" />
                                        <XCircle v-else class="h-3 w-3 mr-1" />
                                        {{ quiz.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            as-child
                                            variant="ghost"
                                            size="sm"
                                            title="View Quiz"
                                        >
                                            <Link :href="route('admin.module-quiz.show', { courseOnline: course.id, courseModule: module.id, quiz: quiz.id })">
                                                <Eye class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            as-child
                                            variant="ghost"
                                            size="sm"
                                            title="Edit Quiz"
                                        >
                                            <Link :href="route('admin.module-quiz.edit', { courseOnline: course.id, courseModule: module.id, quiz: quiz.id })">
                                                <Edit class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            as-child
                                            variant="ghost"
                                            size="sm"
                                            title="View Statistics"
                                        >
                                            <Link :href="route('admin.module-quiz.statistics', { courseOnline: course.id, courseModule: module.id, quiz: quiz.id })">
                                                <BarChart3 class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                            title="Delete Quiz"
                                            @click="deleteQuiz(quiz.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Info Alert -->
            <Alert>
                <AlertTriangle class="h-4 w-4" />
                <AlertDescription>
                    <strong>Note:</strong> When quiz is required for this module, students must pass the quiz 
                    to unlock the next module. Configure the pass threshold and attempt limits carefully.
                </AlertDescription>
            </Alert>
        </div>
    </AppLayout>
</template>