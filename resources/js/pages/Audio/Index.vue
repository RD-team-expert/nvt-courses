<!-- Audio Index Page for Users - Updated for Uploaded Thumbnails -->
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Progress } from '@/components/ui/progress'
import { Alert, AlertDescription } from '@/components/ui/alert'

// Icons
import {
    Search,
    Filter,
    Play,
    Clock,
    CheckCircle,
    BookOpen,
    RotateCcw,
    Volume2,
    X,
    TrendingUp,
    Sparkles,
    Star
} from 'lucide-vue-next'

interface Audio {
    id: number
    name: string
    description: string
    duration: number
    formatted_duration: string
    thumbnail_url?: string // This now handles both uploaded files and external URLs
    category?: {
        id: number
        name: string
    }
    user_progress?: {
        current_time: number
        formatted_current_time: string
        completion_percentage: number
        is_completed: boolean
        last_accessed_at: string
    }
    status: 'not_started' | 'in_progress' | 'completed'
}

interface AudioCategory {
    id: number
    name: string
}

const props = defineProps<{
    audios: Audio[]
    categories: AudioCategory[]
    filters: {
        search?: string
        category_id?: number
        status?: string
        sort?: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Audio Courses', href: '/audio' }
]

// Filters
const searchQuery = ref(props.filters.search || '')
const selectedCategory = ref(props.filters.category_id?.toString() || 'all')
const selectedStatus = ref(props.filters.status || 'all')
const selectedSort = ref(props.filters.sort || 'latest')
const showFilters = ref(false)

// Filter options
const statusOptions = [
    { value: 'all', label: 'All Status', icon: Volume2 },
    { value: 'not_started', label: 'Not Started', icon: Play },
    { value: 'in_progress', label: 'In Progress', icon: RotateCcw },
    { value: 'completed', label: 'Completed', icon: CheckCircle }
]

const sortOptions = [
    { value: 'latest', label: 'Latest Added' },
    { value: 'name', label: 'Name A-Z' },
    { value: 'duration', label: 'Duration' }
]

// Apply filters
const applyFilters = () => {
    const params = {
        search: searchQuery.value || undefined,
        category_id: selectedCategory.value !== 'all' ? selectedCategory.value : undefined,
        status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
        sort: selectedSort.value !== 'latest' ? selectedSort.value : undefined
    }

    router.get('/audio', params, { preserveState: true })
}

// Clear filters
const clearFilters = () => {
    searchQuery.value = ''
    selectedCategory.value = 'all'
    selectedStatus.value = 'all'
    selectedSort.value = 'latest'
    applyFilters()
}

// Get status badge variant
const getStatusVariant = (status: string) => {
    if (status === 'completed') return 'default'
    if (status === 'in_progress') return 'secondary'
    return 'outline'
}

// Get status color classes
const getStatusColor = (status: string) => {
    if (status === 'completed') return 'text-green-600'
    if (status === 'in_progress') return 'text-yellow-600'
    return 'text-blue-600'
}

// Get status icon
const getStatusIcon = (status: string) => {
    if (status === 'completed') return CheckCircle
    if (status === 'in_progress') return RotateCcw
    return Play
}

// Auto-apply search filter with debounce
let searchTimeout: NodeJS.Timeout
watch(searchQuery, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilters, 500)
})

// Statistics
const stats = computed(() => ({
    total: props.audios.length,
    not_started: props.audios.filter(a => a.status === 'not_started').length,
    in_progress: props.audios.filter(a => a.status === 'in_progress').length,
    completed: props.audios.filter(a => a.status === 'completed').length
}))

// Featured/recommended audios (you can modify this logic based on your needs)
const featuredAudios = computed(() =>
    props.audios.filter(a => a.status === 'in_progress').slice(0, 3)
)

// Get thumbnail image with fallback
const getThumbnailUrl = (audio: Audio): string | null => {
    return audio.thumbnail_url || null
}

