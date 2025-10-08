<template>
    <Head title="Video Library" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 bg-background text-foreground">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold flex items-center gap-3 text-foreground">
                        <PlaySquare class="h-8 w-8 text-blue-600 dark:text-blue-500" />
                        Video Library
                    </h1>
                    <p class="text-muted-foreground text-lg">
                        Discover and learn from our comprehensive video courses
                    </p>
                </div>
            </div>

            <!-- Filters and Search -->
            <Card class="bg-card border-border">
                <CardContent class="p-6">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <!-- Search -->
                        <div class="flex-1">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search video courses..."
                                    class="pl-10"
                                />
                            </div>
                        </div>

                        <!-- Category Filter -->
                        <Select v-model="selectedCategory">
                            <SelectTrigger class="w-full lg:w-48">
                                <SelectValue placeholder="All Categories" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Categories</SelectItem>
                                <SelectItem
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id.toString()"
                                >
                                    {{ category.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Status Filter -->
                        <Select v-model="selectedStatus">
                            <SelectTrigger class="w-full lg:w-48">
                                <SelectValue placeholder="All Videos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Videos</SelectItem>
                                <SelectItem value="not_started">Not Started</SelectItem>
                                <SelectItem value="in_progress">In Progress</SelectItem>
                                <SelectItem value="completed">Completed</SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Sort -->
                        <Select v-model="selectedSort">
                            <SelectTrigger class="w-full lg:w-48">
                                <SelectValue placeholder="Sort by..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="latest">Latest</SelectItem>
                                <SelectItem value="name">Name</SelectItem>
                                <SelectItem value="duration">Duration</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <!-- Progress Summary -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card class="border-0 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/50 border border-blue-200/50 dark:border-blue-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-500 dark:bg-blue-600 rounded-lg">
                                <PlaySquare class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                    {{ filteredVideos.length }}
                                </div>
                                <div class="text-sm text-blue-700 dark:text-blue-300">Available Videos</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-950/50 dark:to-yellow-900/50 border border-yellow-200/50 dark:border-yellow-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-yellow-500 dark:bg-yellow-600 rounded-lg">
                                <Play class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-100">
                                    {{ inProgressCount }}
                                </div>
                                <div class="text-sm text-yellow-700 dark:text-yellow-300">In Progress</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/50 border border-green-200/50 dark:border-green-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-500 dark:bg-green-600 rounded-lg">
                                <CheckCircle class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                                    {{ completedCount }}
                                </div>
                                <div class="text-sm text-green-700 dark:text-green-300">Completed</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/50 dark:to-purple-900/50 border border-purple-200/50 dark:border-purple-800/50">
                    <CardContent class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-purple-500 dark:bg-purple-600 rounded-lg">
                                <TrendingUp class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                                    {{ averageProgress }}%
                                </div>
                                <div class="text-sm text-purple-700 dark:text-purple-300">Avg Progress</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Video Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <Card
                    v-for="video in filteredVideos"
                    :key="video.id"
                    class="group hover:shadow-xl transition-all duration-300 bg-card border-border cursor-pointer"
                    @click="playVideo(video)"
                >
                    <!-- Thumbnail -->
                    <div class="relative aspect-video bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-950/50 dark:to-purple-950/50 rounded-t-lg overflow-hidden">
                        <img
                            v-if="video.thumbnail_url"
                            :src="video.thumbnail_url"
                            :alt="video.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            @error="handleImageError"
                        />
                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center"
                        >
                            <PlaySquare class="h-16 w-16 text-blue-500 dark:text-blue-400 opacity-50" />
                        </div>

                        <!-- Play Overlay -->
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <div class="bg-white/90 dark:bg-gray-900/90 rounded-full p-4">
                                <Play class="h-8 w-8 text-blue-600 dark:text-blue-400 fill-current" />
                            </div>
                        </div>

                        <!-- Duration Badge -->
                        <div class="absolute top-2 right-2 bg-black/70 text-white px-2 py-1 rounded text-sm font-medium">
                            {{ video.formatted_duration }}
                        </div>

                        <!-- Progress Bar -->
                        <div v-if="video.user_progress" class="absolute bottom-0 left-0 right-0 bg-black/40 h-2">
                            <div
                                class="h-full bg-blue-500 transition-all duration-300"
                                :style="{ width: `${video.user_progress.completion_percentage}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Content -->
                    <CardContent class="p-4">
                        <div class="space-y-2">
                            <!-- Title -->
                            <h3 class="font-semibold text-lg line-clamp-2 text-card-foreground group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                {{ video.name }}
                            </h3>

                            <!-- Description -->
                            <p class="text-sm text-muted-foreground line-clamp-2">
                                {{ video.description || 'No description available' }}
                            </p>

                            <!-- Category & Status -->
                            <div class="flex items-center justify-between">
                                <Badge v-if="video.category" variant="outline" class="bg-background border-border text-foreground">
                                    {{ video.category.name }}
                                </Badge>
                                <span v-else class="text-xs text-muted-foreground">No category</span>

                                <!-- Status Badge -->
                                <Badge :variant="getStatusVariant(video.status)" class="bg-background border-border">
                                    {{ getStatusLabel(video.status) }}
                                </Badge>
                            </div>

                            <!-- Progress Info -->
                            <div v-if="video.user_progress" class="text-xs text-muted-foreground">
                                {{ Math.round(video.user_progress.completion_percentage) }}% completed
                                <span v-if="video.user_progress.last_accessed_at">
                  • Last watched {{ formatRelativeTime(video.user_progress.last_accessed_at) }}
                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-if="filteredVideos.length === 0" class="text-center py-16">
                <PlaySquare class="h-24 w-24 mx-auto text-muted-foreground mb-6" />
                <h3 class="text-xl font-semibold text-foreground mb-2">No videos found</h3>
                <p class="text-muted-foreground mb-4">
                    {{ hasFilters ? 'Try adjusting your filters to see more videos' : 'No video courses are available yet' }}
                </p>
                <Button v-if="hasFilters" @click="clearFilters" variant="outline">
                    Clear Filters
                </Button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

// Icons
import {
    PlaySquare,
    Play,
    Search,
    CheckCircle,
    TrendingUp,
    Clock
} from 'lucide-vue-next'

interface Video {
    id: number
    name: string
    description?: string
    duration: number
    formatted_duration: string
    thumbnail_url?: string
    category?: {
        id: number
        name: string
    }
    user_progress?: {
        current_time: number
        formatted_current_time: string
        completion_percentage: number
        is_completed: boolean
        last_accessed_at?: string
    }
    status: 'not_started' | 'in_progress' | 'completed'
}

interface VideoCategory {
    id: number
    name: string
}

interface Filters {
    search?: string
    category_id?: string
    status?: string
    sort?: string
}

const props = defineProps<{
    videos: Video[]
    categories: VideoCategory[]
    filters: Filters
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Video Library', href: '/videos' },
]

// Reactive filters
const searchQuery = ref(props.filters.search || '')
const selectedCategory = ref(props.filters.category_id || '')
const selectedStatus = ref(props.filters.status || '')
const selectedSort = ref(props.filters.sort || 'latest')

// Computed properties
const filteredVideos = computed(() => {
    let filtered = [...props.videos]

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(video =>
            video.name.toLowerCase().includes(query) ||
            (video.description && video.description.toLowerCase().includes(query))
        )
    }

    // Category filter
    if (selectedCategory.value) {
        filtered = filtered.filter(video =>
            video.category?.id.toString() === selectedCategory.value
        )
    }

    // Status filter
    if (selectedStatus.value) {
        filtered = filtered.filter(video => video.status === selectedStatus.value)
    }

    // Sorting
    switch (selectedSort.value) {
        case 'name':
            filtered.sort((a, b) => a.name.localeCompare(b.name))
            break
        case 'duration':
            filtered.sort((a, b) => (a.duration || 0) - (b.duration || 0))
            break
        case 'latest':
        default:
            // Already sorted by latest from backend
            break
    }

    return filtered
})

const inProgressCount = computed(() =>
    props.videos.filter(v => v.status === 'in_progress').length
)

const completedCount = computed(() =>
    props.videos.filter(v => v.status === 'completed').length
)

const averageProgress = computed(() => {
    const videosWithProgress = props.videos.filter(v => v.user_progress)
    if (videosWithProgress.length === 0) return 0

    const totalProgress = videosWithProgress.reduce((sum, v) =>
        sum + (v.user_progress?.completion_percentage || 0), 0
    )

    return Math.round(totalProgress / videosWithProgress.length)
})

const hasFilters = computed(() =>
    searchQuery.value || selectedCategory.value || selectedStatus.value
)

// Watch for filter changes and update URL
let timeout: NodeJS.Timeout | null = null

watch([searchQuery, selectedCategory, selectedStatus, selectedSort], () => {
    if (timeout) {
        clearTimeout(timeout)
    }

    timeout = setTimeout(() => {
        const params = new URLSearchParams()

        if (searchQuery.value) params.set('search', searchQuery.value)
        if (selectedCategory.value) params.set('category_id', selectedCategory.value)
        if (selectedStatus.value) params.set('status', selectedStatus.value)
        if (selectedSort.value !== 'latest') params.set('sort', selectedSort.value)

        const queryString = params.toString()
        const newUrl = queryString ? `/videos?${queryString}` : '/videos'

        router.visit(newUrl, {
            preserveState: true,
            preserveScroll: true,
            replace: true
        })
    }, 500)
})

// Functions
function playVideo(video: Video) {
    router.visit(`/videos/${video.id}`)
}

function clearFilters() {
    searchQuery.value = ''
    selectedCategory.value = ''
    selectedStatus.value = ''
    selectedSort.value = 'latest'
}

function getStatusVariant(status: string) {
    switch (status) {
        case 'completed':
            return 'default'
        case 'in_progress':
            return 'secondary'
        default:
            return 'outline'
    }
}

function getStatusLabel(status: string) {
    switch (status) {
        case 'completed':
            return 'Completed'
        case 'in_progress':
            return 'In Progress'
        default:
            return 'New'
    }
}

function formatRelativeTime(dateString: string) {
    const date = new Date(dateString)
    const now = new Date()
    const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60))

    if (diffInHours < 1) return 'just now'
    if (diffInHours < 24) return `${diffInHours}h ago`

    const diffInDays = Math.floor(diffInHours / 24)
    if (diffInDays < 7) return `${diffInDays}d ago`

    return date.toLocaleDateString()
}

function handleImageError(event: Event) {
    const img = event.target as HTMLImageElement
    img.style.display = 'none'
}
</script>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
