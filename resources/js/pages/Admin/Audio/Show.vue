<!-- Admin Audio Analytics Page - Updated with Image Handling -->
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'

// shadcn-vue components
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

// Icons
import {
    ArrowLeft,
    Volume2,
    Clock,
    Users,
    TrendingUp,
    Play,
    CheckCircle,
    Edit,
    Settings,
    MoreHorizontal,
    Calendar,
    BarChart3,
    Timer,
    Eye,
    BookOpen,
    Trash2,
    ToggleLeft,
    ToggleRight,
    AlertTriangle
} from 'lucide-vue-next'

interface Audio {
    id: number
    name: string
    description: string
    google_cloud_url: string
    duration: number
    formatted_duration: string
    thumbnail_url?: string
    is_active: boolean
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
    total_listeners: number
    completed_listeners: number
    completion_rate: number
    avg_progress: number
    total_listening_hours: number
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
    total_listened_time: number
    last_accessed_at?: string
}

const props = defineProps<{
    audio: Audio
    analytics: Analytics
    recent_activity: RecentActivity[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Audio Management', href: '/admin/audio' },
    { title: props.audio.name, href: '#' }
]

// Analytics computations
const completionRateColor = computed(() => {
    if (props.analytics.completion_rate >= 80) return 'text-green-600'
    if (props.analytics.completion_rate >= 60) return 'text-yellow-600'
    return 'text-red-600'
})

const avgProgressColor = computed(() => {
    if (props.analytics.avg_progress >= 80) return 'text-green-600'
    if (props.analytics.avg_progress >= 60) return 'text-yellow-600'
    return 'text-red-600'
})

// ✅ Handle thumbnail image errors
const thumbnailError = ref(false)
function handleThumbnailError() {
    thumbnailError.value = true
}

// Format time helper
function formatTime(seconds: number): string {
    if (!seconds || isNaN(seconds)) return '00:00'

    const minutes = Math.floor(seconds / 60)
    const remainingSeconds = Math.floor(seconds % 60)
    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`
}

// Format date
function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString() + ' ' +
        new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

// Get progress color
function getProgressColor(percentage: number): string {
    if (percentage >= 95) return 'text-green-600'
    if (percentage >= 75) return 'text-blue-600'
    if (percentage >= 50) return 'text-yellow-600'
    return 'text-gray-600'
}

// Actions
function editAudio() {
    router.visit(`/admin/audio/${props.audio.id}/edit`)
}

function toggleActive() {
    router.post(`/admin/audio/${props.audio.id}/toggle-active`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Audio status toggled successfully')
        }
    })
}

function deleteAudio() {
    if (confirm(`Are you sure you want to delete "${props.audio.name}"? This action cannot be undone.`)) {
        router.delete(`/admin/audio/${props.audio.id}`, {
            onSuccess: () => {
                router.visit('/admin/audio')
            }
        })
    }
}
</script>

<template>
    <Head :title="`${audio.name} - Audio Analytics`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 bg-background text-foreground">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-4">
                    <Button asChild variant="ghost" class="text-muted-foreground hover:text-foreground hover:bg-accent">
                        <Link href="/admin/audio">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Audio
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold flex items-center gap-2 text-foreground">
                            <BarChart3 class="h-7 w-7 text-blue-600 dark:text-blue-500" />
                            Audio Analytics
                        </h1>
                        <p class="text-muted-foreground">Performance insights and user activity</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <Button asChild variant="outline" size="sm" class="border-border text-foreground hover:bg-accent hover:text-accent-foreground">
                        <Link :href="`/audio/${audio.id}`" target="_blank">
                            <Eye class="h-4 w-4 mr-2" />
                            Preview
                        </Link>
                    </Button>

                    <Button @click="editAudio" variant="outline" size="sm" class="border-border text-foreground hover:bg-accent hover:text-accent-foreground">
                        <Edit class="h-4 w-4 mr-2" />
                        Edit Audio
                    </Button>

                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" size="sm" class="border-border text-foreground hover:bg-accent hover:text-accent-foreground">
                                <MoreHorizontal class="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="bg-popover border-border text-popover-foreground">
                            <DropdownMenuLabel class="text-popover-foreground">Audio Actions</DropdownMenuLabel>

                            <DropdownMenuItem @click="toggleActive" class="text-popover-foreground hover:bg-accent hover:text-accent-foreground cursor-pointer">
                                <component :is="audio.is_active ? ToggleRight : ToggleLeft" class="h-4 w-4 mr-2" />
                                {{ audio.is_active ? 'Deactivate' : 'Activate' }}
                            </DropdownMenuItem>

                            <DropdownMenuSeparator class="bg-border" />

                            <DropdownMenuItem @click="deleteAudio" class="text-destructive focus:text-destructive cursor-pointer">
                                <Trash2 class="h-4 w-4 mr-2" />
                                Delete Audio
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- Audio Overview -->
            <Card class="bg-card border-border">
                <CardContent class="p-6 bg-card text-card-foreground">
                    <div class="flex flex-col lg:flex-row gap-6">
                        <!-- ✅ IMPROVED THUMBNAIL WITH ERROR HANDLING -->
                        <div class="w-full lg:w-64 aspect-video bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-950/50 dark:to-purple-950/50 rounded-lg overflow-hidden shrink-0 relative border border-border">
                            <img
                                v-if="audio.thumbnail_url && !thumbnailError"
                                :src="audio.thumbnail_url"
                                :alt="audio.name"
                                class="w-full h-full object-cover"
                                @error="handleThumbnailError"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <Volume2 class="h-16 w-16 text-blue-500 dark:text-blue-400" />
                            </div>

                            <!-- Thumbnail Overlay Info -->
                            <div class="absolute bottom-2 right-2">
                                <Badge variant="secondary" class="bg-black/70 dark:bg-white/20 text-white dark:text-gray-100 border-0">
                                    <Clock class="h-3 w-3 mr-1" />
                                    {{ audio.formatted_duration }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Audio Info -->
                        <div class="flex-1 space-y-4">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <Badge :variant="audio.is_active ? 'default' : 'secondary'" class="gap-1 bg-background border-border">
                                        <component :is="audio.is_active ? Play : AlertTriangle" class="h-3 w-3" />
                                        {{ audio.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                    <Badge v-if="audio.category" variant="outline" class="gap-1 bg-background border-border text-foreground">
                                        <BookOpen class="h-3 w-3" />
                                        {{ audio.category.name }}
                                    </Badge>
                                </div>
                                <CardTitle class="text-2xl mb-2 text-card-foreground">{{ audio.name }}</CardTitle>
                                <CardDescription class="text-base leading-relaxed text-muted-foreground">
                                    {{ audio.description || 'No description available' }}
                                </CardDescription>
                                <div class="text-sm text-muted-foreground mt-2">
                                    Created by <strong class="text-foreground">{{ audio.creator.name }}</strong>
                                </div>
                            </div>

                            <!-- Quick Stats Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/50 rounded-lg border border-blue-200/50 dark:border-blue-800/50">
                                    <Clock class="h-6 w-6 mx-auto mb-2 text-blue-600 dark:text-blue-400" />
                                    <div class="font-semibold text-blue-900 dark:text-blue-100">{{ audio.formatted_duration }}</div>
                                    <div class="text-xs text-blue-700 dark:text-blue-300">Duration</div>
                                </div>
                                <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/50 dark:to-purple-900/50 rounded-lg border border-purple-200/50 dark:border-purple-800/50">
                                    <Users class="h-6 w-6 mx-auto mb-2 text-purple-600 dark:text-purple-400" />
                                    <div class="font-semibold text-purple-900 dark:text-purple-100">{{ analytics.total_listeners }}</div>
                                    <div class="text-xs text-purple-700 dark:text-purple-300">Total Listeners</div>
                                </div>
                                <div class="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/50 rounded-lg border border-green-200/50 dark:border-green-800/50">
                                    <CheckCircle class="h-6 w-6 mx-auto mb-2 text-green-600 dark:text-green-400" />
                                    <div class="font-semibold text-green-900 dark:text-green-100">{{ analytics.completed_listeners }}</div>
                                    <div class="text-xs text-green-700 dark:text-green-300">Completed</div>
                                </div>
                                <div class="text-center p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-950/50 dark:to-gray-900/50 rounded-lg border border-gray-200/50 dark:border-gray-800/50">
                                    <Calendar class="h-6 w-6 mx-auto mb-2 text-gray-600 dark:text-gray-400" />
                                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ new Date(audio.created_at).toLocaleDateString() }}</div>
                                    <div class="text-xs text-gray-700 dark:text-gray-300">Created</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Analytics Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card class="border-0 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/50 border border-blue-200/50 dark:border-blue-800/50">
                    <CardContent class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-blue-500 dark:bg-blue-600 rounded-lg">
                                <Users class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <CardTitle class="text-2xl text-blue-900 dark:text-blue-100">{{ analytics.total_listeners }}</CardTitle>
                                <CardDescription class="text-blue-700 dark:text-blue-300">Total Listeners</CardDescription>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/50 border border-green-200/50 dark:border-green-800/50">
                    <CardContent class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-green-500 dark:bg-green-600 rounded-lg">
                                <TrendingUp class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <CardTitle :class="`text-2xl ${completionRateColor}`">{{ analytics.completion_rate }}%</CardTitle>
                                <CardDescription class="text-green-700 dark:text-green-300">Completion Rate</CardDescription>
                            </div>
                        </div>
                        <Progress :value="analytics.completion_rate" class="h-2 bg-green-100 dark:bg-green-800" />
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/50 dark:to-purple-900/50 border border-purple-200/50 dark:border-purple-800/50">
                    <CardContent class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-purple-500 dark:bg-purple-600 rounded-lg">
                                <BarChart3 class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <CardTitle :class="`text-2xl ${avgProgressColor}`">{{ analytics.avg_progress.toFixed(1) }}%</CardTitle>
                                <CardDescription class="text-purple-700 dark:text-purple-300">Average Progress</CardDescription>
                            </div>
                        </div>
                        <Progress :value="analytics.avg_progress" class="h-2 bg-purple-100 dark:bg-purple-800" />
                    </CardContent>
                </Card>

                <Card class="border-0 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950/50 dark:to-orange-900/50 border border-orange-200/50 dark:border-orange-800/50">
                    <CardContent class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-orange-500 dark:bg-orange-600 rounded-lg">
                                <Timer class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <CardTitle class="text-2xl text-orange-900 dark:text-orange-100">{{ analytics.total_listening_hours }}h</CardTitle>
                                <CardDescription class="text-orange-700 dark:text-orange-300">Total Hours Listened</CardDescription>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabs for Different Views -->
            <Tabs default-value="activity" class="space-y-4">
                <TabsList class="grid w-full grid-cols-2 bg-muted text-muted-foreground">
                    <TabsTrigger value="activity" class="data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm">Recent Activity</TabsTrigger>
                    <TabsTrigger value="insights" class="data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm">Performance Insights</TabsTrigger>
                </TabsList>

                <!-- Recent Activity Tab -->
                <TabsContent value="activity">
                    <Card class="bg-card border-border">
                        <CardHeader class="bg-card">
                            <CardTitle class="flex items-center gap-2 text-card-foreground">
                                <Users class="h-5 w-5" />
                                Recent User Activity
                            </CardTitle>
                            <CardDescription class="text-muted-foreground">
                                Latest user interactions with this audio course
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="p-0 bg-card">
                            <div class="overflow-x-auto">
                                <Table>
                                    <TableHeader class="bg-muted/50">
                                        <TableRow class="border-border">
                                            <TableHead class="text-muted-foreground">User</TableHead>
                                            <TableHead class="text-muted-foreground">Progress</TableHead>
                                            <TableHead class="text-muted-foreground">Current Position</TableHead>
                                            <TableHead class="text-muted-foreground">Status</TableHead>
                                            <TableHead class="text-muted-foreground">Listening Time</TableHead>
                                            <TableHead class="text-muted-foreground">Last Accessed</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-if="recent_activity.length === 0" class="border-border">
                                            <TableCell colspan="6" class="text-center py-16 text-muted-foreground">
                                                <Users class="h-16 w-16 mx-auto text-muted-foreground mb-6" />
                                                <div class="text-lg font-medium mb-2 text-foreground">No user activity yet</div>
                                                <p class="text-muted-foreground">Activity will appear here when users start listening to this audio course</p>
                                            </TableCell>
                                        </TableRow>

                                        <TableRow v-for="activity in recent_activity" :key="activity.id" class="hover:bg-accent/50 border-border text-card-foreground">
                                            <!-- User -->
                                            <TableCell>
                                                <div>
                                                    <div class="font-medium text-foreground">{{ activity.user.name }}</div>
                                                    <div class="text-sm text-muted-foreground">{{ activity.user.email }}</div>
                                                </div>
                                            </TableCell>

                                            <!-- Progress -->
                                            <TableCell>
                                                <div class="space-y-2">
                                                    <div class="flex justify-between items-center text-sm">
                                                        <span :class="getProgressColor(activity.completion_percentage)">
                                                            {{ Math.round(activity.completion_percentage) }}%
                                                        </span>
                                                    </div>
                                                    <Progress :value="activity.completion_percentage" class="h-1 w-20 bg-muted" />
                                                </div>
                                            </TableCell>

                                            <!-- Current Position -->
                                            <TableCell>
                                                <div class="text-sm text-foreground">
                                                    {{ activity.formatted_current_time }} / {{ audio.formatted_duration }}
                                                </div>
                                            </TableCell>

                                            <!-- Status -->
                                            <TableCell>
                                                <Badge v-if="activity.is_completed" variant="default" class="gap-1 bg-background border-border">
                                                    <CheckCircle class="h-3 w-3" />
                                                    Completed
                                                </Badge>
                                                <Badge v-else-if="activity.completion_percentage > 0" variant="secondary" class="gap-1 bg-blue-50 border-border">
                                                    <Play class="h-3 w-3" />
                                                    In Progress
                                                </Badge>
                                                <Badge v-else variant="outline" class="gap-1 bg-background border-border">
                                                    <Clock class="h-3 w-3" />
                                                    Not Started
                                                </Badge>
                                            </TableCell>

                                            <!-- Listening Time -->
                                            <TableCell>
                                                <div class="text-sm text-foreground">
                                                    {{ Math.round(activity.total_listened_time / 60) }} minutes
                                                </div>
                                            </TableCell>

                                            <!-- Last Accessed -->
                                            <TableCell>
                                                <div v-if="activity.last_accessed_at" class="text-sm text-muted-foreground">
                                                    {{ formatDate(activity.last_accessed_at) }}
                                                </div>
                                                <div v-else class="text-sm text-muted-foreground">Never</div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Insights Tab -->
                <TabsContent value="insights">
                    <div class="grid gap-6">
                        <!-- Performance Insights -->
                        <Card class="bg-card border-border">
                            <CardHeader class="bg-card">
                                <CardTitle class="flex items-center gap-2 text-card-foreground">
                                    <BarChart3 class="h-5 w-5" />
                                    Performance Analysis
                                </CardTitle>
                                <CardDescription class="text-muted-foreground">
                                    Detailed breakdown of user engagement and completion metrics
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="bg-card">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Completion Analysis -->
                                    <div class="space-y-4">
                                        <h4 class="font-semibold text-base text-foreground">Completion Analysis</h4>
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-center p-2 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded">
                                                <span class="text-sm text-foreground">Users who started</span>
                                                <span class="font-semibold text-blue-700 dark:text-blue-300">{{ analytics.total_listeners }}</span>
                                            </div>
                                            <div class="flex justify-between items-center p-2 bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800 rounded">
                                                <span class="text-sm text-foreground">Users who completed</span>
                                                <span class="font-semibold text-green-700 dark:text-green-300">{{ analytics.completed_listeners }}</span>
                                            </div>
                                            <div class="flex justify-between items-center p-2 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded">
                                                <span class="text-sm text-foreground">Drop-off rate</span>
                                                <span class="font-semibold text-red-700 dark:text-red-300">
                                                    {{ analytics.total_listeners > 0 ? (100 - analytics.completion_rate).toFixed(1) : 0 }}%
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Engagement Metrics -->
                                    <div class="space-y-4">
                                        <h4 class="font-semibold text-base text-foreground">Engagement Metrics</h4>
                                        <div class="space-y-3">
                                            <div class="flex justify-between items-center p-2 bg-purple-50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-800 rounded">
                                                <span class="text-sm text-foreground">Average completion</span>
                                                <span class="font-semibold text-purple-700 dark:text-purple-300">{{ analytics.avg_progress.toFixed(1) }}%</span>
                                            </div>
                                            <div class="flex justify-between items-center p-2 bg-orange-50 dark:bg-orange-950/20 border border-orange-200 dark:border-orange-800 rounded">
                                                <span class="text-sm text-foreground">Total listening time</span>
                                                <span class="font-semibold text-orange-700 dark:text-orange-300">{{ analytics.total_listening_hours }}h</span>
                                            </div>
                                            <div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded">
                                                <span class="text-sm text-foreground">Avg. per listener</span>
                                                <span class="font-semibold text-gray-700 dark:text-gray-300">
                                                    {{ analytics.total_listeners > 0 ? (analytics.total_listening_hours / analytics.total_listeners).toFixed(1) : 0 }}h
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Recommendations -->
                        <Card class="bg-card border-border">
                            <CardHeader class="bg-card">
                                <CardTitle class="flex items-center gap-2 text-card-foreground">
                                    <TrendingUp class="h-5 w-5" />
                                    Recommendations & Insights
                                </CardTitle>
                                <CardDescription class="text-muted-foreground">
                                    AI-powered suggestions to improve your audio course performance
                                </CardDescription>
                            </CardHeader>
                            <CardContent class="bg-card">
                                <div class="space-y-4">
                                    <Alert v-if="analytics.completion_rate < 50" variant="destructive" class="border-destructive/50 text-destructive dark:border-destructive [&>svg]:text-destructive">
                                        <AlertTriangle class="h-4 w-4" />
                                        <AlertDescription>
                                            <strong>Low completion rate ({{ analytics.completion_rate }}%)</strong>
                                            <br>Consider checking audio quality, reducing duration, or improving content structure.
                                        </AlertDescription>
                                    </Alert>

                                    <Alert v-else-if="analytics.completion_rate >= 80" class="border-green-500/50 text-green-700 dark:text-green-300 bg-green-50 dark:bg-green-950/20 [&>svg]:text-green-600 dark:[&>svg]:text-green-400">
                                        <CheckCircle class="h-4 w-4" />
                                        <AlertDescription>
                                            <strong>Excellent completion rate ({{ analytics.completion_rate }}%)</strong>
                                            <br>This audio is performing very well! Consider creating similar content.
                                        </AlertDescription>
                                    </Alert>

                                    <Alert v-if="analytics.total_listeners === 0" class="border-blue-500/50 text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/20 [&>svg]:text-blue-600 dark:[&>svg]:text-blue-400">
                                        <Users class="h-4 w-4" />
                                        <AlertDescription>
                                            <strong>No listeners yet</strong>
                                            <br>Share this audio course with your target audience to get started.
                                        </AlertDescription>
                                    </Alert>

                                    <Alert v-if="analytics.avg_progress < 25 && analytics.total_listeners > 5" class="border-yellow-500/50 text-yellow-700 dark:text-yellow-300 bg-yellow-50 dark:bg-yellow-950/20 [&>svg]:text-yellow-600 dark:[&>svg]:text-yellow-400">
                                        <TrendingUp class="h-4 w-4" />
                                        <AlertDescription>
                                            <strong>Low average progress ({{ analytics.avg_progress.toFixed(1) }}%)</strong>
                                            <br>Users are not progressing far into the content. Consider improving the introduction or reducing barriers to continuation.
                                        </AlertDescription>
                                    </Alert>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
