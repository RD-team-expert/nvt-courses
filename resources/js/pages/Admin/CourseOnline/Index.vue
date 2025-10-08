<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Progress } from '@/components/ui/progress'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'

// Icons
import {
    Plus,
    BookOpen,
    Users,
    TrendingUp,
    Award,
    Search,
    Filter,
    MoreHorizontal,
    Eye,
    Edit,
    Settings,
    BarChart3,
    Clock,
    CheckCircle,
    AlertCircle,
    RefreshCw,
    UserCheck
} from 'lucide-vue-next'

interface Course {
    id: number
    name: string
    description: string
    image_path: string | null
    thumbnails: {
        small: string
        medium: string
        large: string
    } | null
    difficulty_level: 'beginner' | 'intermediate' | 'advanced'
    estimated_duration: number
    is_active: boolean
    creator: {
        id: number
        name: string
    } | null
    modules_count: number
    assignments_count: number
    completion_rate: number
    created_at: string
}

interface CoursesData {
    data: Course[]
    links: any
    meta: {
        total: number
        per_page: number
        current_page: number
        last_page: number
        from: number
        to: number
    }
}

const props = defineProps<{
    courses: CoursesData
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Online Courses', href: '#' }
]

// State
const searchQuery = ref('')
const statusFilter = ref('all')
const levelFilter = ref('all')
const isRefreshing = ref(false)

// Computed properties for stats
const activeCourses = computed(() =>
    props.courses.data.filter(course => course.is_active).length
)

const totalEnrollments = computed(() =>
    props.courses.data.reduce((sum, course) => sum + (course.assignments_count || 0), 0)
)

const averageCompletion = computed(() => {
    const completions = props.courses.data.map(course => course.completion_rate || 0)
    const total = completions.reduce((sum, rate) => sum + rate, 0)
    return completions.length ? Math.round(total / completions.length) : 0
})

const filteredCourses = computed(() => {
    let filtered = props.courses.data

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(course =>
            course.name.toLowerCase().includes(query) ||
            course.description?.toLowerCase().includes(query)
        )
    }

    // Status filter
    if (statusFilter.value !== 'all') {
        const isActive = statusFilter.value === 'active'
        filtered = filtered.filter(course => course.is_active === isActive)
    }

    // Level filter
    if (levelFilter.value !== 'all') {
        filtered = filtered.filter(course => course.difficulty_level === levelFilter.value)
    }

    return filtered
})

// Methods
const truncateText = (text: string | null, maxLength: number): string => {
    if (!text) return ''
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
}

const getDifficultyColor = (level: string): string => {
    switch (level) {
        case 'beginner': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
        case 'intermediate': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
        case 'advanced': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
    }
}

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    })
}

