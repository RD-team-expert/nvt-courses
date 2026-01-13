<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { ref } from 'vue'

import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

import { ArrowLeft, CheckCircle, XCircle, Users, Target, BarChart3, RotateCcw } from 'lucide-vue-next'

interface User {
    id: number
    name: string
    email: string
}

interface Attempt {
    id: number
    user: User
    attempt_number: number
    score: number
    total_score: number
    score_percentage: number
    passed: boolean
    completed_at: string | null
    created_at: string
}

interface Quiz {
    id: number
    title: string
    total_points: number
    pass_threshold: number
    max_attempts: number
}

interface Module {
    id: number
    name: string
}

interface Course {
    id: number
    name: string
}

interface PaginatedAttempts {
    data: Attempt[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
    quiz: Quiz
    module: Module
    course: Course
    attempts: PaginatedAttempts
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Course Online", href: "/admin/course-online" },
    { title: props.course.name, href: `/admin/course-online/${props.course.id}` },
    { title: props.quiz.title, href: '#' },
    { title: "Attempts", href: '#' },
]

const stats = {
    total: props.attempts.total,
    passed: props.attempts.data.filter(a => a.passed).length,
    avgScore: props.attempts.data.length > 0 
        ? (props.attempts.data.reduce((sum, a) => sum + a.score_percentage, 0) / props.attempts.data.length).toFixed(1)
        : 0
}

const resettingUserId = ref<number | null>(null)

const resetUserAttempts = (userId: number, userName: string) => {
    if (!confirm(`Are you sure you want to reset ALL quiz attempts for ${userName}?\n\nThis will:\n- Delete all their attempts\n- Delete all their answers\n- Allow them to retake the quiz from scratch\n\nThis action cannot be undone!`)) {
        return
    }

    resettingUserId.value = userId

    router.post(
        route('admin.module-quiz.reset-attempts', {
            courseOnline: props.course.id,
            courseModule: props.module.id,
            quiz: props.quiz.id
        }),
        { user_id: userId },
        {
            preserveScroll: true,
            onSuccess: () => {
                resettingUserId.value = null
            },
            onError: () => {
                resettingUserId.value = null
                alert('Failed to reset attempts. Please try again.')
            }
        }
    )
}
</script>

<template>
    <Head :title="`Attempts - ${quiz.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button as-child variant="ghost" size="sm">
                    <Link :href="route('admin.module-quiz.show', { courseOnline: course.id, courseModule: module.id, quiz: quiz.id })">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Quiz
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold">Quiz Attempts</h1>
                    <p class="text-muted-foreground">{{ quiz.title }}</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-3">
                            <Users class="h-8 w-8 text-blue-500" />
                            <div>
                                <p class="text-2xl font-bold">{{ stats.total }}</p>
                                <p class="text-sm text-muted-foreground">Total Attempts</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-3">
                            <CheckCircle class="h-8 w-8 text-green-500" />
                            <div>
                                <p class="text-2xl font-bold">{{ stats.passed }}</p>
                                <p class="text-sm text-muted-foreground">Passed</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-3">
                            <BarChart3 class="h-8 w-8 text-purple-500" />
                            <div>
                                <p class="text-2xl font-bold">{{ stats.avgScore }}%</p>
                                <p class="text-sm text-muted-foreground">Avg Score</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-3">
                            <Target class="h-8 w-8 text-orange-500" />
                            <div>
                                <p class="text-2xl font-bold">{{ quiz.pass_threshold }}%</p>
                                <p class="text-sm text-muted-foreground">Pass Threshold</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Attempts Table -->
            <Card>
                <CardHeader>
                    <CardTitle>All Attempts</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table v-if="attempts.data.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>User</TableHead>
                                <TableHead>Attempt</TableHead>
                                <TableHead>Score</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Completed</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="attempt in attempts.data" :key="attempt.id">
                                <TableCell>
                                    <div>
                                        <p class="font-medium">{{ attempt.user.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ attempt.user.email }}</p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline">#{{ attempt.attempt_number }}</Badge>
                                </TableCell>
                                <TableCell>
                                    <div>
                                        <p class="font-medium">{{ attempt.total_score }} / {{ quiz.total_points }}</p>
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
                                    {{ attempt.completed_at || 'In Progress' }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex gap-2">
                                        <Button as-child variant="outline" size="sm">
                                            <Link :href="route('admin.module-quiz.attempts.show', { 
                                                courseOnline: course.id, 
                                                courseModule: module.id, 
                                                quiz: quiz.id,
                                                attempt: attempt.id 
                                            })">
                                                View Answers
                                            </Link>
                                        </Button>
                                        <Button 
                                            variant="destructive" 
                                            size="sm"
                                            @click="resetUserAttempts(attempt.user.id, attempt.user.name)"
                                            :disabled="resettingUserId === attempt.user.id"
                                        >
                                            <RotateCcw class="h-4 w-4 mr-1" />
                                            {{ resettingUserId === attempt.user.id ? 'Resetting...' : 'Reset Attempts' }}
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-else class="text-center py-12 text-muted-foreground">
                        No attempts yet
                    </div>

                    <!-- Pagination -->
                    <div v-if="attempts.last_page > 1" class="flex justify-center gap-2 mt-6">
                        <Button
                            v-for="link in attempts.links"
                            :key="link.label"
                            as-child
                            :variant="link.active ? 'default' : 'outline'"
                            size="sm"
                            :disabled="!link.url"
                        >
                            <Link v-if="link.url" :href="link.url" v-html="link.label" />
                            <span v-else v-html="link.label" />
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
