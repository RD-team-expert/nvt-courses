<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Switch } from '@/components/ui/switch'
import { Checkbox } from '@/components/ui/checkbox'
import { Alert, AlertDescription } from '@/components/ui/alert'
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
  Users,
  BookOpen,
  Search,
  CheckCircle,
  AlertTriangle,
  UserPlus,
  Clock,
  Target,
  Filter,
  X,
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
  department_id: number | null
  department_name: string
  active_assignments: number
}

interface Department {
  id: number
  name: string
}

const props = defineProps<{
  courses: Course[]
  users: User[]
  departments: Department[]
  selectedCourseId?: number
  selectedUserIds?: number[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Course Assignments', href: '/admin/course-assignments' },
  { title: 'Assign Courses', href: '/admin/course-assignments/create' },
]

// Form
const form = useForm({
  course_id: props.selectedCourseId || '',
  user_ids: props.selectedUserIds || [],
  send_notification: true,
})

// ✅ NEW: Filter states
const courseSearch = ref('')
const userSearch = ref('')
const selectedDepartmentId = ref<string | null>(null)
const showOnlyUnassigned = ref(false)

// ✅ NEW: Computed filtered users
const filteredUsers = computed(() => {
  let filtered = props.users

  // Filter by search
  if (userSearch.value) {
    const search = userSearch.value.toLowerCase()
    filtered = filtered.filter(
      (user) =>
        user.name.toLowerCase().includes(search) ||
        user.email.toLowerCase().includes(search) ||
        user.department_name.toLowerCase().includes(search)
    )
  }

  // Filter by department
  if (selectedDepartmentId.value) {
    const deptId = parseInt(selectedDepartmentId.value)
    filtered = filtered.filter((user) => user.department_id === deptId)
  }

  // Filter by unassigned (users who DON'T have this course)
  if (showOnlyUnassigned.value && form.course_id) {
    filtered = filtered.filter((user) => {
      // Check if user already has this course assigned
      const hasAssignment = user.active_assignments > 0 // You can make this more specific
      return !hasAssignment
    })
  }

  return filtered
})

const filteredCourses = computed(() => {
  if (!courseSearch.value) return props.courses
  return props.courses.filter(
    (course) =>
      course.name.toLowerCase().includes(courseSearch.value.toLowerCase()) ||
      course.description.toLowerCase().includes(courseSearch.value.toLowerCase())
  )
})

const selectedCourse = computed(() =>
  props.courses.find((course) => course.id.toString() === form.course_id.toString())
)

const selectedUsersCount = computed(() => form.user_ids.length)

const canSubmit = computed(() => form.course_id && form.user_ids.length > 0)

// ✅ NEW: Active filters count
const activeFiltersCount = computed(() => {
  let count = 0
  if (selectedDepartmentId.value) count++
  if (showOnlyUnassigned.value) count++
  return count
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
    form.user_ids = filteredUsers.value.map((user) => user.id)
  }
}

// ✅ NEW: Clear filters
const clearFilters = () => {
  selectedDepartmentId.value = null
  showOnlyUnassigned.value = false
  userSearch.value = ''
}

const getDifficultyColor = (level: string) => {
  switch (level) {
    case 'beginner':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
    case 'intermediate':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
    case 'advanced':
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
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
        <Button as-child variant="ghost">
          <Link :href="'/admin/course-assignments'">
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
            <CardDescription>Choose the course you want to assign to users</CardDescription>
          </CardHeader>

          <CardContent class="space-y-4">
            <!-- Course Search -->
            <div class="relative">
              <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
              <Input v-model="courseSearch" placeholder="Search courses..." class="pl-10" />
            </div>

            <!-- Course Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              <div
                v-for="course in filteredCourses"
                :key="course.id"
                @click="selectCourse(course.id)"
                class="border rounded-lg p-4 cursor-pointer transition-all hover:shadow-md"
                :class="
                  form.course_id.toString() === course.id.toString()
                    ? 'border-primary bg-primary/5'
                    : 'border-border hover:border-primary/50'
                "
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

                <p class="text-xs text-muted-foreground mb-3 line-clamp-2">{{ course.description }}</p>

                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <Badge :class="getDifficultyColor(course.difficulty_level)" class="text-xs">
                      {{ course.difficulty_level }}
                    </Badge>
                    <div class="flex items-center gap-1 text-xs text-muted-foreground">
                      <Clock class="w-3 h-3" />
                      {{ course.estimated_duration }} min
                    </div>
                  </div>

                  <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>{{ course.modules_count }} modules</span>
                    <span>{{ course.current_assignments }} assigned</span>
                  </div>
                </div>
              </div>
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
                <CardDescription>Choose which users to assign this course to</CardDescription>
              </div>

              <Button type="button" variant="outline" size="sm" @click="toggleAllUsers">
                {{ form.user_ids.length === filteredUsers.length ? 'Deselect All' : 'Select All' }}
              </Button>
            </div>
          </CardHeader>

          <CardContent class="space-y-4">
            <!-- ✅ NEW: Filters Section -->
            <div class="space-y-3 p-4 bg-muted/50 rounded-lg">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Filter class="h-4 w-4 text-muted-foreground" />
                  <span class="font-medium text-sm">Filters</span>
                  <Badge v-if="activeFiltersCount > 0" variant="secondary" class="h-5">
                    {{ activeFiltersCount }}
                  </Badge>
                </div>
                <Button
                  v-if="activeFiltersCount > 0"
                  type="button"
                  variant="ghost"
                  size="sm"
                  @click="clearFilters"
                >
                  <X class="h-3 w-3 mr-1" />
                  Clear
                </Button>
              </div>

              <div class="grid gap-3 md:grid-cols-2">
                <!-- Department Filter -->
                <div class="space-y-2">
                  <Label>Filter by Department</Label>
                  <Select v-model="selectedDepartmentId">
                    <SelectTrigger>
                      <SelectValue placeholder="All Departments" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem :value="null">All Departments</SelectItem>
                      <SelectItem v-for="dept in departments" :key="dept.id" :value="dept.id.toString()">
                        {{ dept.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <!-- Show Unassigned Only -->
                <div class="flex items-center space-x-2">
                  <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
              <Input v-model="userSearch" placeholder="Search users by name, email, or department..." class="pl-10" />
                </div>
              </div>
            </div>

            <!-- User Search -->
            <div class="relative">
              
            </div>

            <!-- User List -->
            <div class="space-y-2 max-h-96 overflow-y-auto">
              <div
                v-for="user in filteredUsers"
                :key="user.id"
                class="flex items-center space-x-3 p-3 border rounded-lg hover:bg-accent/50 transition-colors"
              >
                <Checkbox :checked="form.user_ids.includes(user.id)" @update:checked="toggleUser(user.id)" />

                <div class="flex-1">
                  <div class="font-medium">{{ user.name }}</div>
                  <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                  <div class="text-xs text-muted-foreground mt-1">
                    <Badge variant="outline" class="mr-2">{{ user.department_name }}</Badge>
                    {{ user.active_assignments }} active assignments
                  </div>
                </div>
              </div>

              <!-- No Results -->
              <div v-if="filteredUsers.length === 0" class="text-center py-8 text-muted-foreground">
                <Users class="h-12 w-12 mx-auto mb-2 opacity-50" />
                <p>No users found matching your filters</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Assignment Settings -->
        <Card>
          <CardHeader>
            <CardTitle>Assignment Settings</CardTitle>
            <CardDescription>Configure how the assignment will be handled</CardDescription>
          </CardHeader>

          <CardContent>
            <div class="flex items-center justify-between">
              <div class="space-y-1">
                <Label>Email Notifications</Label>
                <div class="text-sm text-muted-foreground">
                  Send email notifications to users about their new course assignment
                </div>
              </div>
              <Switch v-model:checked="form.send_notification" />
            </div>
          </CardContent>
        </Card>

        <!-- Submit Actions -->
        <div class="flex justify-between items-center py-6">
          <Button as-child variant="outline">
            <Link :href="'/admin/course-assignments'">Cancel</Link>
          </Button>

          <Button type="submit" :disabled="form.processing || !canSubmit" class="min-w-48">
            <UserPlus class="h-4 w-4 mr-2" />
            {{ form.processing ? 'Assigning Courses...' : `Assign Course to ${selectedUsersCount} Users` }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
