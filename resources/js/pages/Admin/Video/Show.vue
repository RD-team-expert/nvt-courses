<template>
    <Head :title="`Analytics - ${video.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 bg-background text-foreground">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div class="flex items-center gap-4">
                    <Button as-child variant="ghost">
                        <Link href="/admin/videos">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Videos
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold flex items-center gap-2">
                            <BarChart3 class="h-7 w-7 text-blue-600 dark:text-blue-500" />
                            Video Analytics
                        </h1>
                        <p class="text-muted-foreground">{{ video.name }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button as-child variant="outline">
                        <Link :href="`/admin/videos/${video.id}/edit`">
                            <Edit class="h-4 w-4 mr-2" />
                            Edit Video
                        </Link>
                    </Button>
                    <Button as-child>
                        <Link :href="`/videos/${video.id}`" target="_blank">
                            <ExternalLink class="h-4 w-4 mr-2" />
                            View Video
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Video Info Card -->
            <Card class="bg-card border-border">
                <CardContent class="p-6">
                    <div class="flex items-start gap-6">
                        <!-- Thumbnail -->
                        <div class="w-48 h-32 rounded-lg overflow-hidden bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-950/50 dark:to-purple-950/50 flex items-center justify-center shrink-0 border border-muted">
                            <img
                                v-if="video.thumbnail_url"
                                :src="video.thumbnail_url"
                                :alt="video.name"
                                class="w-full h-full object-cover"
                                @error="handleImageError"
                            />
                            <PlaySquare v-else class="h-16 w-16 text-blue-500 dark:text-blue-400 opacity-50" />
                        </div>

                        <!-- Video Details -->
                        <div class="flex-1 space-y-3">
                            <div>
                                <h2 class="text-xl font-semibold text-foreground">{{ video.name }}</h2>
                                <p v-if="video.description" class="text-muted-foreground mt-1">
                                    {{ video.description }}
                                </p>
                            </div>

                            <div class="flex items-center gap-6 text-sm text-muted-foreground">
                                <div class="flex items-center gap-1">
                                    <Clock class="h-4 w-4" />
                                    {{ video.formatted_duration }}
                                </div>
                                <Badge v-if="video.category" variant="outline" class="bg-background border-border text-foreground">
                                    <FolderOpen class="h-3 w-3 mr-1" />
                                    {{ video.category.name }}
                                </Badge>
                                <Badge :variant="video.is_active ? 'default' : 'secondary'" class="bg-background border-border">
                                    {{ video.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                                <span>Created {{ new Date(video.created_at).toLocaleDateString() }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- ✅ NEW: Transcoding Status Card (only for local videos) -->
            <Card v-if="video.storage_type === 'local'" class="bg-card border-border">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Film class="h-5 w-5" />
                        Video Transcoding
                    </CardTitle>
                    <CardDescription>
                        Multi-quality transcoding status and available variants
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Transcoding Status -->
                    <div class="flex items-center justify-between p-4 rounded-lg border border-border bg-muted/50">
                        <div class="flex items-center gap-3">
                            <div :class="getTranscodeStatusIconClass(video.transcode_status)">
                                <component :is="getTranscodeStatusIcon(video.transcode_status)" class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="font-medium text-foreground">Transcoding Status</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ getTranscodeStatusDescription(video.transcode_status) }}
                                </div>
                            </div>
                        </div>
                        <Badge :variant="getTranscodeStatusVariant(video.transcode_status)" class="gap-1">
                            {{ getTranscodeStatusLabel(video.transcode_status) }}
                        </Badge>
                    </div>

                    <!-- Available Quality Variants (for completed videos) -->
                    <div v-if="video.transcode_status === 'completed' && video.qualities && video.qualities.length > 0">
                        <h4 class="text-sm font-medium text-foreground mb-3">Available Quality Variants</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div
                                v-for="quality in video.qualities"
                                :key="quality.quality"
                                class="flex items-center justify-between p-3 rounded-lg border border-border bg-background"
                            >
                                <div class="flex items-center gap-2">
                                    <Video class="h-4 w-4 text-muted-foreground" />
                                    <span class="font-medium text-foreground">{{ quality.quality }}</span>
                                </div>
                                <span class="text-sm text-muted-foreground">{{ quality.formatted_file_size }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Retry Button (for failed status) -->
                    <div v-if="video.transcode_status === 'failed'" class="pt-2">
                        <Button
                            @click="retryTranscoding"
                            variant="outline"
                            class="w-full justify-center gap-2"
                        >
                            <RefreshCw class="h-4 w-4" />
                            Retry Transcoding
                        </Button>
                    </div>

                    <!-- Processing Indicator -->
                    <div v-if="video.transcode_status === 'processing'" class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Loader2 class="h-4 w-4 animate-spin" />
                        <span>Transcoding in progress... This may take several minutes.</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Key Analytics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="border-0 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/50 border border-blue-200/50 dark:border-blue-800/50">
                    <CardContent class="p-6">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-blue-500 dark:bg-blue-600 rounded-lg">
                                <Users class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                    {{ analytics.total_viewers }}
                                </div>
                                <div class="text-sm text-blue-700 dark:text-blue-300">Total Viewers</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/50 border border-green-200/50 dark:border-green-800/50">
                    <CardContent class="p-6">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-green-500 dark:bg-green-600 rounded-lg">
                                <CheckCircle class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                                    {{ analytics.completed_viewers }}
                                </div>
                                <div class="text-sm text-green-700 dark:text-green-300">Completed</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/50 dark:to-purple-900/50 border border-purple-200/50 dark:border-purple-800/50">
                    <CardContent class="p-6">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-purple-500 dark:bg-purple-600 rounded-lg">
                                <TrendingUp class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                                    {{ analytics.completion_rate }}%
                                </div>
                                <div class="text-sm text-purple-700 dark:text-purple-300">Completion Rate</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950/50 dark:to-orange-900/50 border border-orange-200/50 dark:border-orange-800/50">
                    <CardContent class="p-6">
                        <div class="flex items-center gap-3">
                            <div class="p-3 bg-orange-500 dark:bg-orange-600 rounded-lg">
                                <Clock class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-orange-900 dark:text-orange-100">
                                    {{ analytics.total_watch_hours }}h
                                </div>
                                <div class="text-sm text-orange-700 dark:text-orange-300">Watch Hours</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Progress Distribution -->
                <Card class="bg-card border-border">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <PieChart class="h-5 w-5" />
                            Progress Distribution
                        </CardTitle>
                        <CardDescription>
                            How viewers are progressing through the video
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-950/50 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                    <span class="text-sm font-medium">0-25% Progress</span>
                                </div>
                                <span class="text-sm text-muted-foreground">
                  {{ progressStats.low }} viewers
                </span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-950/50 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <span class="text-sm font-medium">25-75% Progress</span>
                                </div>
                                <span class="text-sm text-muted-foreground">
                  {{ progressStats.medium }} viewers
                </span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-950/50 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span class="text-sm font-medium">75-100% Progress</span>
                                </div>
                                <span class="text-sm text-muted-foreground">
                  {{ progressStats.high }} viewers
                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Engagement Insights -->
                <Card class="bg-card border-border">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Target class="h-5 w-5" />
                            Engagement Insights
                        </CardTitle>
                        <CardDescription>
                            Key performance indicators
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Average Progress</span>
                                <span class="font-medium">{{ analytics.avg_progress }}%</span>
                            </div>
                            <div class="w-full bg-muted rounded-full h-2">
                                <div
                                    class="bg-blue-500 rounded-full h-2 transition-all"
                                    :style="{ width: `${analytics.avg_progress}%` }"
                                ></div>
                            </div>

                            <Separator />

                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-muted-foreground">Drop-off Rate</span>
                                    <span class="font-medium">{{ 100 - analytics.completion_rate }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-muted-foreground">Avg Watch Time</span>
                                    <span class="font-medium">{{ formatAvgWatchTime(analytics.avg_progress, video.duration) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-muted-foreground">Total Engagement</span>
                                    <span class="font-medium">{{ analytics.total_watch_hours }}h</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activity -->
            <Card class="bg-card border-border">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Activity class="h-5 w-5" />
                        Recent Viewer Activity
                    </CardTitle>
                    <CardDescription>
                        Latest {{ recent_activity.length }} viewers and their progress
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow class="border-border">
                                <TableHead class="text-foreground">Viewer</TableHead>
                                <TableHead class="text-foreground">Progress</TableHead>
                                <TableHead class="text-foreground">Current Position</TableHead>
                                <TableHead class="text-foreground">Watch Time</TableHead>
                                <TableHead class="text-foreground">Last Accessed</TableHead>
                                <TableHead class="text-foreground">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="activity in recent_activity"
                                :key="activity.id"
                                class="hover:bg-accent/50 border-border"
                            >
                                <!-- Viewer Info -->
                                <TableCell>
                                    <div class="font-medium">{{ activity.user.name }}</div>
                                    <div class="text-sm text-muted-foreground">{{ activity.user.email }}</div>
                                </TableCell>

                                <!-- Progress Bar -->
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <div class="w-full max-w-20 bg-muted rounded-full h-2">
                                            <div
                                                class="bg-blue-500 rounded-full h-2"
                                                :style="{ width: `${activity.completion_percentage}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-medium min-w-0">
                      {{ Math.round(activity.completion_percentage) }}%
                    </span>
                                    </div>
                                </TableCell>

                                <!-- Current Position -->
                                <TableCell class="text-sm">
                                    {{ activity.formatted_current_time }}
                                </TableCell>

                                <!-- Watch Time (Fixed Format) -->
                                <TableCell class="text-sm">
                                    {{ formatWatchTime(activity.total_watched_time) }}
                                </TableCell>

                                <!-- Last Accessed -->
                                <TableCell class="text-sm text-muted-foreground">
                                    {{ formatRelativeTime(activity.last_accessed_at) }}
                                </TableCell>

                                <!-- Status -->
                                <TableCell>
                                    <Badge :variant="activity.is_completed ? 'default' : 'secondary'" class="bg-background border-border">
                                        {{ activity.is_completed ? 'Completed' : 'In Progress' }}
                                    </Badge>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-if="recent_activity.length === 0" class="border-border">
                                <TableCell colspan="6" class="text-center py-8 text-muted-foreground">
                                    <Users class="h-12 w-12 mx-auto mb-4 opacity-50" />
                                    <div class="text-lg font-medium mb-2">No viewer activity yet</div>
                                    <p>Once users start watching this video, their activity will appear here</p>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Additional Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Video Performance Summary -->
                <Card class="bg-card border-border">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="h-5 w-5" />
                            Performance Summary
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-muted-foreground">Video Length</span>
                            <span class="font-medium">{{ video.formatted_duration }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-muted-foreground">Total Views</span>
                            <span class="font-medium">{{ analytics.total_viewers }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-muted-foreground">Completion Rate</span>
                            <span class="font-medium" :class="getCompletionRateColor(analytics.completion_rate)">
                {{ analytics.completion_rate }}%
              </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-muted-foreground">Avg Progress</span>
                            <span class="font-medium">{{ analytics.avg_progress }}%</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Engagement Stats -->
                <Card class="bg-card border-border">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Engagement Stats
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-muted-foreground">Active Viewers</span>
                            <span class="font-medium">{{ analytics.total_viewers - analytics.completed_viewers }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-muted-foreground">Completed Viewers</span>
                            <span class="font-medium text-green-600">{{ analytics.completed_viewers }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-muted-foreground">Total Watch Hours</span>
                            <span class="font-medium">{{ analytics.total_watch_hours }}h</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-muted-foreground">Avg per Viewer</span>
                            <span class="font-medium">
                {{ analytics.total_viewers > 0 ? formatWatchTime((analytics.total_watch_hours * 3600) / analytics.total_viewers) : '0s' }}
              </span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Quick Actions -->
                <Card class="bg-card border-border">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Settings class="h-5 w-5" />
                            Quick Actions
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <Button as-child class="w-full justify-start" variant="outline">
                            <Link :href="`/admin/videos/${video.id}/edit`">
                                <Edit class="h-4 w-4 mr-2" />
                                Edit Video Details
                            </Link>
                        </Button>

                        <Button as-child class="w-full justify-start" variant="outline">
                            <Link :href="`/videos/${video.id}`" target="_blank">
                                <ExternalLink class="h-4 w-4 mr-2" />
                                Preview Video
                            </Link>
                        </Button>

                        <Button
                            @click="toggleVideoStatus"
                            class="w-full justify-start"
                            :variant="video.is_active ? 'destructive' : 'default'"
                        >
                            <component :is="video.is_active ? 'EyeOff' : 'Eye'" class="h-4 w-4 mr-2" />
                            {{ video.is_active ? 'Deactivate' : 'Activate' }} Video
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Separator } from '@/components/ui/separator'

// Icons
import {
    ArrowLeft,
    BarChart3,
    Edit,
    ExternalLink,
    PlaySquare,
    Clock,
    FolderOpen,
    Users,
    CheckCircle,
    TrendingUp,
    PieChart,
    Target,
    Activity,
    Settings,
    Eye,
    EyeOff,
    Film,           // ✅ NEW - for transcoding card
    Video,          // ✅ NEW - for quality variants
    RefreshCw,      // ✅ NEW - for retry button
    Loader2,        // ✅ NEW - for processing status
    AlertCircle,    // ✅ NEW - for failed status
    CheckCircle2,   // ✅ NEW - for completed status
    Circle,         // ✅ NEW - for pending status
} from 'lucide-vue-next'

interface VideoQuality {
    quality: string
    file_size: number
    formatted_file_size: string
}

interface Video {
    id: number
    name: string
    description?: string
    formatted_duration: string
    duration?: number
    thumbnail_url?: string
    is_active: boolean
    storage_type?: 'google_drive' | 'local'
    transcode_status?: 'pending' | 'processing' | 'completed' | 'failed' | 'skipped'
    qualities?: VideoQuality[]
    category?: {
        id: number
        name: string
    }
    creator: {
        id: number
        name: string
    }
    created_at: string
}

interface Analytics {
    total_viewers: number
    completed_viewers: number
    completion_rate: number
    avg_progress: number
    total_watch_hours: number
}

interface RecentActivity {
    id: number
    user: {
        id: number
        name: string
        email: string
    }
    current_time: number
    formatted_current_time: string
    completion_percentage: number
    is_completed: boolean
    total_watched_time: number
    last_accessed_at: string
}

const props = defineProps<{
    video: Video
    analytics: Analytics
    recent_activity: RecentActivity[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Video Management', href: '/admin/videos' },
    { title: 'Analytics', href: '' },
]

// Progress distribution stats
const progressStats = computed(() => {
    const activities = props.recent_activity
    return {
        low: activities.filter(a => a.completion_percentage < 25).length,
        medium: activities.filter(a => a.completion_percentage >= 25 && a.completion_percentage < 75).length,
        high: activities.filter(a => a.completion_percentage >= 75).length,
    }
})

// Format watch time properly (hours, minutes, seconds)
const formatWatchTime = (totalSeconds: number): string => {
    if (!totalSeconds || totalSeconds < 1) return '0s'

    const hours = Math.floor(totalSeconds / 3600)
    const minutes = Math.floor((totalSeconds % 3600) / 60)
    const seconds = Math.floor(totalSeconds % 60)

    if (hours > 0) {
        if (minutes > 0) {
            return `${hours}h ${minutes}m`
        }
        return `${hours}h`
    }

    if (minutes > 0) {
        if (seconds > 0) {
            return `${minutes}m ${seconds}s`
        }
        return `${minutes}m`
    }

    return `${seconds}s`
}

// Format average watch time based on progress and duration
const formatAvgWatchTime = (avgProgress: number, duration?: number): string => {
    if (!duration || avgProgress === 0) return '0m'

    const avgSeconds = (avgProgress / 100) * duration
    return formatWatchTime(avgSeconds)
}

// Helper function for relative time
const formatRelativeTime = (dateString: string): string => {
    if (!dateString) return 'Never'

    const date = new Date(dateString)
    const now = new Date()
    const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60))

    if (diffInHours < 1) return 'Just now'
    if (diffInHours < 24) return `${diffInHours}h ago`

    const diffInDays = Math.floor(diffInHours / 24)
    if (diffInDays < 7) return `${diffInDays}d ago`

    return date.toLocaleDateString()
}

// Get completion rate color
const getCompletionRateColor = (rate: number): string => {
    if (rate >= 80) return 'text-green-600'
    if (rate >= 60) return 'text-yellow-600'
    return 'text-red-600'
}

// Handle image error
const handleImageError = (event: Event) => {
    const img = event.target as HTMLImageElement
    img.style.display = 'none'
}

// Toggle video status
const toggleVideoStatus = () => {
    router.post(`/admin/videos/${props.video.id}/toggle-active`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            console.log(`Video ${props.video.is_active ? 'deactivated' : 'activated'} successfully`)
        }
    })
}

// ✅ NEW: Transcoding status helpers
const getTranscodeStatusVariant = (status?: string) => {
    switch (status) {
        case 'completed':
            return 'default'
        case 'processing':
            return 'secondary'
        case 'failed':
            return 'destructive'
        case 'pending':
            return 'outline'
        default:
            return 'outline'
    }
}

const getTranscodeStatusIcon = (status?: string) => {
    switch (status) {
        case 'completed':
            return CheckCircle2
        case 'processing':
            return Loader2
        case 'failed':
            return AlertCircle
        case 'pending':
            return Circle
        default:
            return Circle
    }
}

const getTranscodeStatusIconClass = (status?: string): string => {
    const baseClass = 'p-2 rounded-lg'
    switch (status) {
        case 'completed':
            return `${baseClass} bg-green-100 dark:bg-green-950/50 text-green-600 dark:text-green-400`
        case 'processing':
            return `${baseClass} bg-blue-100 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400`
        case 'failed':
            return `${baseClass} bg-red-100 dark:bg-red-950/50 text-red-600 dark:text-red-400`
        case 'pending':
            return `${baseClass} bg-gray-100 dark:bg-gray-950/50 text-gray-600 dark:text-gray-400`
        default:
            return `${baseClass} bg-gray-100 dark:bg-gray-950/50 text-gray-600 dark:text-gray-400`
    }
}

const getTranscodeStatusLabel = (status?: string): string => {
    switch (status) {
        case 'completed':
            return 'Completed'
        case 'processing':
            return 'Processing'
        case 'failed':
            return 'Failed'
        case 'pending':
            return 'Pending'
        case 'skipped':
            return 'Skipped'
        default:
            return 'Unknown'
    }
}

const getTranscodeStatusDescription = (status?: string): string => {
    switch (status) {
        case 'completed':
            return 'Video has been transcoded to multiple quality levels'
        case 'processing':
            return 'Video is currently being transcoded'
        case 'failed':
            return 'Transcoding failed - you can retry below'
        case 'pending':
            return 'Video is queued for transcoding'
        case 'skipped':
            return 'Transcoding was skipped for this video'
        default:
            return 'Transcoding status unknown'
    }
}

// ✅ NEW: Retry transcoding
const retryTranscoding = () => {
    if (confirm('Are you sure you want to retry transcoding this video?')) {
        router.post(`/admin/videos/${props.video.id}/retry-transcode`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Transcoding retry initiated')
            },
            onError: (errors) => {
                console.error('Failed to retry transcoding:', errors)
            }
        })
    }
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