// Check if has active filters
const hasActiveFilters = computed(() =>
    searchQuery.value ||
    selectedCategory.value !== 'all' ||
    selectedStatus.value !== 'all' ||
    selectedSort.value !== 'latest'
)
</script>

<template>
    <Head title="Audio Courses" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 bg-background text-foreground">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2 text-foreground">
                        <Volume2 class="h-7 w-7 text-blue-600 dark:text-blue-500" />
                        Audio Courses
                    </h1>
                    <p class="text-muted-foreground">Discover and listen to educational audio content</p>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 w-full sm:w-auto">
                    <div class="text-center p-3 bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-950/50 dark:to-slate-900/50 rounded-lg border border-slate-200/50 dark:border-slate-800/50">
                        <div class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ stats.total }}</div>
                        <div class="text-xs text-slate-600 dark:text-slate-400">Total</div>
                    </div>
                    <div class="text-center p-3 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/50 rounded-lg border border-blue-200/50 dark:border-blue-800/50">
                        <div class="text-lg font-bold text-blue-700 dark:text-blue-300">{{ stats.not_started }}</div>
                        <div class="text-xs text-blue-600 dark:text-blue-400">New</div>
                    </div>
                    <div class="text-center p-3 bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-950/50 dark:to-yellow-900/50 rounded-lg border border-yellow-200/50 dark:border-yellow-800/50">
                        <div class="text-lg font-bold text-yellow-700 dark:text-yellow-300">{{ stats.in_progress }}</div>
                        <div class="text-xs text-yellow-600 dark:text-yellow-400">Progress</div>
                    </div>
                    <div class="text-center p-3 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/50 rounded-lg border border-green-200/50 dark:border-green-800/50">
                        <div class="text-lg font-bold text-green-700 dark:text-green-300">{{ stats.completed }}</div>
                        <div class="text-xs text-green-600 dark:text-green-400">Done</div>
                    </div>
                </div>
            </div>

            <!-- Continue Listening Section -->
            <div v-if="featuredAudios.length > 0" class="space-y-4">
                <div class="flex items-center gap-2">
                    <RotateCcw class="h-5 w-5 text-yellow-600 dark:text-yellow-500" />
                    <h2 class="text-xl font-semibold text-foreground">Continue Listening</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Card
                        v-for="audio in featuredAudios"
                        :key="audio.id"
                        class="group hover:shadow-lg dark:hover:shadow-2xl transition-all duration-200 cursor-pointer border-yellow-200 dark:border-yellow-800 bg-card hover:bg-accent/50"
                        @click="$inertia.visit(`/audio/${audio.id}`)"
                    >
                        <div class="aspect-video bg-gradient-to-br from-yellow-100 to-orange-100 dark:from-yellow-950/50 dark:to-orange-950/50 relative overflow-hidden rounded-t-lg border-b border-border">
                            <img
                                v-if="getThumbnailUrl(audio)"
                                :src="getThumbnailUrl(audio)!"
                                :alt="audio.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                @error="$event.target.style.display = 'none'"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <Volume2 class="h-12 w-12 text-yellow-600 dark:text-yellow-400" />
                            </div>

                            <!-- Continue Badge -->
                            <Badge variant="default" class="absolute top-2 left-2 bg-yellow-600 dark:bg-yellow-500 text-white">
                                <RotateCcw class="h-3 w-3 mr-1" />
                                {{ Math.round(audio.user_progress?.completion_percentage || 0) }}%
                            </Badge>
                        </div>

                        <CardContent class="p-3 bg-card text-card-foreground">
                            <CardTitle class="text-sm line-clamp-1 text-card-foreground">{{ audio.name }}</CardTitle>
                            <div class="mt-2">
                                <Progress :value="audio.user_progress?.completion_percentage || 0" class="h-1 bg-muted" />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Search and Filters -->
            <Card class="bg-card border-border">
                <CardContent class="p-4 bg-card">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Search -->
                        <div class="relative flex-1">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                placeholder="Search audio courses..."
                                class="pl-10 pr-10 bg-background border-border text-foreground placeholder:text-muted-foreground"
                            />
                            <Button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                variant="ghost"
                                size="sm"
                                class="absolute right-1 top-1/2 -translate-y-1/2 h-6 w-6 p-0 text-muted-foreground hover:text-foreground"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </div>

                        <!-- Filter Toggle -->
                        <Button
                            @click="showFilters = !showFilters"
                            :variant="hasActiveFilters ? 'default' : 'outline'"
                            class="gap-2 bg-background border-border text-foreground hover:bg-accent hover:text-accent-foreground"
                        >
                            <Filter class="h-4 w-4" />
                            Filters
                            <Badge v-if="hasActiveFilters" variant="secondary" class="ml-1 bg-secondary text-secondary-foreground">
                                {{ Object.values(props.filters).filter(Boolean).length }}
                            </Badge>
                        </Button>
                    </div>

                    <!-- Filter Panel -->
                    <div v-if="showFilters" class="mt-4 pt-4 border-t border-border space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Category Filter -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-foreground">Category</label>
                                <select
                                    v-model="selectedCategory"
                                    @change="applyFilters"
                                    class="flex h-10 w-full rounded-md border border-input bg-background text-foreground px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    <option value="all">All Categories</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id.toString()">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-foreground">Status</label>
                                <select
                                    v-model="selectedStatus"
                                    @change="applyFilters"
                                    class="flex h-10 w-full rounded-md border border-input bg-background text-foreground px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Sort Filter -->
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-foreground">Sort By</label>
                                <select
                                    v-model="selectedSort"
                                    @change="applyFilters"
                                    class="flex h-10 w-full rounded-md border border-input bg-background text-foreground px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Filter Actions -->
                        <div class="flex gap-2">
                            <Button @click="clearFilters" variant="outline" size="sm" class="bg-background border-border text-foreground hover:bg-accent">
                                Clear All Filters
                            </Button>
                            <Button @click="showFilters = false" variant="ghost" size="sm" class="text-muted-foreground hover:text-foreground hover:bg-accent">
                                Hide Filters
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Results Info -->
            <div v-if="hasActiveFilters" class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    {{ audios.length }} course{{ audios.length !== 1 ? 's' : '' }} found
                </div>
                <Button @click="clearFilters" variant="ghost" size="sm" class="text-muted-foreground hover:text-foreground hover:bg-accent">
                    Clear filters
                </Button>
            </div>

            <!-- Audio Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Empty State -->
                <div v-if="audios.length === 0" class="col-span-full">
                    <Card class="bg-card border-border">
                        <CardContent class="text-center py-12 bg-card">
                            <Volume2 class="h-16 w-16 mx-auto text-muted-foreground mb-6" />
                            <CardTitle class="mb-2 text-xl text-card-foreground">No audio courses found</CardTitle>
                            <CardDescription class="max-w-md mx-auto text-muted-foreground">
                                {{ hasActiveFilters
                                ? 'Try adjusting your search terms or filters to find more courses'
                                : 'Audio courses will appear here when available. Check back soon!' }}
                            </CardDescription>
                            <Button v-if="hasActiveFilters" @click="clearFilters" class="mt-4 bg-primary text-primary-foreground hover:bg-primary/90">
                                Clear Filters
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- Audio Cards -->
                <Card
                    v-for="audio in audios"
                    :key="audio.id"
                    class="group hover:shadow-lg dark:hover:shadow-2xl transition-all duration-200 cursor-pointer border-0 ring-1 ring-border hover:ring-2 hover:ring-primary/50 bg-card hover:bg-accent/30"
                    @click="$inertia.visit(`/audio/${audio.id}`)"
                >
                    <!-- Thumbnail -->
                    <div class="aspect-video bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-950/50 dark:to-purple-950/50 relative overflow-hidden rounded-t-lg border-b border-border">
                        <img
                            v-if="getThumbnailUrl(audio)"
                            :src="getThumbnailUrl(audio)!"
                            :alt="audio.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                            @error="$event.target.style.display = 'none'"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <Volume2 class="h-12 w-12 text-blue-500 dark:text-blue-400" />
                        </div>

                        <!-- Play Overlay -->
                        <div class="absolute inset-0 bg-black/30 dark:bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center">
                            <div class="bg-white/95 dark:bg-gray-800/95 rounded-full p-4 transform scale-90 group-hover:scale-100 transition-transform duration-200 border border-border">
                                <component :is="getStatusIcon(audio.status)" :class="`h-8 w-8 ${getStatusColor(audio.status)}`" />
                            </div>
                        </div>

                        <!-- Duration Badge -->
                        <Badge
                            v-if="audio.formatted_duration"
                            variant="secondary"
                            class="absolute top-3 right-3 bg-black/70 dark:bg-gray-900/80 text-white dark:text-gray-100 border-0 backdrop-blur-sm"
                        >
                            <Clock class="h-3 w-3 mr-1" />
                            {{ audio.formatted_duration }}
                        </Badge>

                        <!-- Status Badge -->
                        <Badge
                            :variant="getStatusVariant(audio.status)"
                            class="absolute bottom-3 right-3 backdrop-blur-sm bg-background/90 dark:bg-card/90 border-border"
                        >
                            <component :is="getStatusIcon(audio.status)" class="h-3 w-3 mr-1" />
                            {{ audio.status === 'not_started' ? 'New' :
                            audio.status === 'in_progress' ? 'Continue' : 'Completed' }}
                        </Badge>
                    </div>

                    <CardContent class="p-5 bg-card text-card-foreground">
                        <div class="space-y-4">
                            <!-- Category -->
                            <div v-if="audio.category" class="flex items-center gap-2">
                                <BookOpen class="h-4 w-4 text-muted-foreground" />
                                <Badge variant="outline" class="text-xs bg-background border-border text-foreground">
                                    {{ audio.category.name }}
                                </Badge>
                            </div>

                            <!-- Title & Description -->
                            <div class="space-y-2">
                                <CardTitle class="text-lg line-clamp-2 group-hover:text-primary transition-colors leading-tight text-card-foreground">
                                    {{ audio.name }}
                                </CardTitle>
                                <CardDescription class="line-clamp-2 text-sm text-muted-foreground">
                                    {{ audio.description }}
                                </CardDescription>
                            </div>

                            <!-- Progress Section -->
                            <div v-if="audio.user_progress && audio.user_progress.completion_percentage > 0" class="space-y-3">
                                <div class="flex justify-between items-center text-sm">
                  <span class="text-muted-foreground flex items-center gap-1">
                    <TrendingUp class="h-3 w-3" />
                    Progress
                  </span>
                                    <span class="font-semibold" :class="getStatusColor(audio.status)">
                    {{ Math.round(audio.user_progress.completion_percentage) }}%
                  </span>
                                </div>
                                <Progress :value="audio.user_progress.completion_percentage" class="h-2 bg-muted" />
                                <div class="flex justify-between items-center text-xs text-muted-foreground">
                                    <span>{{ audio.user_progress.formatted_current_time }}</span>
                                    <span>{{ audio.formatted_duration }}</span>
                                </div>
                            </div>

                            <!-- Action Button -->
                            <Button class="w-full group-hover:bg-primary group-hover:text-primary-foreground transition-colors bg-secondary text-secondary-foreground hover:bg-secondary/80" size="sm">
                                <component :is="getStatusIcon(audio.status)" class="h-4 w-4 mr-2" />
                                {{ audio.status === 'not_started' ? 'Start Listening' :
                                audio.status === 'in_progress' ? 'Continue Listening' : 'Listen Again' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Load More or Pagination could go here -->
            <div v-if="audios.length > 0" class="text-center">
                <p class="text-sm text-muted-foreground">
                    Showing {{ audios.length }} course{{ audios.length !== 1 ? 's' : '' }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>

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