const formatDuration = (minutes: number): string => {
    if (minutes < 60) return `${minutes}m`
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`
}

const refreshData = async () => {
    isRefreshing.value = true
    try {
        router.reload()
    } finally {
        setTimeout(() => {
            isRefreshing.value = false
        }, 1000)
    }
}

const toggleCourseStatus = async (courseId: number) => {
    router.patch(route('admin.course-online.toggle-active', courseId))
}

const clearFilters = () => {
    searchQuery.value = ''
    statusFilter.value = 'all'
    levelFilter.value = 'all'
}
</script>

<template>
    <Head title="Online Courses Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Online Courses</h1>
                    <p class="text-muted-foreground">Manage your online courses and track student progress</p>
                </div>
                <!-- ✅ UPDATED: Added Course Assignments button -->
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="refreshData" :disabled="isRefreshing">
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': isRefreshing }" />
                        Refresh
                    </Button>
                    <Button asChild variant="outline">
                        <Link href="/admin/course-assignments">
                            <UserCheck class="mr-2 h-4 w-4" />
                            Course Assignments
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link :href="route('admin.course-online.create')">
                            <Plus class="mr-2 h-4 w-4" />
                            Create Course
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Courses</CardTitle>
                        <BookOpen class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ courses.meta?.total || courses.data.length }}</div>
                        <p class="text-xs text-muted-foreground">
                            +{{ courses.data.filter(c => new Date(c.created_at) > new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)).length }} this month
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Courses</CardTitle>
                        <CheckCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ activeCourses }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ Math.round((activeCourses / courses.data.length) * 100) || 0 }}% of total
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Enrollments</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ totalEnrollments }}</div>
                        <p class="text-xs text-muted-foreground">
                            Avg {{ Math.round(totalEnrollments / (courses.data.length || 1)) }} per course
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Avg. Completion</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ averageCompletion }}%</div>
                        <Progress :value="averageCompletion" class="mt-2" />
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Filter class="h-5 w-5" />
                        Filters
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-4">
                        <!-- Search -->
                        <div class="flex-1 relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                placeholder="Search courses..."
                                class="pl-10"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div class="w-40">
                            <Select v-model="statusFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Level Filter -->
                        <div class="w-40">
                            <Select v-model="levelFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="Level" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Levels</SelectItem>
                                    <SelectItem value="beginner">Beginner</SelectItem>
                                    <SelectItem value="intermediate">Intermediate</SelectItem>
                                    <SelectItem value="advanced">Advanced</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Clear Filters -->
                        <Button variant="outline" @click="clearFilters">
                            Clear
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Courses Content -->
            <Tabs default-value="grid" class="space-y-4">
                <!-- ✅ UPDATED: Removed Export button section -->
                <div class="flex items-center justify-between">
                    <TabsList>
                        <TabsTrigger value="grid">Grid View</TabsTrigger>
                        <TabsTrigger value="table">Table View</TabsTrigger>
                    </TabsList>
                </div>

                <!-- Grid View -->
                <TabsContent value="grid" class="space-y-4">
                    <div v-if="filteredCourses.length === 0" class="flex flex-col items-center justify-center p-12 border-2 border-dashed border-input rounded-lg">
                        <BookOpen class="h-16 w-16 text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium mb-2">No courses found</h3>
                        <p class="text-muted-foreground mb-4">
                            {{ searchQuery || statusFilter !== 'all' || levelFilter !== 'all'
                            ? 'Try adjusting your filters'
                            : 'Get started by creating your first online course' }}
                        </p>
                        <Button asChild v-if="!searchQuery && statusFilter === 'all' && levelFilter === 'all'">
                            <Link :href="route('admin.course-online.create')">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Course
                            </Link>
                        </Button>
                    </div>

                    <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <Card
                            v-for="course in filteredCourses"
                            :key="course.id"
                            class="group hover:shadow-lg transition-shadow duration-200"
                        >
                            <CardHeader class="pb-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <Avatar class="h-12 w-12">
                                            <AvatarImage
                                                v-if="course.image_path"
                                                :src="course.thumbnails?.small || course.image_path"
                                                :alt="course.name"
                                            />
                                            <AvatarFallback>
                                                <BookOpen class="h-6 w-6" />
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="flex-1">
                                            <CardTitle class="text-lg line-clamp-1">{{ course.name }}</CardTitle>
                                            <CardDescription class="line-clamp-2">
                                                {{ truncateText(course.description, 80) }}
                                            </CardDescription>
                                        </div>
                                    </div>

                                    <!-- Actions Dropdown -->
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="sm">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link :href="route('admin.course-online.show', course.id)">
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    View Details
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild>
                                                <Link :href="route('admin.course-modules.index', course.id)">
                                                    <Settings class="mr-2 h-4 w-4" />
                                                    Manage Modules
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem asChild>
                                                <Link :href="route('admin.course-online.edit', course.id)">
                                                    <Edit class="mr-2 h-4 w-4" />
                                                    Edit Course
                                                </Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </CardHeader>

                            <CardContent class="space-y-4">
                                <!-- Course Badges -->
                                <div class="flex items-center gap-2">
                                    <Badge :class="getDifficultyColor(course.difficulty_level)">
                                        {{ course.difficulty_level }}
                                    </Badge>
                                    <Badge :variant="course.is_active ? 'default' : 'secondary'">
                                        {{ course.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </div>

                                <!-- Course Stats -->
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div class="text-lg font-bold">{{ course.modules_count }}</div>
                                        <div class="text-xs text-muted-foreground">Modules</div>
                                    </div>
                                    <div>
                                        <div class="text-lg font-bold">{{ course.assignments_count }}</div>
                                        <div class="text-xs text-muted-foreground">Enrolled</div>
                                    </div>
                                    <div>
                                        <div class="text-lg font-bold">{{ Math.round(course.completion_rate) }}%</div>
                                        <div class="text-xs text-muted-foreground">Completion</div>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span>Completion Rate</span>
                                        <span>{{ Math.round(course.completion_rate) }}%</span>
                                    </div>
                                    <Progress :value="course.completion_rate" />
                                </div>

                                <!-- Course Info -->
                                <div class="flex items-center justify-between text-sm text-muted-foreground">
                                    <div class="flex items-center gap-1">
                                        <Clock class="h-3 w-3" />
                                        {{ formatDuration(course.estimated_duration) }}
                                    </div>
                                    <div>Created {{ formatDate(course.created_at) }}</div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex gap-2 pt-2">
                                    <Button asChild size="sm" class="flex-1">
                                        <Link :href="route('admin.course-online.show', course.id)">
                                            <Eye class="mr-2 h-4 w-4" />
                                            View
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline" size="sm" class="flex-1">
                                        <Link :href="route('admin.course-modules.index', course.id)">
                                            <Settings class="mr-2 h-4 w-4" />
                                            Modules
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Table View -->
                <TabsContent value="table">
                    <Card>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Course</TableHead>
                                    <TableHead>Level</TableHead>
                                    <TableHead>Modules</TableHead>
                                    <TableHead>Enrolled</TableHead>
                                    <TableHead>Completion</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="course in filteredCourses" :key="course.id">
                                    <TableCell>
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-10 w-10">
                                                <AvatarImage
                                                    v-if="course.image_path"
                                                    :src="course.thumbnails?.small || course.image_path"
                                                    :alt="course.name"
                                                />
                                                <AvatarFallback>
                                                    <BookOpen class="h-5 w-5" />
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium">{{ course.name }}</div>
                                                <div class="text-sm text-muted-foreground">
                                                    {{ truncateText(course.description, 40) }}
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getDifficultyColor(course.difficulty_level)">
                                            {{ course.difficulty_level }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ course.modules_count }}</TableCell>
                                    <TableCell>{{ course.assignments_count }}</TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Progress :value="course.completion_rate" class="w-16" />
                                            <span class="text-sm">{{ Math.round(course.completion_rate) }}%</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="course.is_active ? 'default' : 'secondary'">
                                            {{ course.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Button asChild size="sm" variant="outline">
                                                <Link :href="route('admin.course-online.show', course.id)">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button asChild size="sm" variant="outline">
                                                <Link :href="route('admin.course-modules.index', course.id)">
                                                    <Settings class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button asChild size="sm" variant="outline">
                                                <Link :href="route('admin.course-online.edit', course.id)">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </Card>
                </TabsContent>
            </Tabs>

            <!-- Pagination -->
            <div v-if="courses.meta && courses.meta.last_page > 1" class="flex items-center justify-center space-x-2">
                <Button
                    v-for="link in courses.links"
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    size="sm"
                    @click="link.url && router.get(link.url)"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
