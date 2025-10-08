<!--
  Quizzes Index Page
  Display available quizzes with filtering, search, and progress tracking
-->
<script setup>
import { Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Pagination from '@/Components/Pagination.vue'
import AppLayout from "@/layouts/AppLayout.vue"
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    FileText,
    CheckCircle,
    Clock,
    Search,
    List,
    Grid3X3,
    Brain,
    BarChart3
} from 'lucide-vue-next'

const props = defineProps({
    quizzes: {
        type: Object,
        required: true,
    },
})

const searchQuery = ref('')
const selectedCourse = ref('all') // Default to 'all' instead of empty string
const viewMode = ref('grid')

const breadcrumbs = [
    { name: 'Quizzes', route: null },
]

// Computed properties
const uniqueCourses = computed(() => {
    if (!props.quizzes.data || !props.quizzes.data.length) return []
    const courses = props.quizzes.data.map(quiz => quiz.course)
    return courses.filter((course, index, self) =>
        index === self.findIndex(c => c.id === course.id)
    )
})

const filteredQuizzes = computed(() => {
    if (!props.quizzes.data || !props.quizzes.data.length) return []

    let filtered = props.quizzes.data

    if (searchQuery.value) {
        filtered = filtered.filter(quiz =>
            quiz.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (quiz.description && quiz.description.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            quiz.course.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        )
    }

    // Updated to handle 'all' value properly
    if (selectedCourse.value && selectedCourse.value !== 'all') {
        filtered = filtered.filter(quiz => quiz.course.id.toString() === selectedCourse.value)
    }

    return filtered
})

const completedQuizzes = computed(() => {
    if (!props.quizzes.data || !props.quizzes.data.length) return 0
    // ✅ Count quizzes that are passed OR reached max attempts
    return props.quizzes.data.filter(quiz =>
        quiz.has_passed || quiz.attempts >= 3
    ).length
})

const pendingQuizzes = computed(() => {
    if (!props.quizzes.data || !props.quizzes.data.length) return 0
    // ✅ Count quizzes that are not passed AND under max attempts
    return props.quizzes.data.filter(quiz =>
        !quiz.has_passed && quiz.attempts < 3
    ).length
})

// Methods
const toggleView = () => {
    viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid'
}

const clearFilters = () => {
    searchQuery.value = ''
    selectedCourse.value = 'all' // Reset to 'all' instead of empty string
}

const canTakeQuiz = (quiz) => {
    // ✅ Cannot retake if already passed
    if (quiz.has_passed) {
        return false
    }
    // Can take if under 3 attempts and hasn't passed
    return quiz.attempts < 3
}

const isQuizCompleted = (quiz) => {
    // ✅ Quiz is completed if passed or reached max attempts
    return quiz.has_passed || quiz.attempts >= 3
}

const getQuizStatus = (quiz) => {
    // ✅ Show "Passed" if user has passed
    if (quiz.has_passed) return 'Passed'
    if (quiz.attempts >= 3) return 'Failed'
    if (quiz.attempts > 0) return 'In Progress'
    return 'Not Started'
}

const getQuizStatusColorClass = (quiz) => {
    // ✅ Green for passed, red for failed after max attempts
    if (quiz.has_passed) return 'bg-green-500'
    if (quiz.attempts >= 3) return 'bg-red-500'
    if (quiz.attempts > 0) return 'bg-yellow-500'
    return 'bg-blue-500'
}

const getQuizStatusVariant = (quiz) => {
    // ✅ Badge variants for different statuses
    if (quiz.has_passed) return 'default'
    if (quiz.attempts >= 3) return 'destructive'
    if (quiz.attempts > 0) return 'secondary'
    return 'outline'
}

const getActiveButtonText = (quiz) => {
    // ✅ Show appropriate button text for active quizzes
    if (quiz.attempts > 0) return 'Continue Quiz'
    return 'Start Quiz'
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-gradient-to-br from-background to-secondary/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                <!-- Header Section -->
                <div class="text-center lg:text-left">
                    <h1 class="text-4xl font-bold text-foreground mb-2">Available Quizzes</h1>
                    <p class="text-lg text-muted-foreground">Test your knowledge and track your progress</p>
                </div>

                <!-- Stats Overview -->
                <div v-if="quizzes.data && quizzes.data.length" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-primary/10 text-primary">
                                    <FileText class="w-6 h-6" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted-foreground">Total Quizzes</p>
                                    <p class="text-2xl font-bold text-foreground">{{ quizzes.data.length }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-500/10 text-green-600">
                                    <CheckCircle class="w-6 h-6" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted-foreground">Completed</p>
                                    <p class="text-2xl font-bold text-foreground">{{ completedQuizzes }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-6">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-500/10 text-yellow-600">
                                    <Clock class="w-6 h-6" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-muted-foreground">Pending</p>
                                    <p class="text-2xl font-bold text-foreground">{{ pendingQuizzes }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Filters and Search -->
                <Card v-if="quizzes.data && quizzes.data.length">
                    <CardContent class="p-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="relative">
                                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search quizzes..."
                                        class="w-full sm:w-64 pl-9"
                                    />
                                </div>

                                <Select v-model="selectedCourse">
                                    <SelectTrigger class="w-full sm:w-48">
                                        <SelectValue placeholder="All Courses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Courses</SelectItem>
                                        <SelectItem v-for="course in uniqueCourses" :key="course.id" :value="course.id.toString()">
                                            {{ course.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="flex items-center gap-2">
                                <Button
                                    @click="toggleView"
                                    variant="ghost"
                                    size="sm"
                                    :title="viewMode === 'grid' ? 'Switch to list view' : 'Switch to grid view'"
                                >
                                    <List v-if="viewMode === 'grid'" class="w-5 h-5" />
                                    <Grid3X3 v-else class="w-5 h-5" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Quiz Cards -->
                <div v-if="filteredQuizzes.length" class="space-y-6">
                    <div :class="viewMode === 'grid' ? 'grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6' : 'space-y-4'">
                        <Card
                            v-for="quiz in filteredQuizzes"
                            :key="quiz.id"
                            class="group hover:shadow-lg transition-all duration-200 overflow-hidden"
                        >
                            <!-- Quiz Status Indicator -->
                            <div class="flex">
                                <div :class="[
                                    'w-1 shrink-0',
                                    getQuizStatusColorClass(quiz)
                                ]"></div>

                                <CardContent class="flex-1 p-6">
                                    <!-- Quiz Header -->
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1 pr-4">
                                            <h2 class="text-xl font-semibold text-foreground mb-2">
                                                {{ quiz.title }}
                                            </h2>
                                            <p class="text-sm text-muted-foreground mb-3 line-clamp-3">
                                                {{ quiz.description || 'No description available' }}
                                            </p>
                                        </div>
                                        <Badge :variant="getQuizStatusVariant(quiz)" class="shrink-0">
                                            {{ getQuizStatus(quiz) }}
                                        </Badge>
                                    </div>

                                    <!-- Quiz Metrics -->
                                    <div class="grid grid-cols-2 gap-4 mb-4">
                                        <Card class="bg-muted/50">
                                            <CardContent class="p-3">
                                                <p class="text-xs text-muted-foreground mb-1">Course</p>
                                                <p class="text-sm font-medium text-foreground">{{ quiz.course.name }}</p>
                                            </CardContent>
                                        </Card>
                                        <Card class="bg-muted/50">
                                            <CardContent class="p-3">
                                                <p class="text-xs text-muted-foreground mb-1">Total Points</p>
                                                <p class="text-sm font-medium text-foreground">{{ quiz.total_points }}</p>
                                            </CardContent>
                                        </Card>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="mb-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-xs text-muted-foreground">Progress</span>
                                            <span class="text-xs text-foreground">{{ quiz.attempts }}/3 attempts</span>
                                        </div>
                                        <div class="w-full bg-secondary rounded-full h-2">
                                            <div
                                                :class="[
                                                    'h-2 rounded-full transition-all duration-300',
                                                    quiz.has_passed ? 'bg-green-500' : 'bg-gradient-to-r from-primary to-purple-600'
                                                ]"
                                                :style="{ width: quiz.has_passed ? '100%' : `${(quiz.attempts / 3) * 100}%` }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Quiz Details -->
                                    <div class="flex items-center justify-between text-xs text-muted-foreground mb-4">
                                        <span>Pass: {{ quiz.pass_threshold }}%</span>
                                        <span v-if="quiz.time_limit" class="flex items-center">
                                            <Clock class="w-3 h-3 mr-1" />
                                            {{ quiz.time_limit }} min
                                        </span>
                                    </div>

                                    <!-- Action Buttons - UPDATED VERSION -->
                                    <div class="space-y-2">
                                        <!-- Active Quiz Button (Start/Continue) -->
                                        <Button
                                            v-if="canTakeQuiz(quiz)"
                                            asChild
                                            class="w-full"
                                        >
                                            <Link :href="route('quizzes.show', quiz.id)">
                                                <Brain class="w-4 h-4 mr-2" />
                                                {{ getActiveButtonText(quiz) }}
                                            </Link>
                                        </Button>

                                        <!-- View Results Button (For completed/failed quizzes) -->
                                        <Button
                                            v-else-if="isQuizCompleted(quiz)"
                                            asChild
                                            variant="secondary"
                                            class="w-full"
                                        >
                                            <Link :href="route('quiz-attempts.results', quiz.id)">
                                                <BarChart3 class="w-4 h-4 mr-2" />
                                                View Results
                                            </Link>
                                        </Button>

                                        <!-- Fallback disabled button (should not normally show) -->
                                        <Button
                                            v-else
                                            variant="ghost"
                                            disabled
                                            class="w-full"
                                        >
                                            <Brain class="w-4 h-4 mr-2" />
                                            Unavailable
                                        </Button>
                                    </div>
                                </CardContent>
                            </div>
                        </Card>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else-if="!quizzes.data || !quizzes.data.length" class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-muted/50 rounded-full flex items-center justify-center mb-4">
                        <FileText class="w-12 h-12 text-muted-foreground" />
                    </div>
                    <h3 class="text-lg font-medium text-foreground mb-2">No quizzes available</h3>
                    <p class="text-muted-foreground mb-4">There are no quizzes available for your enrolled courses at the moment.</p>
                </div>

                <!-- No Results State -->
                <div v-else class="text-center py-16">
                    <div class="mx-auto w-24 h-24 bg-muted/50 rounded-full flex items-center justify-center mb-4">
                        <Search class="w-12 h-12 text-muted-foreground" />
                    </div>
                    <h3 class="text-lg font-medium text-foreground mb-2">No quizzes found</h3>
                    <p class="text-muted-foreground mb-4">Try adjusting your search or filter criteria.</p>
                    <Button @click="clearFilters">
                        Clear Filters
                    </Button>
                </div>

                <!-- Pagination -->
                <div v-if="quizzes.links && quizzes.data && quizzes.data.length" class="flex justify-center">
                    <pagination :links="quizzes.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
