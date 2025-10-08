<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import { Checkbox } from '@/components/ui/checkbox'
import { Alert, AlertDescription } from '@/components/ui/alert'

// Icons
import {
    ArrowLeft,
    Users,
    BookOpen,
    Search,
    CheckCircle,
    AlertTriangle,
    UserPlus,
    Clock,
    Target
} from 'lucide-vue-next'

interface Course {
    id: number
    name: string
    description: string
    difficulty_level: string
    estimated_duration: number
    modules_count: number
    current_assignments: number
}

interface User {
    id: number
    name: string
    email: string
    active_assignments: number
}

const props = defineProps<{
    courses: Course[]
    users: User[]
    selectedCourseId?: number
    selectedUserIds?: number[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Course Assignments', href: '/admin/course-assignments' },
    { title: 'Assign Courses', href: '#' }
]

// Form
const form = useForm({
    course_id: props.selectedCourseId || '',
    user_ids: props.selectedUserIds || [],
    send_notification: true
})

// Search
const courseSearch = ref('')
const userSearch = ref('')

// Computed
const filteredCourses = computed(() => {
    if (!courseSearch.value) return props.courses
    return props.courses.filter(course =>
        course.name.toLowerCase().includes(courseSearch.value.toLowerCase()) ||
        course.description.toLowerCase().includes(courseSearch.value.toLowerCase())
    )
})

const filteredUsers = computed(() => {
    if (!userSearch.value) return props.users
    return props.users.filter(user =>
        user.name.toLowerCase().includes(userSearch.value.toLowerCase()) ||
        user.email.toLowerCase().includes(userSearch.value.toLowerCase())
    )
})

const selectedCourse = computed(() => {
    return props.courses.find(course => course.id.toString() === form.course_id.toString())
})

const selectedUsersCount = computed(() => form.user_ids.length)

const canSubmit = computed(() => {
    return form.course_id && form.user_ids.length > 0
})

// Methods
const selectCourse = (courseId: number) => {
    form.course_id = courseId.toString()
}

const toggleUser = (userId: number) => {
    const index = form.user_ids.indexOf(userId)
    if (index > -1) {
        form.user_ids.splice(index, 1)
    } else {
        form.user_ids.push(userId)
    }
}

const toggleAllUsers = () => {
    if (form.user_ids.length === filteredUsers.value.length) {
        form.user_ids = []
    } else {
        form.user_ids = filteredUsers.value.map(user => user.id)
    }
}

const getDifficultyColor = (level: string) => {
    switch (level) {
        case 'beginner': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
        case 'intermediate': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
        case 'advanced': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    }
}

const submit = () => {
    form.post('/admin/course-assignments')
}
</script>

<template>
    <Head title="Assign Courses" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Button asChild variant="ghost">
                    <Link href="/admin/course-assignments">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back to Assignments
                    </Link>
                </Button>
                <div>
                    <h1 class="text-3xl font-bold">Assign Courses to Users</h1>
                    <p class="text-muted-foreground">Select a course and assign it to multiple users at once</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Course Selection -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BookOpen class="h-5 w-5 text-primary" />
                            Select Course
                        </CardTitle>
                        <CardDescription>
                            Choose the course you want to assign to users
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Course Search -->
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="courseSearch"
                                placeholder="Search courses..."
                                class="pl-10"
                            />
                        </div>

                        <!-- Course Grid -->
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="course in filteredCourses"
                                :key="course.id"
                                @click="selectCourse(course.id)"
                                class="border rounded-lg p-4 cursor-pointer transition-all hover:shadow-md"
                                :class="form.course_id.toString() === course.id.toString()
                                    ? 'border-primary bg-primary/5'
                                    : 'border-border hover:border-primary/50'"
                            >
                                <div class="flex items-start justify-between mb-3">
                                    <h3 class="font-semibold text-sm line-clamp-2">{{ course.name }}</h3>
                                    <div
                                        v-if="form.course_id.toString() === course.id.toString()"
                                        class="w-5 h-5 rounded-full bg-primary text-primary-foreground flex items-center justify-center flex-shrink-0 ml-2"
                                    >
                                        <CheckCircle class="w-3 h-3" />
                                    </div>
                                </div>

                                <p class="text-xs text-muted-foreground mb-3 line-clamp-2">
                                    {{ course.description }}
                                </p>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <Badge :class="getDifficultyColor(course.difficulty_level)" class="text-xs">
                                            {{ course.difficulty_level }}
                                        </Badge>
                                        <div class="flex items-center gap-1 text-xs text-muted-foreground">
                                            <Clock class="w-3 h-3" />
                                            {{ course.estimated_duration }}min
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-muted-foreground">
                                        <span>{{ course.modules_count }} modules</span>
                                        <span>{{ course.current_assignments }} assigned</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Course Summary -->
                        <div v-if="selectedCourse" class="p-4 bg-primary/5 border border-primary/20 rounded-lg">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center flex-shrink-0">
                                    <CheckCircle class="w-4 h-4" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold">{{ selectedCourse.name }}</h4>
                                    <p class="text-sm text-muted-foreground mt-1">{{ selectedCourse.description }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-sm">
                                        <span>{{ selectedCourse.modules_count }} modules</span>
                                        <span>{{ selectedCourse.estimated_duration }} minutes</span>
                                        <Badge :class="getDifficultyColor(selectedCourse.difficulty_level)">
                                            {{ selectedCourse.difficulty_level }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.errors.course_id" class="text-sm text-destructive">
                            {{ form.errors.course_id }}
                        </div>
                    </CardContent>
                </Card>

                <!-- User Selection -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Users class="h-5 w-5 text-primary" />
                                    Select Users
                                    <Badge v-if="selectedUsersCount > 0" variant="secondary">
                                        {{ selectedUsersCount }} selected
                                    </Badge>
                                </CardTitle>
                                <CardDescription>
                                    Choose which users to assign this course to
                                </CardDescription>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="toggleAllUsers"
                            >
                                {{ form.user_ids.length === filteredUsers.length ? 'Deselect All' : 'Select All' }}
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- User Search -->
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="userSearch"
                                placeholder="Search users by name or email..."
                                class="pl-10"
                            />
                        </div>

                        <!-- User List -->
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            <div
                                v-for="user in filteredUsers"
                                :key="user.id"
                                class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-accent/50 transition-colors"
                            >
                                <Checkbox
                                    :checked="form.user_ids.includes(user.id)"
                                    @update:checked="toggleUser(user.id)"
                                />
                                <div class="flex-1">
                                    <div class="font-medium">{{ user.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ user.active_assignments }} active assignments
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.errors.user_ids" class="text-sm text-destructive">
                            {{ form.errors.user_ids }}
                        </div>
                    </CardContent>
                </Card>

                <!-- Assignment Settings -->
                <Card>
                    <CardHeader>
                        <CardTitle>Assignment Settings</CardTitle>
                        <CardDescription>
                            Configure how the assignment will be handled
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <Label>Email Notifications</Label>
                                <div class="text-sm text-muted-foreground">
                                    Send email notifications to users about their new course assignment
                                </div>
                            </div>
                            <Switch
                                :checked="form.send_notification"
                                @update:checked="form.send_notification = $event"
                            />
                        </div>
                    </CardContent>
                </Card>

                <!-- Summary -->
                <Card v-if="selectedCourse && selectedUsersCount > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Target class="h-5 w-5 text-primary" />
                            Assignment Summary
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label>Course</Label>
                                    <div class="font-medium">{{ selectedCourse.name }}</div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ selectedCourse.modules_count }} modules • {{ selectedCourse.estimated_duration }} minutes
                                    </div>
                                </div>
                                <div>
                                    <Label>Users</Label>
                                    <div class="font-medium">{{ selectedUsersCount }} users selected</div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ form.send_notification ? 'With email notifications' : 'No email notifications' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Guidelines -->
                <Alert>
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                        <strong>Note:</strong> Users will be able to access the assigned course immediately.
                        If email notifications are enabled, they will receive an email with course details and access instructions.
                    </AlertDescription>
                </Alert>

                <!-- Submit Actions -->
                <div class="flex justify-between items-center py-6">
                    <Button asChild variant="outline">
                        <Link href="/admin/course-assignments">
                            Cancel
                        </Link>
                    </Button>

                    <Button
                        type="submit"
                        :disabled="form.processing || !canSubmit"
                        class="min-w-48"
                    >
                        <UserPlus class="h-4 w-4 mr-2" />
                        {{ form.processing ? 'Assigning Courses...' : `Assign Course to ${selectedUsersCount} Users` }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
